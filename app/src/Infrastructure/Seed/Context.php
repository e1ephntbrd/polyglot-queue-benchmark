<?php

namespace app\src\Infrastructure\Seed;

/**
 * Seed Context
 *
 * Implementation of ContextInterface for seeding operations.
 * This class stores information about users count and inactive users percentage,
 * and provides helper methods to calculate active/inactive users.
 */
class Context implements ContextInterface
{
    /**
     * @param int $usersCount Total count of generated users
     * @param int $inactiveUsersPercent Percent of inactive users
     */
    public function __construct(
        private int $usersCount = 1000,
        private int $inactiveUsersPercent = 7
    ) {
    }

    /**
     * Set the total number of users.
     *
     * @param int $count Number of users
     * @return ContextInterface Fluent interface
     */
    public function setUsersCount(int $count): ContextInterface
    {
        $this->usersCount = $count;
        return $this;
    }

    /**
     * Get the total number of users.
     *
     * @return int Number of users
     */
    public function getUsersCount(): int
    {
        return $this->usersCount;
    }

    /**
     * Get the number of active users.
     *
     * @return int Active users count
     */
    public function getActiveUsersCount(): int
    {
        $inactive = $this->getInactiveUsersCount();
        return max(0, $this->usersCount - $inactive);
    }

    /**
     * Get the number of inactive users.
     *
     * @return int Inactive users count
     */
    public function getInactiveUsersCount(): int
    {
        return (int)floor($this->usersCount * $this->inactiveUsersPercent / 100);
    }

    /**
     * Set the percentage of inactive users.
     *
     * @param int $percent Percentage (0-100)
     * @return ContextInterface Fluent interface
     */
    public function setInactiveUsersPercent(int $percent): ContextInterface
    {
        $this->inactiveUsersPercent = $percent;
        return $this;
    }

    /**
     * Get the percentage of inactive users.
     *
     * @return int Percentage (0-100)
     */
    public function getInactiveUsersPercent(): int
    {
        return $this->inactiveUsersPercent;
    }
}
