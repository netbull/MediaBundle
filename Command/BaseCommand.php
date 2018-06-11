<?php

namespace NetBull\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class BaseCommand
 * @package NetBull\MediaBundle\Command
 */
abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * Debug switch
     * @var bool
     */
    protected $debug = false;

    /**
     * @var
     */
    protected $output;

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    public function getManager()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        return $em;
    }

    /**
     * Clear the Doctrine's cache
     */
    protected function optimize()
    {
        if ($this->getContainer()->has('doctrine')) {
            $this->getManager()->clear();
        }
    }

    /**
     * Output used for nice debug
     * @param $text
     */
    protected function output($text)
    {
        if (!$this->debug) {
            return;
        }

        $this->output->writeln($text);
    }
}
