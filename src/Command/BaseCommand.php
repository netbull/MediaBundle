<?php

namespace NetBull\MediaBundle\Command;

use LogicException;
use NetBull\MediaBundle\Provider\Pool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class BaseCommand
 * @package NetBull\MediaBundle\Command
 */
abstract class BaseCommand extends Command
{
    /**
     * Debug switch
     * @var bool
     */
    protected $debug = false;

    /**
     * @var SymfonyStyle|null
     */
    protected $io = null;

    /**
     * @var EntityManagerInterface $em
     */
    protected $em;

    /**
     * @var Pool
     */
    protected $pool;

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
     * @return EntityManagerInterface
     */
    public function getManager(): EntityManagerInterface
    {
        if (!$this->em) {
            throw new LogicException('The DoctrineBundle is not registered in your application. Try running "composer require symfony/orm-pack".');
        }

        return $this->em;
    }

    /**
     * Clear the Doctrine's cache
     */
    protected function optimize()
    {
        if ($this->em) {
            $this->em->clear();
        }
    }

    /**
     * Output used for nice debug
     * @param $text
     */
    protected function log($text)
    {
        if (!$this->debug) {
            return;
        }

        $this->io->writeln($text);
    }
}
