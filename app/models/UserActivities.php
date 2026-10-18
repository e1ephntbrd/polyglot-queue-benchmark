<?php

namespace app\models;

use app\models\dto\BatchOutput;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Model "{{%user_activities}}"
 *
 * @property int         $id            Primary key
 * @property int         $user_id       Foreign key to Users
 * @property string      $activity_type Type of activity (ENUM)
 * @property array|null  $activity_data Optional JSON payload
 * @property string      $created_at    Timestamp when the activity was created
 */
class UserActivities extends ActiveRecord
{
    public const TABLE_NAME = '{{%user_activities}}';

    /**#@+
     * Column names
     */
    public const ID_COLUMN            = 'id';
    public const USER_ID_COLUMN       = 'user_id';
    public const ACTIVITY_TYPE_COLUMN = 'activity_type';
    public const ACTIVITY_DATA_COLUMN = 'activity_data';
    public const CREATED_AT_COLUMN    = 'created_at';
    /**#@- */

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * Validation rules for model attributes.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // user_id must be present and integer
            [[self::USER_ID_COLUMN], 'required'],
            [[self::USER_ID_COLUMN], 'integer'],

            // activity_type must be present and string
            [[self::ACTIVITY_TYPE_COLUMN], 'required'],
            [[self::ACTIVITY_TYPE_COLUMN], 'string', 'max' => 64],

            // activity_data can be safe (JSON/text)
            [[self::ACTIVITY_DATA_COLUMN], 'safe'],

            // created_at must be a valid datetime
            [[self::CREATED_AT_COLUMN], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

    /**
     * Retrieve a batch of distinct user IDs within the given time window,
     * using key-pagination with a cursor (lastId).
     *
     * @param int $lastId Cursor position (last processed ID)
     * @param int $limit  Batch size
     * @param int $hoursWindow Time window in hours (e.g. 24)
     * @return \app\models\dto\BatchOutput
     */
    public static function getDistinctUserIdsBatch(int $lastId, int $limit = 1000, int $hoursWindow = 24): BatchOutput
    {
        $rows = self::find()
            ->select([self::ID_COLUMN, self::USER_ID_COLUMN])
            ->distinct()
            ->where(['>=', self::CREATED_AT_COLUMN, new Expression("NOW() - INTERVAL {$hoursWindow} HOUR")])
            ->andWhere(['>', self::ID_COLUMN, $lastId])
            ->orderBy([self::ID_COLUMN => SORT_ASC])
            ->limit($limit)
            ->asArray()
            ->all();
        return new BatchOutput(
            array_column($rows, self::USER_ID_COLUMN),
            !empty($rows) ? end($rows)[self::ID_COLUMN] : $lastId
        );
    }
}
