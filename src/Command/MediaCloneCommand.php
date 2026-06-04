<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;

#[AsCommand(name: 'netbull:media:clone', description: 'Clone media')]
class MediaCloneCommand extends BaseCommand
{
    public function __construct(
        protected ParameterBagInterface $parameterBag,
        EntityManagerInterface $em,
        Pool $pool,
        ?string $name = null,
    ) {
        parent::__construct($em, $pool, $name);
    }

    public function configure(): void
    {
        $this->addArgument('mediaId', InputArgument::REQUIRED, 'The Media ID');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($input->getArgument('mediaId'));

        if (!$media) {
            $this->log(\sprintf('<error>Unable to find media %s</error>', $input->getArgument('mediaId')));

            return Command::FAILURE;
        }

        $provider = $this->pool->getProvider($media->getProviderName());

        $clone = clone $media;

        $remote = $provider->getCdnPath($provider->getReferenceImage($media));

        $tmpDir = $this->parameterBag->get('kernel.project_dir') . '/tmp';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0o775, true) && !is_dir($tmpDir)) {
            $this->log(\sprintf('<error>Unable to create the temporary directory "%s"</error>', $tmpDir));

            return Command::FAILURE;
        }
        $tmp = $tmpDir . '/' . $media->getProviderReference();

        $content = @file_get_contents($remote);
        if (false === $content) {
            $this->log(\sprintf('<error>Unable to download the source media from "%s"</error>', $remote));

            return Command::FAILURE;
        }

        try {
            if (false === file_put_contents($tmp, $content)) {
                $this->log(\sprintf('<error>Unable to write the temporary file "%s"</error>', $tmp));

                return Command::FAILURE;
            }

            $clone->setBinaryContent(new File($tmp));

            $this->em->persist($clone);
            $this->em->flush();
        } catch (Exception $e) {
            $this->log(\sprintf('<error>Unable to clone media %s: %s</error>', $media->getId(), $e->getMessage()));

            return Command::FAILURE;
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }

        $this->log((string) $clone->getId());

        return Command::SUCCESS;
    }
}
