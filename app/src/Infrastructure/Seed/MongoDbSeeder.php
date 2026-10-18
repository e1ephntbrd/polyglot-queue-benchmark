<?php

namespace app\src\Infrastructure\Seed;

use app\models\Users;
use app\src\Shared\Config\AppSettings;
use MongoDB\BSON\UTCDateTime;

/**
 * Seeder for MongoDB user activity logs.
 *
 * Generates synthetic user activity events for both active and inactive users.
 * Ensures that 20–40% of users have at least 3 "view_product" actions.
 * Inserts data into the "user_activity_logs" collection in batches.
 */
class MongoDbSeeder extends SeederAbstract
{
    /** @var string[] List of possible activity types */
    private const ACTIVITY_TYPES = ['view_product', 'search', 'add_to_cart', 'purchase'];

    /**
     * Run the MongoDB seeder.
     *
     * @return bool True on success
     */
    public function run(): bool
    {
        $mongodb     = \Yii::$app->mongodb;
        $hoursWindow = (int) AppSettings::get('campaign.hoursWindow');
        $collection  = $mongodb->getCollection('user_activity_logs');

        foreach (Users::find()->each(1000) as $u) {
            $isActive    = (bool)$u[Users::IS_ACTIVE_COLUMN];
            $probability = $isActive ? 25 : mt_rand(3, 7);

            if (mt_rand(1, 100) <= $probability) {
                $actions = $this->generateActions($hoursWindow, (int)$u['id']);
                if ($actions) {
                    $collection->batchInsert($actions);
                }
            }
        }

        return true;
    }

    /**
     * Generate a random set of actions for a user.
     * Ensures 20–40% of users have >=3 "view_product" actions.
     *
     * @param int $hoursWindow Time window in hours
     * @param int $userId User ID
     * @return array<int, array{user_id:int,action:string,created_at:UTCDateTime}>
     */
    private function generateActions(int $hoursWindow, int $userId): array
    {
        $actions = [];
        $numActions = mt_rand(1, 6);

        if (mt_rand(1, 100) <= mt_rand(20, 40)) {
            $viewCount = mt_rand(3, 6);
            for ($i = 0; $i < $viewCount; $i++) {
                $actions[] = $this->makeAction('view_product', $hoursWindow, $userId);
            }
        }

        for ($i = 0; $i < $numActions; $i++) {
            $type = self::ACTIVITY_TYPES[array_rand(self::ACTIVITY_TYPES)];
            $actions[] = $this->makeAction($type, $hoursWindow, $userId);
        }

        return $actions;
    }

    /**
     * Create a single action record.
     *
     * @param string $type Activity type
     * @param int $hoursWindow Time window in hours
     * @param int $userId User ID
     * @return array{user_id:int,action:string,created_at:UTCDateTime}
     */
    private function makeAction(string $type, int $hoursWindow, int $userId): array
    {
        $ts = (new \DateTimeImmutable())->modify('-' . mt_rand(0, $hoursWindow - 1) . ' hours');
        return [
            'user_id'    => $userId,
            'action'     => $type,
            'created_at' => new UTCDateTime($ts->getTimestamp() * 1000),
        ];
    }
}
