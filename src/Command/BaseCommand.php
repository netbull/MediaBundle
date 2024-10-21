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
     * @var EntityManagerInterface $em
     */
    protected EntityManagerInterface $em;

    /**
     * @var Pool
     */
    protected Pool $pool;

    /**
     * BaseCommand constructor.
     * @param EntityManagerInterface $em
     * @param Pool $pool
     * @param null|string $name
     */
    public function __construct(EntityManagerInterface $em, Pool $pool, ?string $name = null)
    {
        parent::__construct($name);

        $this->em = $em;
        $this->pool = $pool;
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
