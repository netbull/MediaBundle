<?php

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use NetBull\MediaBundle\Entity\Media;

#[AsCommand(name: 'netbull:media:clone', description: 'Clone media')]
class MediaCloneCommand extends BaseCommand
{
    /**
     * @var ParameterBag
     */
    protected ParameterBag $parameterBag;

    /**
     * @param ParameterBag $parameterBag
     * @param EntityManagerInterface $em
     * @param Pool $pool
     * @param string|null $name
     */
    public function __construct(ParameterBag $parameterBag, EntityManagerInterface $em, Pool $pool, ?string $name = null)
    {
        parent::__construct($em, $pool, $name);
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return void
     */
    public function configure(): void
    {
        $this->addArgument('mediaId', InputArgument::REQUIRED, 'The Media ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($input->getArgument('mediaId'));

        if (!$media) {
            $this->log('null');
        } else {
            $provider = $this->pool->getProvider($media->getProviderName());

            $clone = clone $media;

            $remote = $provider->getCdnPath($provider->getReferenceImage($media));
            $tmp = $this->parameterBag->get('kernel.project_dir') . '/tmp/' . $media->getProviderReference();
            $content = file_get_contents($remote);

            if (!$content) {
                return Command::SUCCESS;
            }

            if (!file_put_contents($tmp, $content)) {
                return Command::SUCCESS;
            }

            $clone->setBinaryContent(new File($tmp));

            try {
                $this->em->persist($clone);
                $this->em->flush();
            } catch (Exception $e) {
                return Command::SUCCESS;
            }

            unlink($tmp);
            $this->log($clone->getId());
        }

        return Command::SUCCESS;
    }
}
