<?php
namespace app\services;

use yii\db\Query;

/**
 * Utility class for keyset pagination.
 *
 * Provides efficient pagination using a numeric ID column
 * instead of offset-based queries, which improves performance
 * on large datasets.
 */
class KeysetPagination
{
    /** @var Query The base query object */
    private Query $query;

    /** @var int|null The last processed ID for pagination */
    private ?int $lastId = null;

    /** @var string The column name used for ordering and pagination */
    private string $idColumn;

    /**
     * Constructor.
     *
     * @param Query  $query     The base query to paginate
     * @param string $idColumn  The column name used for ordering (default: "id")
     */
    public function __construct(Query $query, string $idColumn = 'id')
    {
        $this->query = $query;
        $this->idColumn = $idColumn;
    }

    /**
     * Fetch the next batch of rows.
     *
     * Clones the base query, applies a condition to fetch rows
     * with IDs greater than the last processed ID, orders by
     * ascending ID, and limits the result set.
     *
     * @param int $limit Number of rows to fetch
     * @return array The result set as an array of rows
     */
    public function nextBatch(int $limit): array
    {
        $q = clone $this->query;

        if ($this->lastId !== null) {
            $q->andWhere(['>', $this->idColumn, $this->lastId]);
        }

        $rows = $q->orderBy([$this->idColumn => SORT_ASC])
            ->limit($limit)
            ->all();

        if (!empty($rows)) {
            $this->lastId = (int)end($rows)[$this->idColumn];
        }

        return $rows;
    }
}
