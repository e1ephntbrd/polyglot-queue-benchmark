<?php

namespace app\src\Infrastructure\UserId;

use app\src\Shared\Config\AppSettings;
use yii\mongodb\Connection;

/**
 * MongoDB source for user IDs.
 *
 * Streams user IDs from the `user_activity_logs` collection
 * based on product view activity within a configured time window.
 * Only users who meet the minimum view threshold are returned.
 */
class MongoDbSource implements SourceInterface
{
    /**
     * Stream user IDs from MongoDB.
     *
     * Builds an aggregation pipeline to:
     * - Match product view actions within the configured time window.
     * - Group by user ID and count views.
     * - Filter users who meet the minimum view threshold.
     * - Sort results by user ID.
     *
     * Each matching user ID is passed to the provided callback.
     *
     * @param callable $callback Callback function to process each user ID
     *
     * @return void
     *
     * @throws \MongoDB\Driver\Exception\Exception
     * @throws \yii\mongodb\Exception
     */
    public function stream(callable $callback): void
    {
        $views       = (int) AppSettings::get('campaign.minViews');
        $hoursWindow = (int) AppSettings::get('campaign.hoursWindow');
        $since       = new \MongoDB\BSON\UTCDateTime((time() - $hoursWindow * 3600) * 1000);

        $pipeline = [
            ['$match' => [
                'action'     => 'view_product',
                'created_at' => ['$gte' => $since],
            ]],
            ['$group' => [
                '_id'   => '$user_id',
                'views' => ['$sum' => 1],
            ]],
            ['$match' => ['views' => ['$gte' => $views]]],
            ['$sort'  => ['_id' => 1]],
        ];

        /** @var Connection $mongodb */
        $mongodb = \Yii::$app->mongodb;
        $mongodb->open();
        $command = new \MongoDB\Driver\Command([
            'aggregate' => 'user_activity_logs',
            'pipeline'  => $pipeline,
            'cursor'    => ['batchSize' => 5000],
        ]);
        $cursor = $mongodb->manager
            ->executeCommand($mongodb->defaultDatabaseName, $command);

        foreach ($cursor as $doc) {
            $callback((int) $doc->_id);
        }
    }
}
