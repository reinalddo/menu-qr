<?php

declare(strict_types=1);

namespace MenuQr\Models;

use MenuQr\Db;

/** Moves a row one place up or down by swapping positions with its neighbour. */
final class Positions
{
    /** @param string $scope column that groups the rows (dishes move inside their category) */
    public static function swap(string $table, int $id, string $direction, ?string $scope = null): void
    {
        if (!in_array($table, ['categories', 'dishes'], true) || !in_array($scope, [null, 'category_id'], true)) {
            throw new \InvalidArgumentException('Unsupported table or scope');
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $self = $pdo->prepare("SELECT id, position" . ($scope ? ", {$scope}" : '') . " FROM {$table} WHERE id = ? FOR UPDATE");
            $self->execute([$id]);
            $row = $self->fetch();
            if (!$row) {
                $pdo->rollBack();
                return;
            }

            $up = $direction === 'up';
            $sql = "SELECT id, position FROM {$table} WHERE "
                . ($scope ? "{$scope} = :scope AND " : '')
                . ($up ? '(position < :pos OR (position = :pos2 AND id < :id))' : '(position > :pos OR (position = :pos2 AND id > :id))')
                . ' ORDER BY position ' . ($up ? 'DESC' : 'ASC') . ', id ' . ($up ? 'DESC' : 'ASC') . ' LIMIT 1 FOR UPDATE';
            $neighbour = $pdo->prepare($sql);
            $params = ['pos' => $row['position'], 'pos2' => $row['position'], 'id' => $id];
            if ($scope) {
                $params['scope'] = $row[$scope];
            }
            $neighbour->execute($params);
            $other = $neighbour->fetch();

            if ($other) {
                $update = $pdo->prepare("UPDATE {$table} SET position = ? WHERE id = ?");
                // Equal positions (rows created at the same time) still need distinct values.
                $newSelf = (int) $other['position'];
                $newOther = (int) $row['position'];
                if ($newSelf === $newOther) {
                    $newOther += $up ? 1 : -1;
                }
                $update->execute([$newSelf, $id]);
                $update->execute([$newOther, $other['id']]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
