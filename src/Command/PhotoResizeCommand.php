<?php

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'netbull:media:sync-thumbnails', description: 'Sync uploaded image thumbs with new media formats')]
class PhotoResizeCommand extends BaseCommand
{
    /**
     * @return void
     */
    public function configure(): void
    {
        $this->addArgument('context', InputArgument::OPTIONAL, 'The context');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->getManager();

        $context  = $input->getArgument('context');
        if (null === $context) {
            $contexts = array_keys($this->pool->getContexts());
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select the context', $contexts);
            $context = $helper->ask($input, $output, $question);
        }

        $this->debug = $input->getOption('quiet');
        $this->io = new SymfonyStyle($input, $output);

        $qb = $em->createQueryBuilder();
        $medias = $qb->select('m.id')
            ->from(Media::class, 'm')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('m.providerName', ':providerName'),
                $qb->expr()->eq('m.context', ':context')
            ))
            ->setParameters([
                'providerName' => 'netbull_media.provider.image',
                'context' => $context,
            ])
            ->getQuery()
            ->getArrayResult();

        $this->log(sprintf('Loaded %s medias from context: %s for generating thumbs', count($medias), $context));

        foreach ( $medias as $media ) {
            $this->_processMedia($media['id']);
            $this->optimize();
        }

        $this->log('Done.');
        return Command::SUCCESS;
    }

    /**
     * @param $id
     * @return void
     */
    protected function _processMedia($id): void
    {
        $qb = $this->em->createQueryBuilder();
        try {
            $media = $qb->select('m')
                ->from(Media::class, 'm')
                ->where($qb->expr()->eq('m.id', ':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return;
        }

        if (!$media) {
            return;
        }

        $provider = $this->pool->getProvider($media->getProviderName());

        $this->log('Generating thumbs for '.$media->getName().' - '.$media->getId());

        try {
            $provider->removeThumbnails($media);
        } catch (Exception $e) {
            $this->log(sprintf('<error>Unable to remove old thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            return;
        }

        try {
            $provider->generateThumbnails($media);
        } catch (Exception $e) {
            $this->log(sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            return;
        }
    }
}
