<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ttpryg\Queue\Contracts\QueueInterface;

class RetryCommand extends Command
{
    protected static $defaultName = 'queue:retry';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Retry failed queue jobs')
            ->addOption('id', 'i', InputOption::VALUE_OPTIONAL, 'The ID of the failed job')
            ->addOption('queue', 'q', InputOption::VALUE_OPTIONAL, 'The queue name to retry jobs from');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('id');
        $queue = $input->getOption('queue');

        $count = $this->queueHandler->retry(
            $id !== null ? (int) $id : null,
            $queue !== null ? (string) $queue : null
        );

        $output->writeln("<info>Retried {$count} failed job(s).</info>");

        return Command::SUCCESS;
    }
}
