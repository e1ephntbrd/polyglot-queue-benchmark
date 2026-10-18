<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Model "{{%users}}" table
 *
 * @property int $id         Primary key
 * @property string $email      User email address
 * @property string|null $name  Optional display name
 * @property bool $is_active  Whether the user is active
 * @property string $created_at Timestamp when the record was created
 * @property string|null $updated_at Timestamp when the record was last updated
 */
class Users extends ActiveRecord
{
    public const TABLE_NAME = '{{%users}}';

    /**#@+
     * Columns
     */
    public const ID_COLUMN = 'id';
    public const EMAIL_COLUMN = 'email';
    public const IS_ACTIVE_COLUMN = 'is_active';
    /**#@- */

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return [
            [[self::EMAIL_COLUMN], 'required'],
            [[self::EMAIL_COLUMN], 'email'],
            [[self::EMAIL_COLUMN], 'string', 'max' => 255],
            [[self::EMAIL_COLUMN], 'unique'],
            [[self::IS_ACTIVE_COLUMN], 'boolean'],
            [[self::IS_ACTIVE_COLUMN], 'default', 'value' => true],
        ];
    }

    /**
     * Retrieve email addresses of active users by a list of IDs.
     *
     * Optimized for batches of 2,500–5,000 IDs.
     * Uses index (id, is_active) for efficient filtering.
     *
     * @param int[] $ids List of user IDs
     * @return string[] Array of email addresses
     */
    public static function getActiveEmailsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return self::find()
            ->select(self::EMAIL_COLUMN)
            ->where([self::ID_COLUMN => $ids])
            ->andWhere([self::IS_ACTIVE_COLUMN => true])
            ->column();
    }

    /**
     * Retrieve users data
     *
     * @return array
     */
    public static function getAll(): array
    {
        return self::find()
            ->select([self::ID_COLUMN, self::IS_ACTIVE_COLUMN, self::EMAIL_COLUMN])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }
}
