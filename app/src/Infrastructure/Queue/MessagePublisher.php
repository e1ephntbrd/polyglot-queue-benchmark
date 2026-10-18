<?php

namespace app\src\Infrastructure\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publishes messages to a RabbitMQ queue.
 *
 * Encapsulates the logic for creating AMQP messages,
 * publishing them to the specified routing key, and
 * managing the lifecycle of the channel and connection.
 */
class MessagePublisher
{
    /** @var AMQPChannel The AMQP channel used for publishing */
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
     * Publish a message to the queue.
     *
     * Encodes the payload as JSON and publishes it
     * to the given routing key with persistent delivery mode.
     *
     * @param string $routingKey The routing key (queue name)
     * @param array  $payload    The message payload
     *
     * @return void
     *
     * @throws \JsonException
     */
    public function publish(string $routingKey, array $payload): void
    {
        $msg = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $this->channel->basic_publish($msg, '', $routingKey);
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
