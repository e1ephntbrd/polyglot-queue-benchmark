<?php

namespace app\src\Infrastructure\Seed;

/**
 * Interface for seeding context configuration.
 *
 * Defines methods for managing user counts and inactive user percentages
 * during the seeding process.
 */
interface ContextInterface
{
    /**
     * Set the total number of users to generate.
     *
     * @param int $count Number of users
     * @return ContextInterface Fluent interface
     */
    public function setUsersCount(int $count): ContextInterface;

    /**
     * Get the total number of users to generate.
     *
     * @return int Number of users
     */
    public function getUsersCount(): int;

    /**
     * Get the number of active users to generate.
     *
     * @return int Number of active users
     */
    public function getActiveUsersCount(): int;

    /**
     * Get the number of inactive users to generate.
     *
     * @return int Number of inactive users
     */
    public function getInactiveUsersCount(): int;

    /**
     * Set the percentage of inactive users.
     *
     * @param int $percent Percentage of inactive users
     * @return ContextInterface Fluent interface
     */
    public function setInactiveUsersPercent(int $percent): ContextInterface;

    /**
     * Get the percentage of inactive users.
     *
     * @return int Percentage of inactive users
     */
    public function getInactiveUsersPercent(): int;
}
