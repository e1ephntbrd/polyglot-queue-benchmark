<?php

namespace app\src\Infrastructure\Seed;

/**
 * Interface for seeders.
 *
 * Defines the contract for seeder classes that generate
 * and insert data into the application during the seeding process.
 */
interface SeederInterface
{
    /**
     * Run the seeder.
     *
     * Executes the seeding logic, such as generating
     * and inserting data into the database.
     *
     * @return bool True if seeding was successful, false otherwise
     */
    public function run(): bool;

    /**
     * Set the seeding context.
     *
     * Provides configuration values (e.g., user counts, inactive percentages)
     * to the seeder before execution.
     *
     * @param ContextInterface $context The seeding context instance
     * @return SeederInterface Fluent interface
     */
    public function setContext(ContextInterface $context): SeederInterface;
}
