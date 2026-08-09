<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Ttpryg\Queue\Contracts\QueueInterface;
use Ttpryg\Queue\Entities\QueueJob;
use Ttpryg\Queue\Events\QueueEventManager;

class WorkCommand extends Command
{
    protected static $defaultName = 'queue:work';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Process jobs on the queue worker')
            ->addOption('queue', 'q', InputOption::VALUE_OPTIONAL, 'The queue name to work on', 'default')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Priorities to filter jobs (comma-separated)', '')
            ->addOption('rest', 'r', InputOption::VALUE_OPTIONAL, 'Seconds to rest when no job is available', 3)
            ->addOption('memory', 'm', InputOption::VALUE_OPTIONAL, 'Memory limit in MB', 128)
            ->addOption('tries', 't', InputOption::VALUE_OPTIONAL, 'Maximum attempts for a job', 3)
            ->addOption('delay', 'd', InputOption::VALUE_OPTIONAL, 'Delay before retrying failed job in seconds', 60);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getOption('queue');
        $priorityStr = (string) $input->getOption('priority');
        $rest = (int) $input->getOption('rest');
        $memoryLimit = (int) $input->getOption('memory');
        $maxTries = (int) $input->getOption('tries');

        $priorities = $priorityStr !== '' ? array_map('intval', explode(',', $priorityStr)) : [];

        $output->writeln("<info>Worker started for queue [{$queueName}]</info>");
        QueueEventManager::workerStarted($this->queueHandler->name(), $queueName, $priorities);

        $startTime = microtime(true);
        $jobsProcessed = 0;

        while (true) {
            if (memory_get_usage(true) / 1024 / 1024 >= $memoryLimit) {
                $output->writeln('<error>Memory limit exceeded. Stopping worker.</error>');
                break;
            }

            $job = $this->queueHandler->pop($queueName, $priorities);

            if ($job === null) {
                sleep($rest);

                continue;
            }

            $this->processJob($job, $maxTries, $queueName, $output);
            $jobsProcessed++;
        }

        $uptime = microtime(true) - $startTime;
        QueueEventManager::workerStopped($this->queueHandler->name(), $queueName, $priorities, $uptime, $jobsProcessed);

        return Command::SUCCESS;
    }

    protected function processJob(QueueJob $job, int $maxTries, string $queueName, OutputInterface $output): void
    {
        $output->writeln("<comment>Processing job: {$job->job} [ID: {$job->id}]</comment>");
        $startTime = microtime(true);

        QueueEventManager::jobProcessingStarted($this->queueHandler->name(), $queueName, $job);

        try {
            if ($job->payload === null) {
                throw new \RuntimeException('Job payload is empty.');
            }

            $jobClass = $job->payload->job;
            if (! class_exists($jobClass)) {
                throw new \RuntimeException("Job class '{$jobClass}' not found.");
            }

            $instance = new $jobClass($job->payload->data);
            if (method_exists($instance, 'execute')) {
                $instance->execute();
            } elseif (method_exists($instance, 'process')) {
                $instance->process();
            }

            $this->queueHandler->done($job);
            $duration = microtime(true) - $startTime;

            QueueEventManager::jobProcessingCompleted($this->queueHandler->name(), $queueName, $job, $duration);
            $output->writeln("<info>Job completed: {$job->job} [ID: {$job->id}] in ".round($duration, 4).'s</info>');
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            QueueEventManager::jobFailed($this->queueHandler->name(), $queueName, $job, $e, $duration);

            if ($job->getAttempts() >= $maxTries) {
                $this->queueHandler->failed($job, $e);
                $output->writeln("<error>Job failed permanently: {$job->job} [ID: {$job->id}]</error>");
            } else {
                $this->queueHandler->later($job, 60);
                $output->writeln("<comment>Job failed, will retry: {$job->job} [ID: {$job->id}]</comment>");
            }
        }
    }
}
