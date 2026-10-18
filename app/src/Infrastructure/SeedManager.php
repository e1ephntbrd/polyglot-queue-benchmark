<?php

namespace app\src\Infrastructure;

use app\src\Infrastructure\Seed\Context;
use app\src\Infrastructure\Seed\SeederInterface;

/**
 * Manages execution of seeders.
 *
 * Provides methods to run all seeders or a specific seeder
 * with the configured context.
 */
class SeedManager
{
    /**
     * @param Context            $context The seeding context configuration
     * @param SeederInterface[]  $seeds   Array of seeders indexed by key
     */
    public function __construct(
        private Context $context,
        private readonly array $seeds = []
    ) {
    }

    /**
     * Run all seeders.
     *
     * Iterates through all configured seeders, sets the context,
     * and executes their `run()` method.
     *
     * @return void
     */
    public function runAll(): void
    {
        foreach ($this->seeds as $seed) {
            $seed->setContext($this->context)->run();
        }
    }

    /**
     * Run a specific seeder by key
     *
     * If the seeder exists in the array, sets the context
     * and executes its `run()` method.
     *
     * @param string $key The seeder key
     * @return void
     */
    public function run(string $key): void
    {
        if (isset($this->seeds[$key])) {
            $this->seeds[$key]
                ->setContext($this->context)
                ->run();
        }
    }

    /**
     * Set a new seeding context
     *
     * Allows updating the context before running seeders
     *
     * @param Context $context The new seeding context
     * @return static Fluent interface
     */
    public function setContext(Context $context): static
    {
        $this->context = $context;
        return $this;
    }
}
