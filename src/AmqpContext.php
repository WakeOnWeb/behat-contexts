<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * AmqpContext.
 *
 * @uses \Context
 *
 * @author Stephane PY <s.py@wakeonweb.com>
 */
class AmqpContext implements Context
{
    use KernelDictionary;

    /**
     * @var AmqpAdapter\AdapterInterface
     */
    private $adapter;

    /**
     * @param string[] $transports transports
     */
    public function __construct(array $transports, string $adapterClass = AmqpAdapter\SymfonyMessengerAdapter::class, bool $setupQueuesAutomatically = true)
    {
        $this->adapter = new $adapterClass($transports);
        $this->setupQueuesAutomatically = $setupQueuesAutomatically;
    }

    /**
     * @param int    $countExpected
     * @param string $transport
     *
     * @throws \Exception
     *
     * @Given I have :count messages in amqp :transport queue
     */
    public function iHaveMessagesInAmqpQueue(int $countExpected, string $transport): void
    {
        $count = $this->adapter->countMessagesInTransport($transport);

        if ($count !== $countExpected) {
            throw new \Exception(sprintf('There is %d message(s) in the queue at this moment.', $count));
        }
    }

    /**
     * @BeforeScenario @amqp
     */
    public function setupQueuesAutomatically(): void
    {
        if ($this->setupQueuesAutomatically) {
            $this->adapter->setupQueues();
        }
    }

    /**
     * @BeforeScenario @amqp
     *
     * @Given I clear messages in all amqp queues
     */
    public function iClearMessagesInAllAmqpTransports(): void
    {
        $this->adapter->purgeAllTransports();
    }

    /**
     * @param string $transport
     *
     * @Given I clear messages in amqp :transport queue
     */
    public function iClearMessagesInAmqpTransport(string $transport): void
    {
        $this->adapter->purgeTransport($transport);
    }

    /**
     * @param string       $transport
     * @param string       $command
     * @param PyStringNode $string
     *
     * @Given I publish in amqp queue :transport message :command with content:
     */
    public function iPublishInAmqpQueueMessageWithContent(string $transport, string $command, PyStringNode $string): void
    {
        $this->adapter->publish($transport, $string, $command);
    }

    /**
     * @param string       $transport
     * @param PyStringNode $string
     *
     * @throws \Exception
     *
     * @Then I acknowledge the content of next message in amqp queue :transport and its content is:
     */
    public function iAcknowledgeTheContentOfTheNextMessageInAmqpTransportAndItsContentIs(string $transport, PyStringNode $string): void
    {
        $messageContent = $this->adapter->acknowledgeAndGetNextMessageInTransport($transport);

        if (json_decode($messageContent) != json_decode($string)) {
            throw new \Exception(sprintf('Retrieved message %s from the queue %s is not equal to expected message %s', $messageContent, $transport, $string));
        }
    }
}
