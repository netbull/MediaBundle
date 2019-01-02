<?php

namespace NetBull\MediaBundle\Command;

use NetBull\MediaBundle\Provider\Pool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var OutputInterface
     */
    protected $output;

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
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    public function getManager()
    {
        if (!$this->em) {
            throw new \LogicException('The DoctrineBundle is not registered in your application. Try running "composer require symfony/orm-pack".');
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

        $this->output->writeln($text);
    }
}
