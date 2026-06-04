<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    protected ?SymfonyStyle $io = null;

    public function __construct(
        protected EntityManagerInterface $em,
        protected Pool $pool,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * Clear the Doctrine's cache
     */
    protected function optimize(): void
    {
        $this->em->clear();
    }

    /**
     * Write a progress/diagnostic line. Output honours the command's verbosity natively
     * (e.g. suppressed with --quiet), so no extra gating is needed.
     */
    protected function log(string $text): void
    {
        $this->io?->writeln($text);
    }
}
