<?php

namespace NetBull\MediaBundle\Command;

use Exception;
use NetBull\MediaBundle\Entity\MediaInterface;
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
        $em = $this->getManager();
        $this->output = $output;

        $mediaId = $input->getArgument('mediaId');
        $format = $input->getArgument('format');

        /** @var MediaInterface|null $media */
        $media = $em->getRepository(Media::class)->find($mediaId);

        if (!$media){
            $this->log(sprintf('NOT FOUND: %s - %s', $mediaId, $format));
            echo "Media not found with id: " . $mediaId;
            exit(1);
        }

        $provider = $this->pool->getProvider($media->getProviderName());

        $this->log(sprintf('Generating %s size for %s - %d', $format, $media->getName(), $media->getId()));

        try {
            $provider->generateThumbnail($media, $format);
        } catch (Exception $e) {
            $this->log(sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            echo "Generating thumbnails error: " . $e->getMessage();
            exit(1);
        }

        $this->log(sprintf('Done with %d - %s', $media->getId(), $format));

        return 0;
    }
}
