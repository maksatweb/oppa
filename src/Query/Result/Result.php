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

namespace Oppa\Query\Result;

use Oppa\Resource;
use Oppa\Agent\AgentInterface;
use Oppa\Exception\InvalidValueException;

/**
 * @package Oppa
 * @object  Oppa\Query\Result\Result
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class Result implements ResultInterface
{
    /**
     * Agent.
     * @var Oppa\Agent\AgentInterface
     */
    protected $agent;

    /**
     * Resource.
     * @var Oppa\Resource
     */
    protected $result;

    /**
     * Fetch type.
     * @var int
     */
    protected $fetchType = ResultInterface::AS_OBJECT;

    /**
     * Fetch limit.
     * @var int
     */
    protected $fetchLimit = ResultInterface::LIMIT;

    /**
     * Fetch object.
     * @var string
     */
    protected $fetchObject = 'stdClass';


    /**
     * Data.
     * @var array
     */
    protected $data = [];

    /**
     * Ids.
     * @var array
     */
    protected $ids = [];

    /**
     * Rows count.
     * @var int
     */
    protected $rowsCount = 0;

    /**
     * Rows affected.
     * @var int
     */
    protected $rowsAffected = 0;

    /**
     * Destructor.
     */
    public final function __destruct()
    {
        $this->free();
    }

    /**
     * Get agent.
     * @return Oppa\Agent\AgentInterface
     */
    public final function getAgent(): AgentInterface
    {
        return $this->agent;
    }

    /**
     * Get result.
     * @return ?Oppa\Resource
     */
    public final function getResult(): ?Resource
    {
        return $this->result;
    }

    /**
     * Free.
     * @return void
     */
    public final function free(): void
    {
        $this->result && $this->result->free();
    }

    /**
     * Reset.
     * @return void
     */
    public final function reset(): void
    {
        // reset data
        $this->data = [];
        // reset properties
        $this->ids = [];
        $this->rowsCount = 0;
        $this->rowsAffected = 0;
    }

    /**
     * Detect fetch type.
     * @param  int|string $fetchType
     * @return int
     * @throws Oppa\Exception\InvalidValueException
     */
    public final function detectFetchType($fetchType): int
    {
        switch (gettype($fetchType)) {
            case 'NULL':
                return ResultInterface::AS_OBJECT;
            case 'integer':
                if (in_array($fetchType, [ResultInterface::AS_OBJECT, ResultInterface::AS_ARRAY_ASC,
                    ResultInterface::AS_ARRAY_NUM, ResultInterface::AS_ARRAY_ASCNUM])) {
                    return $fetchType;
                }
                break;
            case 'string':
                //  object, array_asc etc.
                $fetchTypeConst = 'Oppa\Query\Result\ResultInterface::AS_'. strtoupper($fetchType);
                if (defined($fetchTypeConst)) {
                    return constant($fetchTypeConst);
                }

                // user classes
                if (class_exists($fetchType)) {
                    $this->setFetchObject($fetchType);

                    return ResultInterface::AS_OBJECT;
                }
                break;
        }

        throw new InvalidValueException("Given '{$fetchType}' fetch type is not implemented!");
    }

    /**
     * Set fetch type.
     * @param  int|string $fetchType
     * @return void
     */
    public final function setFetchType($fetchType): void
    {
        $this->fetchType = $this->detectFetchType($fetchType);
    }

    /**
     * Get fetch type.
     * @return int
     */
    public final function getFetchType(): int
    {
        return $this->fetchType;
    }

    /**
     * Set fetch limit.
     * @param  int $fetchLimit
     * @return void
     */
    public final function setFetchLimit(int $fetchLimit): void
    {
        $this->fetchLimit = $fetchLimit;
    }

    /**
     * Get fetch limit.
     * @return int
     */
    public final function getFetchLimit(): int
    {
        return $this->fetchLimit;
    }

    /**
     * Set fetch object.
     * @param  string $fetchObject
     * @return void
     * @throws Oppa\Exception\InvalidValueException
     */
    public final function setFetchObject(string $fetchObject): void
    {
        if (!$fetchObject) {
            throw new InvalidValueException('Fetch object should not be empty!');
        }

        $this->fetchObject = $fetchObject;
    }

    /**
     * Get fetch object.
     * @return string
     */
    public final function getFetchObject(): string
    {
        return $this->fetchObject;
    }

    /**
     * Set id.
     * @param int $id
     */
    public final function setId(int $id): void
    {
        $this->ids[] = $id;
    }

    /**
     * Get id.
     * @return ?int
     */
    public final function getId(): ?int
    {
        $id = end($this->ids);

        return ($id !== false) ? $id : null;
    }

    /**
     * Set ids.
     * @param  array $ids
     * @return void
     */
    public final function setIds(array $ids): void
    {
        foreach ($ids as $id) {
            $this->ids[] = (int) $id;
        }
    }

    /**
     * Get ids.
     * @return array
     */
    public final function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Set rows count.
     * @param  int $rowsCount
     * @return void
     */
    public final function setRowsCount(int $rowsCount): void
    {
        $this->rowsCount = $rowsCount;
    }

    /**
     * Get rows count.
     * @return int
     */
    public final function getRowsCount(): int
    {
        return $this->rowsCount;
    }

    /**
     * Set rows affected.
     * @param  int $rowsAffected
     * @return void
     */
    public final function setRowsAffected(int $rowsAffected): void
    {
        $this->rowsAffected = $rowsAffected;
    }

    /**
     * Get rows affected.
     * @return int
     */
    public final function getRowsAffected(): int
    {
        return $this->rowsAffected;
    }

    /**
     * Has data.
     * @return bool
     */
    public final function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Get data.
     * @return array
     */
    public final function getData(): array
    {
        return $this->data;
    }

    /**
     * Get data item.
     * @param  int $i
     * @return any|null
     */
    public final function getDataItem(int $i)
    {
        return $this->data[$i] ?? null;
    }

    /**
     * Item.
     * @param  int $i
     * @return any|null
     */
    public final function item(int $i)
    {
        return $this->getDataItem($i);
    }

    /**
     * Item first.
     * @return any|null
     */
    public final function itemFirst()
    {
        return $this->getDataItem(0);
    }

    /**
     * Item last.
     * @return any|null
     */
    public final function itemLast()
    {
        return $this->getDataItem(count($this->data) - 1);
    }

    /**
     * To array.
     * @return ?array
     */
    public final function toArray(): ?array
    {
        $data = null;
        if (!empty($this->data)) {
            // no need to type-cast
            if (is_array($this->data[0])) {
                return $this->data;
            }
            $data = $this->data;
            foreach ($data as &$dat) {
                $dat = (array) $dat;
            }
        }

        return $data;
    }

    /**
     * To object.
     * @return ?array
     */
    public final function toObject(): ?array
    {
        $data = null;
        if (!empty($this->data)) {
            // no need to type-cast
            if (is_object($this->data[0])) {
                return $this->data;
            }
            $data = $this->data;
            foreach ($data as &$dat) {
                $dat = (object) $dat;
            }
        }

        return $data;
    }

    /**
     * To class.
     * @param  string $class
     * @return ?array
     */
    public final function toClass(string $class): ?array
    {
        $data = null;
        if (!empty($this->data)) {
            $data = $this->data;
            foreach ($data as &$dat) {
                $datClass = new $class();
                foreach ((array) $dat as $key => $value) {
                    $datClass->{$key} = $value;
                }
                $dat = $datClass;
            }
        }

        return $data;
    }

    /**
     * Is empty.
     * @return bool
     */
    public final function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Count.
     * @return int
     */
    public final function count(): int
    {
        return count($this->data);
    }

    /**
     * Get iterator.
     * @return \ArrayIterator
     */
    public final function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
