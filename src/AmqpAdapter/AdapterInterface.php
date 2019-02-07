<?php

namespace WakeOnWeb\BehatContexts\AmqpAdapter;

/**
 * Interface AdapterInterface
 *
 * @author Stephane PY <s.py@wakeonweb.com>
 */
interface AdapterInterface
{
    /**
     * @param string $transport transport
     * @param string $content   content
     * @param string $command   command
     *
     * @return void
     */
    public function publish(string $transport, string $content, string $command = null): void;

    /**
     * @param string $transport transport
     *
     * @return int
     */
    public function countMessagesInTransport(string $transport): int;

    /**
     * @param string $transport
     *
     * @return string
     */
    public function acknowledgeAndGetNextMessageInTransport(string $transport): ?string;

    /**
     * @return void
     */
    public function purgeAllTransports(): void;

    /**
     * @param string $transport transport
     *
     * @return void
     */
    public function purgeTransport(string $transport): void;

    /**
     * @return void
     */
    public function setupQueues(): void;
}
