<?php

namespace WakeOnWeb\BehatContexts\AmqpAdapter;

use Symfony\Component\Messenger\Transport\AmqpExt\Connection as AmqpConnection;

/**
 * Class SymfonyMessengerAdapter
 *
 * @author Stephane PY <s.py@wakeonweb.com>
 */
class SymfonyMessengerAdapter implements AdapterInterface
{
    /** @var array */
    private $transports;

    /**
     * @param array $transports transports
     */
    public function __construct(array $transports)
    {
        $this->transports = $transports;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $transport, string $content, string $command = null): void
    {
        $this->getAmqpConnection($transport)->publish($content, [
            'content_type' => 'text/plain',
            'type' => $command,
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function countMessagesInTransport(string $transport): int
    {
        return $this->getAmqpConnection($transport)->queue()->declare();
    }


    /**
     * {@inheritdoc}
     */
    public function purgeAllTransports(): void
    {
        foreach ($this->transports as $transportName => $dsn) {
            $this->purgeTransport($transportName);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function purgeTransport(string $transport): void
    {
        $this->getAmqpConnection($transport)->queue()->purge();

    }


    /**
     * {@inheritdoc}
     */
    public function setupQueues(): void
    {
        foreach ($this->transports as $transportName => $dsn) {
            $this->getAmqpConnection($transportName)->setup();
        }
    }

    private function getAmqpConnection(string $transport): AmqpConnection
    {
        if (false === array_key_exists($transport, $this->transports)) {
            throw new \Exception(sprintf('AMQP Connection with name %s does not exist.', $transport));
        }

        $dsn = $this->transports[$transport];

        if (preg_match('/^env\((?P<env_var>.*)\)$/', $dsn, $matches)) {
            $dsn = getenv($matches['env_var']);
        }

        return AmqpConnection::fromDsn($dsn);
    }
}
