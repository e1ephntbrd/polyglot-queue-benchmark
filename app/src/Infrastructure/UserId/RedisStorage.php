<?php

namespace app\src\Infrastructure\UserId;

use app\src\Shared\Config\AppSettings;
use yii\redis\Connection;

/**
 * Redis-based storage for user IDs.
 *
 * Accumulates user IDs in a Redis set and releases them
 * in batches using SSCAN for efficient iteration.
 */
class RedisStorage implements StorageInterface
{
    /** @var string Redis key for storing eligible user IDs */
    private const INDEX_KEY = 'eligible_users';

    /**
     * @param Connection $connection Redis connection instance
     */
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Accumulate a user ID.
     *
     * Adds the given user ID to the Redis set.
     *
     * @param int $userId The user ID to accumulate
     * @return void
     */
    public function accumulate(int $userId): void
    {
        $this->connection->sadd(self::INDEX_KEY, $userId);
    }

    /**
     * Release accumulated user IDs in batches.
     *
     * Iterates over the Redis set using SSCAN with the configured batch size,
     * and invokes the provided callback with each batch of IDs.
     * After releasing all IDs, the Redis set is deleted.
     *
     * @param callable $callback Callback function to process batches of user IDs
     * @return void
     */
    public function release(callable $callback): void
    {
        $cursor = 0;
        $count  = (int) AppSettings::get('campaign.batchSize');

        do {
            [$cursor, $ids] = $this->connection->sscan(self::INDEX_KEY, $cursor, 'COUNT', $count);
            $callback($ids);
        } while ($cursor != 0);

        $this->connection->del(self::INDEX_KEY);
    }
}
