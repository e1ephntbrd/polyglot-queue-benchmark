<?php

namespace app\src\Infrastructure\Seed;

/**
 * Abstract base class for seeders.
 *
 * Provides a common implementation for setting the seeding context
 * and defines the contract for running a seeder.
 */
abstract class SeederAbstract implements SeederInterface
{
    /** @var ContextInterface The seeding context configuration */
    protected ContextInterface $context;

    /**
     * Run the seeder.
     *
     * Must be implemented by concrete seeders to perform
     * data generation and insertion logic.
     *
     * @return bool True if seeding was successful, false otherwise
     */
    abstract public function run(): bool;

    /**
     * Set the seeding context.
     *
     * @param ContextInterface $context The seeding context instance
     * @return SeederInterface Fluent interface
     */
    public function setContext(ContextInterface $context): SeederInterface
    {
        $this->context = $context;
        return $this;
    }
}
