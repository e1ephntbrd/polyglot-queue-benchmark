<?php

namespace app\src\Shared\Config;

use app\models\Config;

/**
 * Application settings helper.
 *
 * Provides methods to retrieve, update, and list configuration
 * values from both Yii application parameters and the Config model.
 */
class AppSettings
{
    /**
     * Get a configuration value by path.
     *
     * Traverses Yii application parameters using dot notation
     * and falls back to the Config model if not found.
     *
     * @param string $path Dot-notated configuration path (e.g., "campaign.queue")
     *
     * @return mixed The configuration value or null if not found
     */
    public static function get(string $path)
    {
        $current = \Yii::$app->params;

        foreach (explode('.', $path) as $node) {
            if (isset($current[$node])) {
                $current = $current[$node];
                continue;
            }
            $current = null;
            break;
        }

        return Config::get($path, $current);
    }

    /**
     * Add or update a configuration value.
     *
     * Stores the given value in the Config model.
     *
     * @param string $path  Dot-notated configuration path
     * @param mixed  $value Value to store
     *
     * @return bool True if the configuration was saved successfully
     */
    public static function set(string $path, $value): bool
    {
        return Config::add($path, $value);
    }

    /**
     * Get all configuration values.
     *
     * Combines Yii application parameters with values
     * stored in the Config model.
     *
     * @return array Associative array of configuration values
     */
    public static function all(): array
    {
        $file   = [];
        $params = \Yii::$app->params;

        foreach ($params as $scope => $scopeConfig) {
            foreach ($scopeConfig as $field => $value) {
                $file[$scope . '.' . $field] = $value;
            }
        }

        return array_merge($file, Config::all());
    }
}
