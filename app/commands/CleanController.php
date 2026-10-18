<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console controller for cleaning application data.
 *
 * This command truncates selected SQL tables and removes
 * documents from MongoDB collections to reset the environment.
 */
class CleanController extends Controller
{
    /** @var string Default action name */
    public $defaultAction = 'run';

    /**
     * Run the cleaning process.
     *
     * - Disables foreign key checks.
     * - Truncates specific SQL tables if they exist.
     * - Re-enables foreign key checks.
     * - Removes all documents from the MongoDB `user_activity_logs` collection.
     *
     * @return int Exit code (0 if successful)
     * @throws \yii\db\Exception
     */
    public function actionRun(): int
    {
        // Disable foreign key checks
        \Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS=0")->execute();

        // Truncate selected tables if they exist
        foreach (['user_activities', 'users', 'config'] as $table) {
            if (\Yii::$app->db->schema->getTableSchema($table, true)) {
                \Yii::$app->db->createCommand()->truncateTable($table)->execute();
            }
        }

        // Re-enable foreign key checks
        \Yii::$app->db->createCommand("SET FOREIGN_KEY_CHECKS=1")->execute();

        // Clear MongoDB collection
        \Yii::$app->mongodb->getCollection('user_activity_logs')->remove([]);

        echo "Clean completed.\n";
        return ExitCode::OK;
    }
}
