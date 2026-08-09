<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Ttpryg\Queue\Contracts\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlushCommand extends Command
{
    protected static $defaultName = 'queue:flush';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Flush failed queue jobs')
            ->addOption('hours', 'H', InputOption::VALUE_OPTIONAL, 'Flush jobs older than X hours')
            ->addOption('queue', 'q', InputOption::VALUE_OPTIONAL, 'The queue name to flush');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hours = $input->getOption('hours');
        $queue = $input->getOption('queue');

        $this->queueHandler->flush(
            $hours !== null ? (int) $hours : null,
            $queue !== null ? (string) $queue : null
        );

        $output->writeln('<info>Failed jobs flushed successfully.</info>');
        return Command::SUCCESS;
    }
}
