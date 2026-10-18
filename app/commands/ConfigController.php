<?php

namespace app\commands;

use app\src\Shared\Config\AppSettings;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console controller for managing application configuration.
 *
 * Provides commands to display and update configuration values
 * stored in the AppSettings component.
 */
class ConfigController extends Controller
{
    /**
     * Show all configuration settings.
     *
     * Iterates through all key-value pairs in AppSettings
     * and prints them to the console.
     *
     * @return int Exit code (0 if successful)
     */
    public function actionShow(): int
    {
        $all = AppSettings::all();
        foreach ($all as $key => $value) {
            $val = is_array($value) ? json_encode($value) : (string)$value;
            echo $key . " = " . $val . PHP_EOL;
        }
        return ExitCode::OK;
    }

    /**
     * Set a configuration value.
     *
     * Updates the AppSettings storage with the given path and value.
     *
     * @param string $path   Configuration key path
     * @param string $value  New value to assign
     *
     * @return int Exit code (0 if successful)
     */
    public function actionSet(string $path, string $value): int
    {
        AppSettings::set($path, $value);
        echo "Set {$path} = {$value}" . PHP_EOL;
        return ExitCode::OK;
    }
}
