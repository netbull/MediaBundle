<?php

namespace NetBull\MediaBundle\Command;

use NetBull\MediaBundle\Provider\Pool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    /**
     * Debug switch
     * @var bool
     */
    protected bool $debug = false;

    /**
     * @var SymfonyStyle|null
     */
    protected ?SymfonyStyle $io = null;

    /**
     * @param EntityManagerInterface $em
     * @param Pool $pool
     * @param string|null $name
     */
    public function __construct(
        protected EntityManagerInterface $em,
        protected Pool $pool,
        ?string $name = null
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
     * @param $text
     */
    protected function log($text): void
    {
        if (!$this->debug) {
            return;
        }

        $this->io->writeln($text);
    }
}
