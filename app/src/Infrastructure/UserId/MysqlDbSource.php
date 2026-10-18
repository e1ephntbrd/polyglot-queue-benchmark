<?php

namespace app\src\Infrastructure\UserId;

use app\models\UserActivities;
use app\src\Shared\Config\AppSettings;

/**
 * MySQL source for user IDs.
 *
 * Streams user IDs from the `user_activities` table
 * based on product view activity within a configured time window.
 */
class MysqlDbSource implements SourceInterface
{
    /**
     * Stream user IDs from MySQL.
     *
     * Builds a query to:
     * - Select distinct user IDs from the `user_activities` table.
     * - Filter by activity timestamp within the configured time window.
     * - Pass each matching user ID to the provided callback.
     *
     * @param callable $callback Callback function to process each user ID
     *
     * @return void
     *
     * @throws \yii\db\Exception
     */
    public function stream(callable $callback): void
    {
        $hoursWindow = (int) AppSettings::get('campaign.hoursWindow');

        $command = UserActivities::find()
            ->select(UserActivities::USER_ID_COLUMN)
            ->distinct()
            ->where([
                '>=',
                UserActivities::CREATED_AT_COLUMN,
                date('Y-m-d H:i:s', time() - $hoursWindow * 3600)
            ])
            ->createCommand();

        $dataReader = $command->query();

        foreach ($dataReader as $row) {
            $callback((int) $row['user_id']);
        }
    }
}
