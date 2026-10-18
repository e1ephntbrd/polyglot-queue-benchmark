<?php

namespace app\src\Infrastructure\Queue\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Factory for creating RabbitMQ connections.
 *
 * Provides a simple method to instantiate an AMQPStreamConnection
 * with the given connection parameters.
 */
class ConnectionFactory
{
    /**
     * Create a new RabbitMQ connection.
     *
     * @param string $host RabbitMQ server hostname
     * @param int    $port RabbitMQ server port
     * @param string $user Username for authentication
     * @param string $pass Password for authentication
     *
     * @return AMQPStreamConnection The established AMQP connection
     *
     * @throws \Exception
     */
    public function create(string $host, int $port, string $user, string $pass): AMQPStreamConnection
    {
        return new AMQPStreamConnection($host, $port, $user, $pass);
    }
}
