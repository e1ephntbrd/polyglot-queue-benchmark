<?php

namespace app\commands;

use app\src\Infrastructure\Seed\Context;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console controller for seeding application data.
 *
 * Provides commands to populate the database with test users
 * and activity logs using the SeedManager component.
 */
class SeedController extends Controller
{
    /** @var string Default action name */
    public $defaultAction = 'run';

    /**
     * Define available command-line options.
     *
     * @param string $actionID The action identifier
     * @return string[] List of available options
     */
    public function options($actionID): array
    {
        return ['users', 'inactive'];
    }

    /**
     * Define option aliases for command-line usage.
     *
     * @return array<string,string> Aliases mapped to option names
     */
    public function optionAliases(): array
    {
        return ['u' => 'users', 'i' => 'inactive'];
    }

    /**
     * Run the seeding process.
     *
     * Creates a new seeding context with the specified number of users
     * and inactive percentage, then executes all seeders via SeedManager.
     *
     * @param int $users    Number of users to generate (default: 10,000)
     * @param int $inactive Percentage of inactive users (default: 10)
     *
     * @return int Exit code (0 if successful)
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRun(int $users = 10000, int $inactive = 10): int
    {
        /** @var \app\src\Infrastructure\SeedManager $seedManager */
        $seedManager = \Yii::$container->get(\app\src\Infrastructure\SeedManager::class);

        $seedManager
            ->setContext(new Context($users, $inactive))
            ->runAll();

        echo "Seed done.\n";
        return ExitCode::OK;
    }
}
