<?php

namespace NetBull\MediaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use NetBull\MediaBundle\Entity\Media;

/**
 * Class PhotoCreateThumbnailCommand
 * @package NetBull\MediaBundle\Command
 */
class PhotoCreateThumbnailCommand extends BaseCommand
{
    protected $quiet = false;
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('netbull:media:create-thumbnail')
            ->addArgument('mediaId', InputArgument::REQUIRED, 'The Media ID')
            ->addArgument('format', InputArgument::OPTIONAL, 'The thumbnail format', 'normal')
            ->setDescription('Create Image thumbnail with new media formats')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        sleep(5);
        $em = $this->getManager();
        $this->output = $output;

        $mediaId = $input->getArgument('mediaId');
        $format = $input->getArgument('format');

        $qb = $em->createQueryBuilder();
        $media = $qb->select('m')
            ->from(Media::class, 'm')
            ->where($qb->expr()->eq('m.id', ':id'))
            ->setParameter('id', $mediaId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$media){
            $this->log(sprintf('NOT FOUND: %s - %s', $mediaId, $format));
            return;
        }

        $provider = $this->getMediaPool()->getProvider($media->getProviderName());

        $this->log(sprintf('Generating %s size for %s - %d', $format, $media->getName(), $media->getId()));

        try {
            $provider->generateThumbnail($media, $format);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            return;
        }

        $this->log(sprintf('Done with %d - %s', $media->getId(), $format));
    }

    /**
     * @return \MediaBundle\Provider\Pool|object
     */
    public function getMediaPool()
    {
        return $this->getContainer()->get('netbull_media.pool');
    }

    /**
     * Write a message to the output.
     *
     * @param string $message
     */
    protected function log($message)
    {
        if (false === $this->quiet) {
            $this->output->writeln($message);
        }
    }
}
