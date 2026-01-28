<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Command;

use Exception;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'netbull:media:create-thumbnail', description: 'Create Image thumbnail with new media formats')]
class PhotoCreateThumbnailCommand extends BaseCommand
{
    public function configure(): void
    {
        $this->addArgument('mediaId', InputArgument::REQUIRED, 'The Media ID')
            ->addArgument('format', InputArgument::OPTIONAL, 'The thumbnail format', 'normal');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $mediaId = $input->getArgument('mediaId');
        $format = $input->getArgument('format');

        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($mediaId);

        if (!$media) {
            $this->log(\sprintf('NOT FOUND: %s - %s', $mediaId, $format));
            $this->io->writeLn('Media not found with id: ' . $mediaId);

            return Command::FAILURE;
        }

        $provider = $this->pool->getProvider($media->getProviderName());

        $this->log(\sprintf('Generating %s size for %s - %d', $format, $media->getName(), $media->getId()));

        try {
            $provider->generateThumbnail($media, $format);
        } catch (Exception $e) {
            $this->log(\sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            $this->io->writeln('Generating thumbnails error: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->log(\sprintf('Done with %d - %s', $media->getId(), $format));

        return Command::SUCCESS;
    }
}
