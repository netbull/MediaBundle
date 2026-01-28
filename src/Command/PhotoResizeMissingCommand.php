<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Exception;
use NetBull\MediaBundle\Entity\Media;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'netbull:media:resize', description: 'Resize missing thumbnails')]
class PhotoResizeMissingCommand extends BaseCommand
{
    public function configure(): void
    {
        $this->addArgument('context', InputArgument::OPTIONAL, 'The context');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $context = $input->getArgument('context');
        if (null === $context) {
            $contexts = array_keys($this->pool->getContexts());
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select the context', $contexts);
            $context = $helper->ask($input, $output, $question);
        }

        $qb = $this->em->createQueryBuilder();
        $medias = $qb->select('m.id')
            ->from(Media::class, 'm')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('m.providerName', ':providerName'),
                $qb->expr()->eq('m.context', ':context'),
            ))
            ->setParameters(new ArrayCollection([
                new Parameter('providerName', 'netbull_media.provider.image'),
                new Parameter('context', $context),
            ]))
            ->getQuery()
            ->getArrayResult();

        $this->log(\sprintf('Loaded %s medias for generating thumbs', \count($medias)));

        foreach ($medias as $media) {
            $this->_processMedia($media['id']);
        }

        $this->log('Done.');

        return Command::SUCCESS;
    }

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
        $format = $provider->getFormatName($media, 'tiny');

        if ($this->hasThumbnails($provider->generatePublicUrl($media, $format))) {
            return;
        }

        $this->log('Generating thumbs for ' . $media->getName() . ' - ' . $media->getId());

        try {
            $provider->removeThumbnails($media);
        } catch (Exception $e) {
            $this->log(\sprintf('<error>Unable to remove old thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            $this->optimize();

            return;
        }

        try {
            $provider->generateThumbnails($media);
        } catch (Exception $e) {
            $this->log(\sprintf('<error>Unable to generated new thumbnails, media: %s - %s </error>', $media->getId(), $e->getMessage()));
            $this->optimize();

            return;
        }

        $this->optimize();
    }

    private function hasThumbnails(string $url): bool
    {
        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        // don't download content
        curl_setopt($ch, \CURLOPT_NOBODY, 1);
        curl_setopt($ch, \CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        if (false !== $result) {
            return true;
        }

        return false;
    }
}
