<?php

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class PhotoResizeCommand
 * @package NetBull\MediaBundle\Command
 */
class PhotoResizeCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('media:sync-thumbnails')
            ->addArgument('context', InputArgument::OPTIONAL, 'The context')
            ->setDescription('Sync uploaded image thumbs with new media formats');
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
     */
    protected function _processMedia($id)
    {
        $em = $this->getManager();

        $qb = $em->createQueryBuilder();
        try {
            $media = $qb->select('m')
                ->from(Media::class, 'm')
                ->where($qb->expr()->eq('m.id', ':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
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
