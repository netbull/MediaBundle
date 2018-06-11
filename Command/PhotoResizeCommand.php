<?php

namespace NetBull\MediaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

use NetBull\MediaBundle\Entity\Media;

/**
 * Class PhotoResizeCommand
 * @package NetBull\MediaBundle\Command
 */
class PhotoResizeCommand extends BaseCommand
{
    protected $quiet = false;
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('media:sync-thumbnails')
            ->addArgument('context', InputArgument::OPTIONAL, 'The context')
            ->setDescription('Sync uploaded image thumbs with new media formats')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getManager();

        $context  = $input->getArgument('context');
        if (null === $context) {
            $contexts = array_keys($this->getMediaPool()->getContexts());
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select the context', $contexts);
            $context = $helper->ask($input, $output, $question);
        }

        $this->quiet = $input->getOption('quiet');
        $this->output = $output;

        $qb = $em->createQueryBuilder();
        $medias = $qb->select('m.id')
            ->from(Media::class, 'm')
            ->where($qb->expr()->eq('m.providerName', ':providerName'))
            ->andWhere($qb->expr()->eq('m.context', ':context'))
            ->setParameters([
                'providerName' => 'netbull_media.provider.image',
                'context'      => $context,
            ])
            ->getQuery()
            ->getArrayResult()
        ;

        $this->log(sprintf('Loaded %s medias from context: %s for generating thumbs', count($medias), $context));

        foreach ( $medias as $media ) {
            $this->_processMedia($media['id']);
            $this->optimize();
        }

        $this->log('Done.');
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

        if( !$media ){
            return;
        }

        $provider = $this->getMediaPool()->getProvider($media->getProviderName());

        $this->log('Generating thumbs for '.$media->getName().' - '.$media->getId());

        try {
            $provider->removeThumbnails($media);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>Unable to remove old thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            return;
        }

        try {
            $provider->generateThumbnails($media);
        } catch (\Exception $e) {
            $this->log(sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            return;
        }
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
