<?php

declare(strict_types=1);

namespace Ttpryg\Queue;

use Psr\Log\LoggerInterface;
use Ttpryg\Queue\Config\QueueConfig;
use Ttpryg\Queue\Contracts\DatabaseAdapterInterface;
use Ttpryg\Queue\Contracts\EventDispatcherInterface;
use Ttpryg\Queue\Contracts\QueueInterface;
use Ttpryg\Queue\Events\QueueEventManager;
use Ttpryg\Queue\Exceptions\QueueException;

class QueueManager
{
    public function __construct(
        protected QueueConfig $config,
        protected ?DatabaseAdapterInterface $db = null,
        protected ?LoggerInterface $logger = null,
        protected ?EventDispatcherInterface $eventDispatcher = null
    ) {
        if ($eventDispatcher !== null) {
            QueueEventManager::setDispatcher($eventDispatcher);
        }
    }

    public function init(): QueueInterface
    {
        return $this->handler($this->config->defaultHandler);
    }

    public function handler(string $name): QueueInterface
    {
        if (! isset($this->config->handlers[$name])) {
            throw QueueException::forIncorrectHandler();
        }

        $handlerClass = $this->config->handlers[$name];

        if ($handlerClass === \Ttpryg\Queue\Handlers\DatabaseHandler::class && $this->db !== null) {
            return new $handlerClass($this->config, $this->db, $this->logger);
        }

        return new $handlerClass($this->config, $this->logger);
    }
}
