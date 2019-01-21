<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection as AmqpConnection;

/**
 * AmqpContext
 *
 * @uses Context
 *
 * @author Stephane PY <s.py@wakeonweb.com>
 */
class AmqpContext implements Context
{
    use KernelDictionary;

    /**
     * @var string[]
     */
    private $transports;

    /**
     * @var \AmqpQueue[]
     */
    private $queues = [];

    /**
     * @param string[] $transports transports
     */
    public function __construct(array $transports)
    {
        $this->transports = $transports;
    }

    /**
     * @param int    $countExpected
     * @param string $queueName
     *
     * @throws \Exception
     *
     * @Given I have :count messages in amqp :queueName queue
     */
    public function iHaveMessagesInAmqpQueue(int $countExpected, string $queueName): void
    {
        $count = $this->getAMQPConnection($queueName)->declare();

        if ($count !== $countExpected) {
            throw new \Exception(sprintf('There is %d message(s) in the queue at this moment.', $count));
        }
    }

    /**
     * @BeforeScenario @amqp
     *
     * @Given I clear messages in all amqp queues
     */
    public function iClearMessagesInAllAmqpQueues(): void
    {
        foreach ($this->transports as $queue => $dsn) {
            $this->iClearMessagesInAmqpQueue($queue);
        }
    }

    /**
     * @param string $queueName
     *
     * @Given I clear messages in amqp :queueName queue
     */
    public function iClearMessagesInAmqpQueue(string $queueName): void
    {
        $this->getAMQPQueue($queueName)->purge();
    }

    /**
     * @param string $queueName queueName
     *
     * @return \AmqpQueue
     */
    private function getAMQPQueue(string $queueName): \AmqpQueue
    {
        if (false === array_key_exists($queueName, $this->queues)) {
            if (false === array_key_exists($queueName, $this->transports)) {
                throw new \Exception(sprintf('AMQP Connection with name %s does not exist.', $queueName));
            }

            $dsn = $this->transports[$queueName];

            if (preg_match('/^env\((?P<env_var>.*)\)$/', $dsn, $matches)) {
                $dsn = getenv($matches['env_var']);
            }

            if (false === class_exists(AmqpConnection::class)) {
                throw new \Exception('We support at this moment only symfony/messenger arm-pack to provide an AmqpConnection.');
            }

            $this->queues[$queueName] = AmqpConnection::fromDsn($dsn)->queue();
        }

        return $this->queues[$queueName];
    }
}
