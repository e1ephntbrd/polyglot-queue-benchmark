<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Revolt\EventLoop;
use Amp\Mysql\ConnectionConfig;
use function Amp\Mysql\pool;
use function Amp\Socket\connect as socketConnect;
use function Amp\Promise\wait;

if (extension_loaded('pcntl')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, function () {
        echo "\n[*] Shutting down gracefully...\n";
        exit(0);
    });
}

$mysqlHost = getenv('MYSQL_HOST') ?: 'mysql';
$mysqlPort = (int)(getenv('MYSQL_PORT') ?: 3306);
$mysqlUser = getenv('MYSQL_USER') ?: 'root';
$mysqlPass = getenv('MYSQL_PASSWORD') ?: 'root';
$mysqlDb   = getenv('MYSQL_DB') ?: 'app';

$mongoUri    = getenv('MONGO_URI') ?: "mongodb://mongodb:27017";
$rabbitUrl   = getenv('RABBIT_URL') ?: "amqp://guest:guest@rabbitmq:5672/";
$mailhogHost = getenv('MAILHOG_HOST') ?: 'mailhog';
$mailhogPort = (int)(getenv('MAILHOG_PORT') ?: 1025);

function sendSingleEmailAsync(string $emailAddr, string $host, int $port): \Amp\Promise
{
    return \Amp\call(function () use ($emailAddr, $host, $port) {
        $socketUri = "tcp://{$host}:{$port}";
        try {
            /** @var \Amp\Socket\EncryptableSocket $socket */
            $socket = yield socketConnect($socketUri);

            $emailRaw = "From: campaign@benchmark.local\r\n" .
                "To: {$emailAddr}\r\n" .
                "Subject: Email Campaign Benchmark (PHP Fiber)\r\n\r\n" .
                "This is a test campaign email sent by PHP Fiber Worker.\r\n.\r\n";

            yield $socket->read(); // 220
            yield $socket->write("EHLO benchmark\r\n"); yield $socket->read();
            yield $socket->write("MAIL FROM:<campaign@benchmark.local>\r\n"); yield $socket->read();
            yield $socket->write("RCPT TO:<{$emailAddr}>\r\n"); yield $socket->read();
            yield $socket->write("DATA\r\n"); yield $socket->read();
            yield $socket->write($emailRaw); yield $socket->read();
            yield $socket->write("QUIT\r\n"); yield $socket->read();

            $socket->close();
        } catch (\Throwable $e) {
            echo "[MAIL ERROR] Failed to send to {$emailAddr}: " . $e->getMessage() . "\n";
        }
    });
}

EventLoop::queue(function () use ($mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass, $mysqlDb, $mongoUri, $rabbitUrl, $mailhogHost, $mailhogPort) {
    echo "[INIT] Initializing MySQL pool...\n";

    try {
        $connectionConfig = ConnectionConfig::fromString(
            "host={$mysqlHost};port={$mysqlPort};user={$mysqlUser};password={$mysqlPass};db={$mysqlDb}"
        );
        $dbPool = pool($connectionConfig);

        wait(\Amp\call(function() use ($dbPool) {
            /** @var \Amp\Sql\Statement $stmt */
            $stmt = yield $dbPool->query("SELECT 1");
            yield $stmt->advance();
            echo "[INIT] MySQL pool connected successfully!\n";
        }));
    } catch (\Throwable $e) {
        echo "[FATAL ERROR] MySQL Connection failed: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        exit(1);
    }

    // MongoDB
    try {
        $mongoClient     = new MongoDB\Client($mongoUri);
        $mongoCollection = $mongoClient->selectDatabase('app')->selectCollection('benchmark_metrics');
        echo "[INIT] MongoDB connected successfully.\n";
    } catch (\Throwable $e) {
        echo "[ERROR] MongoDB setup failed: " . $e->getMessage() . "\n";
    }

    // RabbitMQ
    try {
        $urlParts   = parse_url($rabbitUrl);
        $connection = new AMQPStreamConnection(
            $urlParts['host'],
            $urlParts['port'] ?? 5672,
            $urlParts['user'] ?? 'guest',
            $urlParts['pass'] ?? 'guest'
        );
        $channel = $connection->channel();
        $channel->queue_declare('email_campaign_queue', false, true, false, false);
        $channel->basic_qos(null, 100, null);
        echo "[INIT] RabbitMQ connected successfully.\n";
    } catch (\Throwable $e) {
        echo "[FATAL ERROR] RabbitMQ connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }

    echo " [*] PHP Native Fiber Worker ready and listening for messages.\n";

    $callback = function ($msg) use ($dbPool, $mongoCollection, $channel, $mailhogHost, $mailhogPort) {
        EventLoop::queue(function () use ($msg, $dbPool, $mongoCollection, $channel, $mailhogHost, $mailhogPort) {
            $startTime   = microtime(true);
            $messageId   = "msg_" . hrtime(true);
            $deliveryTag = $msg->delivery_tag ?? null;
            $status      = "success";

            try {
                $body    = json_decode($msg->body, true);
                $userIds = [];

                if (is_array($body) && array_key_exists(0, $body)) {
                    $userIds = array_map('intval', $body);
                } elseif (is_array($body) && isset($body['ids'])) {
                    $userIds = array_map('intval', $body['ids']);
                }

                echo "[WORKER] Processing msg {$messageId} with " . count($userIds) . " user IDs...\n";

                if (!empty($userIds)) {
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));

                    // Обгортаємо запит в Amp\Promise\wait, щоб результати вичікувалися коректно у файбері
                    $emails = wait(\Amp\call(function() use ($dbPool, $placeholders, $userIds) {
                        /** @var \Amp\Sql\Statement $statement */
                        $statement = yield $dbPool->prepare("SELECT email FROM users WHERE id IN ($placeholders) AND is_active = 1");

                        /** @var \Amp\Sql\ResultSet $result */
                        $result = yield $statement->execute($userIds);

                        $fetched = [];
                        while (yield $result->advance()) {
                            $row = $result->getCurrent();
                            $fetched[] = $row['email'];
                        }
                        return $fetched;
                    }));

                    echo "[MYSQL] Found " . count($emails) . " emails for msg {$messageId}\n";

                    if (!empty($emails)) {
                        foreach ($emails as $emailAddr) {
                            wait(sendSingleEmailAsync($emailAddr, $mailhogHost, $mailhogPort));
                        }
                    }
                }

                if ($deliveryTag !== null) {
                    $channel->basic_ack($deliveryTag);
                    echo "[ACK] Msg {$messageId} processed successfully.\n";
                }
            } catch (\Throwable $e) {
                $status = "failed";
                echo "[WORKER ERROR] Msg {$messageId} failed: " . $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";
                if ($deliveryTag !== null) {
                    $channel->basic_nack($deliveryTag, false, true);
                }
            }

            try {
                $mongoCollection->insertOne([
                    "worker"      => "php-fiber-worker",
                    "message_id"  => $messageId,
                    "status"      => $status,
                    "duration_ms" => (microtime(true) - $startTime) * 1000.0,
                    "timestamp"   => new \MongoDB\BSON\UTCDateTime()
                ]);
            } catch (\Throwable $ex) {
                echo "[MONGO ERROR] " . $ex->getMessage() . "\n";
            }
        });
    };

    $channel->basic_consume('email_campaign_queue', '', false, false, false, false, $callback);

    $socket = $connection->getIO()->getSocket();
    EventLoop::onReadable($socket, function () use ($channel) {
        try {
            $channel->wait(null, true);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {}
    });
});

EventLoop::run();
