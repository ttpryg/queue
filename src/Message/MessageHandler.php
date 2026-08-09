<?php

declare(strict_types=1);

namespace Ttpryg\Queue\Message;

use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Contracts\JobInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageHandler implements MessageHandlerInterface
{
    private ?MessageBusInterface $messageBus = null;

    public function __construct(
        protected QueueConfig $config,
        protected ?LoggerInterface $logger = null
    ) {
    }

    public function __invoke(SymfonyQueueMessage $message): void
    {
        $jobClass = $this->resolveJobClass($message->job);

        if (!class_exists($jobClass)) {
            throw new RuntimeException("Job class '{$jobClass}' not found.");
        }

        /** @var JobInterface $job */
        $job = new $jobClass();

        $this->logger?->info("Processing job: {$message->job}", [
            'queue' => $message->getQueue(),
            'priority' => $message->getPriority(),
        ]);

        $job->setData($message->data);
        $job->execute();
    }

    private function resolveJobClass(string $name): string
    {
        if (!isset($this->config->jobHandlers[$name])) {
            throw new RuntimeException("Job handler '{$name}' not found.");
        }

        return $this->config->jobHandlers[$name];
    }

    public function setMessageBus(MessageBusInterface $messageBus): void
    {
        $this->messageBus = $messageBus;
    }

    public function dispatch(Envelope $envelope): Envelope
    {
        if ($this->messageBus === null) {
            throw new RuntimeException('MessageBus not set. Call setMessageBus first.');
        }

        return $this->messageBus->dispatch($envelope);
    }
}
