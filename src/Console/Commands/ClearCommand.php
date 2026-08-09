<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Ttpryg\Queue\Contracts\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    protected static $defaultName = 'queue:clear';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Clear waiting and reserved jobs from a queue')
            ->addOption('queue', 'q', InputOption::VALUE_OPTIONAL, 'The queue name to clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->getOption('queue');
        $this->queueHandler->clear($queue ? (string) $queue : null);

        $output->writeln('<info>Queue cleared successfully.</info>');
        return Command::SUCCESS;
    }
}
