<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
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
     * @var \AMQPExchange[]
     */
    private $exchanges = [];

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
        $count = $this->getAMQPQueue($queueName)->declare();

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
        try {
            $this->getAMQPQueue($queueName)->purge();
        } catch (\AMQPQueueException $e) {
            if ($e->getCode() === 404) {
                var_export(sprintf('Queue %s does not exists\\n', $queueName));
                return;
            }

            throw $e;
        }
    }

    /**
     *
     * @param string       $queueName
     * @param string       $command
     * @param PyStringNode $string
     *
     * @Given I publish in amqp queue :queueName message :command with content:
     */
    public function iPublishInAmqpQueueMessageWithContent(string $queueName, string $command, PyStringNode $string): void
    {
        try {
            $this->getAMQPConnection($queueName)->publish($string, ['content_type' => 'text/plain', 'type' => $command]);
        } catch (\AMQPQueueException $e) {
            if ($e->getCode() === 404) {
                var_export(sprintf('Queue %s does not exists\\n', $queueName));
                return;
            }

            throw $e;
        }
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

    /**
     * @param string $queueName queueName
     *
     * @return \AmqpExchange
     */
    private function getAMQPExchange(string $queueName): \AmqpExchange
    {
        if (false === array_key_exists($queueName, $this->exchanges)) {
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

            $this->exchanges[$queueName] = AmqpConnection::fromDsn($dsn)->exchange();
        }

        return $this->exchanges[$queueName];
    }

    /**
     * @param string $queueName queueName
     *
     * @return AmqpConnection
     *
     * @throws \Exception
     */
    private function getAMQPConnection(string $queueName): AmqpConnection
    {
            if (false === array_key_exists($queueName, $this->transports)) {
                throw new \Exception(sprintf('AMQP Connection with name %s does not exist.', $queueName));
            }

            $dsn = $this->transports[$queueName];

            if (preg_match('/^env\((?P<env_var>.*)\)$/', $dsn, $matches)) {
                $dsn = getenv($matches['env_var']);
            }

            return AmqpConnection::fromDsn($dsn);
    }
}
