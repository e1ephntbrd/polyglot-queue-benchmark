<?php

namespace app\models\dto;

/**
 * Value object representing the result of a batch query
 * from UserActivities.
 *
 * @property-read int[] $userIds List of distinct user IDs
 * @property-read int   $lastId  Cursor for key-pagination
 */
readonly class BatchOutput
{
    /**
     * @param int[] $userIds
     * @param int   $lastId
     */
    public function __construct(
        public array $userIds,
        public int $lastId
    ) {}
}
