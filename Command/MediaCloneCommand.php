<?php

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use NetBull\MediaBundle\Entity\Media;

/**
 * Class MediaCloneCommand
 * @package NetBull\MediaBundle\Command
 */
class MediaCloneCommand extends BaseCommand
{
    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * MediaCloneCommand constructor.
     * @param ParameterBag $parameterBag
     * @param EntityManagerInterface $em
     * @param Pool $pool
     * @param null|string $name
     */
    public function __construct(ParameterBag $parameterBag, EntityManagerInterface $em, Pool $pool, ?string $name = null)
    {
        parent::__construct($em, $pool, $name);

        $this->parameterBag = $parameterBag;
    }


    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('media:clone')
            ->addArgument('mediaId', InputArgument::REQUIRED, 'The Media ID')
            ->setDescription('Clone Media')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getManager();
        $this->output = $output;

        $media = $em->getRepository(Media::class)->find($input->getArgument('mediaId'));

        if (!$media) {
            $this->log('null');
        } else {
            $provider = $this->pool->getProvider($media->getProviderName());

            $clone = clone $media;

            $remote = $provider->getCdnPath($provider->getReferenceImage($media));
            $tmp = $this->parameterBag->get('kernel.root_dir') . '/../tmp/' . $media->getProviderReference();
            $content = file_get_contents($remote);

            if (!$content) {
                return;
            }

            if (!file_put_contents($tmp, $content)) {
                return;
            }

            $clone->setBinaryContent(new File($tmp));

            try {
                $em->persist($clone);
                $em->flush();
            } catch (\Exception $e) {
                return;
            }

            unlink($tmp);
            $this->log($clone->getId());
        }
    }

    /**
     * @param $id
     */
    protected function _processMedia($id)
    {
        $em = $this->getManager();

        $qb = $em->createQueryBuilder();
        $media = $qb->select('m')
            ->from(Media::class, 'm')
            ->where($qb->expr()->eq('m.id', ':id'))
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$media) {
            return;
        }

        $provider = $this->pool->getProvider($media->getProviderName());
        $format = $provider->getFormatName($media, 'tiny');

        if ($this->hasThumbnails($provider->generatePublicUrl($media, $format))) {
            return;
        }

        $this->log('Generating thumbs for '.$media->getName().' - '.$media->getId());

        try {
            $provider->removeThumbnails($media);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>Unable to remove old thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            $this->optimize();
            return;
        }

        try {
            $provider->generateThumbnails($media);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            $this->optimize();
            return;
        }

        $this->optimize();
    }

    /**
     * @param $url
     * @return bool
     */
    private function hasThumbnails($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // don't download content
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        if (false !== $result) {
            return true;
        }

        return false;
    }
}
