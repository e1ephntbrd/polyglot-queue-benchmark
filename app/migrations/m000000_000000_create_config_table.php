<?php

namespace app\migrations;

use app\models\Config;
use app\models\UserActivities;
use app\models\Users;
use yii\db\Migration;

class m000000_000000_create_config_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema;
        if ($schema->getTableSchema(Config::TABLE_NAME, true) === null) {
            $this->createTable(Config::TABLE_NAME, [
                Config::ID_COLUMN => $this->primaryKey(),
                Config::PATH_COLUMN => $this->string(191)->notNull()->unique(),
                Config::VALUE_COLUMN => $this->text()->notNull(),
                Config::CREATED_AT_COLUMN => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
                Config::UPDATE_AT_COLUMN => $this->dateTime()
                    ->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);
        }
        if ($schema->getTableSchema(Users::TABLE_NAME, true) === null) {
            $this->createTable(Users::TABLE_NAME, [
                Users::ID_COLUMN => $this->bigPrimaryKey(),
                Users::EMAIL_COLUMN => $this->string(191)->notNull()->unique(),
                Users::IS_ACTIVE_COLUMN => $this->boolean()->notNull()->defaultValue(true),
            ]);
            $this->createIndex(
                'idx_users_id_active',
                Users::TABLE_NAME,
                [Users::ID_COLUMN, Users::IS_ACTIVE_COLUMN]
            );
        }
        if ($schema->getTableSchema(UserActivities::TABLE_NAME, true) === null) {
            $this->createTable(UserActivities::TABLE_NAME, [
                UserActivities::ID_COLUMN => $this->bigPrimaryKey(),
                UserActivities::USER_ID_COLUMN => $this->bigInteger()->notNull(),
                UserActivities::ACTIVITY_TYPE_COLUMN => "ENUM('banner_click','promo_view','message_read') NOT NULL",
                UserActivities::ACTIVITY_DATA_COLUMN => $this->json(),
                UserActivities::CREATED_AT_COLUMN => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')
            ]);
            $this->createIndex(
                'idx_user_activities_created_user_id',
                UserActivities::TABLE_NAME,
                [UserActivities::CREATED_AT_COLUMN, UserActivities::USER_ID_COLUMN]
            );
            $this->addForeignKey(
                'fk_user_activities_user',
                UserActivities::TABLE_NAME,
                UserActivities::USER_ID_COLUMN,
                Users::TABLE_NAME,
                Users::ID_COLUMN,
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        $tableList = [
            UserActivities::TABLE_NAME,
            Config::TABLE_NAME,
            Users::TABLE_NAME,
        ];
        foreach ($tableList as $targetTable) {
            $this->dropTable($targetTable);
        }
    }
}
