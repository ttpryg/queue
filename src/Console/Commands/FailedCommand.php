<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Ttpryg\Queue\Contracts\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FailedCommand extends Command
{
    protected static $defaultName = 'queue:failed';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('List all failed queue jobs')
            ->addOption('queue', 'q', InputOption::VALUE_OPTIONAL, 'The queue name to filter failed jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->getOption('queue');
        $failedJobs = $this->queueHandler->listFailed($queue ? (string) $queue : null);

        if (empty($failedJobs)) {
            $output->writeln('<info>No failed jobs found.</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<comment>ID | Queue | Job | Exception | Failed At</comment>');
        $output->writeln('--------------------------------------------------');

        foreach ($failedJobs as $job) {
            $failedAt = $job->failed_at?->format('Y-m-d H:i:s') ?? 'N/A';
            $output->writeln("{$job->id} | {$job->queue} | {$job->job} | {$job->exception} | {$failedAt}");
        }

        return Command::SUCCESS;
    }
}
