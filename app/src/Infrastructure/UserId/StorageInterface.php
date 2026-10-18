<?php

namespace app\src\Infrastructure\UserId;

/**
 * Interface for user ID storage.
 *
 * Defines the contract for accumulating user IDs
 * and releasing them in batches for further processing.
 */
interface StorageInterface
{
    /**
     * Accumulate a user ID.
     *
     * Adds the given user ID to the storage for later release.
     *
     * @param int $userId The user ID to accumulate
     * @return void
     */
    public function accumulate(int $userId): void;

    /**
     * Release accumulated user IDs.
     *
     * Invokes the provided callback with batches of user IDs
     * or message bodies prepared for publishing.
     *
     * @param callable $callback Callback function to process released user IDs
     * @return void
     */
    public function release(callable $callback): void;
}
