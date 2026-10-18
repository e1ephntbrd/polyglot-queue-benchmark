<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property string $path
 * @property string|null $value
 */
class Config extends ActiveRecord
{
    public const TABLE_NAME = '{{%config}}';

    /**#@+
     * Columns
     */
    public const ID_COLUMN = 'id';
    public const PATH_COLUMN = 'path';
    public const VALUE_COLUMN = 'value';
    public const CREATED_AT_COLUMN = 'created_at';
    public const UPDATE_AT_COLUMN = 'update_at';
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
            [[self::PATH_COLUMN], 'required'],
            [[self::PATH_COLUMN], 'string', 'max' => 255],
            [[self::VALUE_COLUMN], 'safe'],
        ];
    }

    /**
     * Додає або оновлює конфіг за ключем
     */
    public static function add(string $path, $value): bool
    {
        $config = static::findOne([self::PATH_COLUMN => $path]) ?? new static();
        $config->path = $path;
        $config->value = is_scalar($value) ? (string)$value : json_encode($value);
        try {
            return $config->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Отримати значення конфігу
     */
    public static function get(string $path, $default = null)
    {
        return static::find()
            ->select(self::VALUE_COLUMN)
            ->where([self::PATH_COLUMN => $path])
            ->scalar() ?: $default;
    }

    /**
     * Отримати всі конфіги
     */
    public static function all(): array
    {
        return static::find()
            ->select([self::VALUE_COLUMN])
            ->indexBy(self::PATH_COLUMN)
            ->column();
    }
}
