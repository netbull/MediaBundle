<?php

namespace NetBull\MediaBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use NetBull\MediaBundle\Entity\Media;

/**
 * Class PhotoResizeMissingCommand
 * @package NetBull\MediaBundle\Command
 */
class PhotoResizeMissingCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('media:resize')
            ->setDescription('Resize missing thumbnails')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getManager();
        $this->output = $output;

        $qb = $em->createQueryBuilder();
        $medias = $qb->select('m.id')
            ->from(Media::class, 'm')
            ->where($qb->expr()->eq('m.providerName', ':providerName'))
            ->setParameters([
                'providerName' => 'netbull_media.provider.image',
            ])
            ->getQuery()
            ->getArrayResult()
        ;

        $this->log(sprintf('Loaded %s medias for generating thumbs', count($medias)));

        foreach ($medias as $media) {
            $this->_processMedia($media['id']);
        }

        $this->log('Done.');

        return 0;
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
