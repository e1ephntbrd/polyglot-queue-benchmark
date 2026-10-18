<?php

namespace app\src\Infrastructure\Seed;

use app\models\Users;
use app\models\UserActivities;
use app\src\Shared\Config\AppSettings;
use Faker\Factory as FakerFactory;

/**
 * Database seeder for SQL tables.
 *
 * Seeds the `users` and `user_activities` tables with synthetic data.
 * Uses chunked inserts and generators to avoid memory exhaustion.
 */
class DbSeeder extends SeederAbstract
{
    /**
     * Run the SQL seeder.
     *
     * @return bool True on success
     *
     * @throws \yii\db\Exception
     */
    public function run(): bool
    {
        $this->seedUsers();
        $this->seedUserActivities();
        return true;
    }

    /**
     * Seed the `users` table with fake emails and active flags.
     *
     * @return void
     *
     * @throws \yii\db\Exception
     */
    private function seedUsers(): void
    {
        $faker = FakerFactory::create();
        $totalUsers = $this->context->getUsersCount();
        $activeUsers = $this->context->getActiveUsersCount();

        $chunk = [];
        for ($i = 0; $i < $totalUsers; $i++) {
            $chunk[] = [
                $faker->unique()->safeEmail(),
                $i < $activeUsers,
            ];

            if (count($chunk) >= 1000) {
                Users::find()->createCommand()
                    ->batchInsert(Users::TABLE_NAME, [Users::EMAIL_COLUMN, Users::IS_ACTIVE_COLUMN], $chunk)
                    ->execute();
                $chunk = [];
            }
        }

        if ($chunk) {
            Users::find()->createCommand()
                ->batchInsert(Users::TABLE_NAME, [Users::EMAIL_COLUMN, Users::IS_ACTIVE_COLUMN], $chunk)
                ->execute();
        }
    }

    /**
     * Seed the `user_activities` table with random activity events.
     *
     * @return void
     *
     * @throws \yii\db\Exception
     */
    private function seedUserActivities(): void
    {
        $columns = [
            UserActivities::USER_ID_COLUMN,
            UserActivities::ACTIVITY_TYPE_COLUMN,
            UserActivities::ACTIVITY_DATA_COLUMN,
            UserActivities::CREATED_AT_COLUMN
        ];

        $activityTypes = AppSettings::get('campaign.promoActions');
        $hoursWindow   = (int) AppSettings::get('campaign.hoursWindow');

        $chunk = [];
        foreach (Users::find()->each(1000) as $u) {
            if ($this->shouldGenerateActivities((bool)$u[Users::IS_ACTIVE_COLUMN])) {
                foreach ($this->generateActivitiesForUser((int)$u['id'], $activityTypes, $hoursWindow) as $activity) {
                    $chunk[] = $activity;
                    if (count($chunk) >= 1000) {
                        UserActivities::find()->createCommand()
                            ->batchInsert(UserActivities::TABLE_NAME, $columns, $chunk)
                            ->execute();
                        $chunk = [];
                    }
                }
            }
        }

        if ($chunk) {
            UserActivities::find()->createCommand()
                ->batchInsert(UserActivities::TABLE_NAME, $columns, $chunk)
                ->execute();
        }
    }

    /**
     * Probability check for generating activities.
     *
     * @param bool $isActive Whether the user is active
     * @return bool True if activities should be generated
     */
    private function shouldGenerateActivities(bool $isActive): bool
    {
        $probability = $isActive ? 30 : mt_rand(2, 8);
        return mt_rand(1, 100) <= $probability;
    }

    /**
     * Generate random activities for a single user.
     *
     * @param int $userId User ID
     * @param string[] $activityTypes List of possible activity types
     * @param int $hoursWindow Time window in hours
     * @return \Generator<int, array{int,string,string,string}>
     */
    private function generateActivitiesForUser(int $userId, array $activityTypes, int $hoursWindow): \Generator
    {
        $numActivities = mt_rand(0, 5);
        for ($i = 0; $i < $numActivities; $i++) {
            $type = $activityTypes[array_rand($activityTypes)];
            $when = (new \DateTimeImmutable())->modify('-' . mt_rand(0, $hoursWindow - 1) . ' hours');
            $w1   = (new \DateTimeImmutable())->modify('-' . mt_rand(0, $userId - 1) . ' hours');

            yield [
                $userId,
                $type,
                json_encode(['kl' => $userId * 0.157 . ' sec', 'tt' => $w1->format('d:Hi')]),
                $when->format('Y-m-d H:i:s'),
            ];
        }
    }
}
