<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ttpryg\Queue\Contracts\QueueInterface;

class ForgetCommand extends Command
{
    protected static $defaultName = 'queue:forget';

    public function __construct(
        protected QueueInterface $queueHandler
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Delete a failed queue job by ID')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'The ID of the failed job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('id');
        if ($id === null) {
            $output->writeln('<error>Option --id is required.</error>');

            return Command::FAILURE;
        }

        $result = $this->queueHandler->forget((int) $id);

        if ($result) {
            $output->writeln("<info>Failed job [ID: {$id}] deleted successfully.</info>");
        } else {
            $output->writeln("<error>Failed job [ID: {$id}] not found.</error>");
        }

        return Command::SUCCESS;
    }
}
