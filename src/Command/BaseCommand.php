<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    /**
     * Debug switch
     */
    protected bool $debug = false;

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
     * Output used for nice debug
     */
    protected function log($text): void
    {
        if (!$this->debug) {
            return;
        }

        $this->io->writeln($text);
    }
}
