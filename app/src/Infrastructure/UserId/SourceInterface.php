<?php

namespace app\src\Infrastructure\UserId;

/**
 * Interface for user ID sources.
 *
 * Defines the contract for streaming user IDs from different
 * data sources (e.g., MySQL, MongoDB) into the system.
 */
interface SourceInterface
{
    /**
     * Stream user IDs from the source.
     *
     * Iterates through the source data and invokes the provided
     * callback function for each user ID found.
     *
     * @param callable $callback Func to process each user ID (literally "accumulate to a storage before release")
     * @return void
     */
    public function stream(callable $callback): void;
}
