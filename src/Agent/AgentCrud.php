<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Oppa\Agent;

use Oppa\Config;
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\{Error, InvalidKeyException};

/**
 * @package    Oppa
 * @subpackage Oppa\Agent
 * @object     Oppa\Agent\AgentCrud
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class AgentCrud
{
    /**
     * Select.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int|array    $limit
     * @param  int          $fetchType
     * @return any
     */
    final public function select(string $table, $fields = null, string $where = null,
        array $params = null, $limit = null, int $fetchType = null)
    {
        if ($fields == null) {
            $fields = '*';
        }

        return $this->query(sprintf(
            'SELECT %s FROM %s %s %s',
                $this->escapeIdentifier($fields),
                    $this->escapeIdentifier($table),
                        $this->where($where, $params),
                            $this->limit($limit)
        ), null, null, $fetchType)->getData();
    }

    /**
     * Select one.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int          $fetchType
     * @return any
     */
    final public function selectOne(string $table, $fields = null, string $where = null,
        array $params = null, int $fetchType = null)
    {
        if ($fields == null) {
            $fields = '*';
        }

        return $this->query(sprintf(
            'SELECT %s FROM %s %s LIMIT 2',
                $this->escapeIdentifier($fields),
                    $this->escapeIdentifier($table),
                        $this->where($where, $params)
        ), null, null, $fetchType)->getDataItem(0);
    }

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return ?int
     */
    final public function insert(string $table, array $data): ?int
    {
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        $keys = array_keys(current($data));
        $values = [];
        foreach ($data as $dat) {
            $values[] = '('. $this->escape(array_values($dat)) .')';
        }

        return $this->query(sprintf(
            'INSERT INTO %s (%s) VALUES %s',
                $this->escapeIdentifier($table),
                    $this->escapeIdentifier($keys),
                        join(',', $values)
        ))->getId();
    }

    /**
     * Update.
     * @param  string    $table
     * @param  array     $data
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    final public function update(string $table, array $data, string $where = null,
        array $params = null, $limit = null): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = sprintf('%s = %s',
                $this->escapeIdentifier($key), $this->escape($value));
        }

        return $this->query(sprintf(
            'UPDATE %s SET %s %s %s',
                $this->escapeIdentifier($table),
                    join(', ', $set),
                        $this->where($where, $params),
                            $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Delete.
     * @param  string    $table
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    final public function delete(string $table, string $where = null,
        array $params = null, $limit = null): int
    {
        return $this->query(sprintf(
            'DELETE FROM %s %s %s',
                $this->escapeIdentifier($table),
                    $this->where($where, $params),
                        $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Count.
     * @param  string $query
     * @return int
     */
    final public function count(string $query): int
    {
        $result = $this->get("SELECT count(*) AS count FROM ({$query}) AS tmp");

        return intval($result->count);
    }

    /**
     * Get.
     * @param  string $query
     * @param  array  $params
     * @return object|array|null
     */
    final public function get(string $query, array $params = null)
    {
        return $this->query($query, $params, 1)->item(0);
    }

    /**
     * Get array.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getArray(string $query, array $params = null): ?array
    {
        return $this->query($query, $params, 1)->toArray()[0] ?? null;
    }

    /**
     * Get object.
     * @param  string $query
     * @param  array  $params
     * @return ?\stdClass
     */
    final public function getObject(string $query, array $params = null): ?\stdClass
    {
        return $this->query($query, $params, 1)->toObject()[0] ?? null;
    }

    /**
     * Get class.
     * @param  string $query
     * @param  array  $params
     * @return object
     */
    final public function getClass(string $query, array $params = null, string $class)
    {
        return $this->query($query, $params, 1)->toClass($class)[0] ?? null;
    }

    /**
     * Get all.
     * @param  string $query
     * @param  array  $params
     * @return array
     */
    final public function getAll(string $query, array $params = null): array
    {
        return $this->query($query, $params)->getData();
    }

    /**
     * Get all array.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getAllArray(string $query, array $params = null): ?array
    {
        return $this->query($query, $params)->toArray();
    }

    /**
     * Get all object.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getAllObject(string $query, array $params = null): ?array
    {
        return $this->query($query, $params)->toObject();
    }

    /**
     * Get all array.
     * @param  string $query
     * @param  array  $params
     * @param  string $class
     * @return ?array
     */
    final public function getAllClass(string $query, array $params = null, string $class): ?array
    {
        return $this->query($query, $params)->toClass($class);
    }
}