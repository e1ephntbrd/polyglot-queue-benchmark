<?php

namespace app\src\Infrastructure\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * RabbitMQ worker for consuming messages from a queue.
 *
 * Encapsulates the logic for declaring a queue, setting QoS,
 * consuming messages with a callback, and managing the lifecycle
 * of the channel and connection.
 */
class Worker
{
    /** @var AMQPChannel The AMQP channel used for consuming messages */
    private AMQPChannel $channel;

    /**
     * @param AMQPStreamConnection $connection The AMQP connection instance
     */
    public function __construct(
        private readonly AMQPStreamConnection $connection
    ) {
        $this->channel = $connection->channel();
    }

    /**
     * Start listening to a queue.
     *
     * Declares the queue, sets QoS (prefetch count = 20),
     * and begins consuming messages using the provided callback.
     * The loop continues while the channel is consuming.
     *
     * @param string   $queue    The queue name
     * @param callable $callback The callback to handle incoming messages
     * @return void
     */
    public function listen(string $queue, callable $callback): void
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->basic_qos(null, 20, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * Close the channel and connection.
     *
     * Ensures resources are properly released.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Automatically closes the channel and connection
     * when the object is destroyed.
     *
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->close();
    }
}
