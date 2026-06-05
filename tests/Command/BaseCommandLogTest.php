<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\MediaBundle\Command\BaseCommand;
use NetBull\MediaBundle\Provider\Pool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(BaseCommand::class)]
class BaseCommandLogTest extends TestCase
{
    /**
     * Regression: commands used to be silent because log() was gated on a $debug flag that was
     * never enabled. log() must now actually write at normal verbosity.
     */
    public function testLogWritesAtNormalVerbosity(): void
    {
        $output = new BufferedOutput();
        $command = $this->command($output);

        $command->emitLog('processing media 1');

        self::assertStringContainsString('processing media 1', $output->fetch());
    }

    public function testLogIsSuppressedWhenQuiet(): void
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $command = $this->command($output);

        $command->emitLog('should not appear');

        self::assertSame('', $output->fetch());
    }

    private function command(BufferedOutput $output)
    {
        $command = new class($this->createMock(EntityManagerInterface::class), $this->createMock(Pool::class)) extends BaseCommand {
            public function emitLog(string $text): void
            {
                $this->log($text);
            }

            public function bindIo(SymfonyStyle $io): void
            {
                $this->io = $io;
            }
        };
        $command->bindIo(new SymfonyStyle(new ArrayInput([]), $output));

        return $command;
    }
}
