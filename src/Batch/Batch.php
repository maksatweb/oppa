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

namespace Oppa\Batch;

use Oppa\{Util, Resource};
use Oppa\Agent\AgentInterface;
use Oppa\Query\Result\ResultInterface;

/**
 * @package Oppa
 * @object  Oppa\Batch\Batch
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class Batch implements BatchInterface
{
    /**
     * Agent.
     * @var Oppa\Agent\AgentInterface
     */
    protected $agent;

    /**
     * Query queue.
     * @var array
     */
    protected $queue = [];

    /**
     * Query results.
     * @var array
     */
    protected $results = [];

    /**
     * Total transaction time.
     * @var float
     */
    protected $totalTime = 0.00;

    /**
     * Get agent.
     * @return Oppa\Agent\AgentInterface
     */
    public final function getAgent(): AgentInterface
    {
        return $this->agent;
    }

    /**
     * Get queue.
     * @return array
     */
    public final function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * Get result.
     * @param  int $i
     * @return ?Oppa\Query\Result\ResultInterface
     */
    public final function getResult(int $i = 0): ?ResultInterface
    {
        return $this->results[$i] ?? null;
    }

    /**
     * Get result id.
     * @param  int $i
     * @return ?int
     */
    public final function getResultId(int $i): ?int
    {
        return ($result = $this->getResult($i)) ? $result->getId() : null;
    }

    /**
     * Get result ids.
     * @param  int $i
     * @return array
     */
    public final function getResultIds(int $i): array
    {
        return ($result = $this->getResult($i)) ? $result->getIds() : [];
    }

    /**
     * Get results.
     * @return array
     */
    public final function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get results ids.
     * @param  bool $merge
     * @return array
     */
    public final function getResultsIds(bool $merge = true): array
    {
        $return = [];
        if (!empty($this->results)) {
            if ($merge) {
                foreach ($this->results as $result) {
                    $return = array_merge($return, $result->getIds());
                }
            } else {
                foreach ($this->results as $result) {
                    $return[] = $result->getIds();
                }
            }
        }

        return $return;
    }

    /**
     * Get total time.
     * @return float
     */
    public final function getTotalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * Reset.
     * @return void
     */
    public final function reset(): void
    {
        $this->queue = [];
        $this->results = [];
        $this->totalTime = 0.00;
    }

    /**
     * Queue.
     * @param  string     $query
     * @param  array|null $queryParams
     * @return self
     */
    public final function queue(string $query, array $queryParams = null): BatchInterface
    {
        $this->queue[] = $this->agent->prepare($query, $queryParams);

        return $this;
    }

    /**
     * Do.
     * @return Oppa\Batch\BatchInterface
     */
    public final function do(): BatchInterface
    {
        // no need to get excited
        if (empty($this->queue)) {
            return $this;
        }

        // check transaction status
        $resource = $this->agent->getResource();
        /* if ($resource->getType() == Resource::TYPE_MYSQL_LINK) {
            // nope..
        } else */ if ($resource->getType() == Resource::TYPE_PGSQL_LINK) {
            $resource = $resource->getObject();
            $resourceStatus = pg_transaction_status($resource);
            if ($resourceStatus !== PGSQL_TRANSACTION_IDLE) do {
                time_nanosleep(0, 100000);
                $resourceStatus = pg_transaction_status($resource);
            } while ($resourceStatus === PGSQL_TRANSACTION_ACTIVE);
        }

        $startTime = microtime(true);

        $this->start(); // begin

        foreach ($this->queue as $query) {
            // @important (clone)
            $result = clone $this->agent->query($query);

            if ($result->getRowsAffected() > 0) {
                $this->results[] = $result;
            }

            unset($result);
        }

        $this->end(); // commit, go go go!

        $this->totalTime = (float) number_format(microtime(true) - $startTime, 10);

        $this->agent->getResult()->reset();

        return $this;
    }

    /**
     * Do query.
     * @param  string     $query
     * @param  array|null $queryParams
     * @return Oppa\Batch\BatchInterface
     */
    public final function doQuery(string $query, array $queryParams = null): BatchInterface
    {
        return $this->queue($query, $queryParams)->do();
    }

    /**
     * Run.
     * @deprecated
     */
    public final function run(...$args)
    {
        Util::generateDeprecatedMessage($this, 'run()', 'do()');

        return call_user_func_array([$this, 'do'], $args);

    }

    /**
     * Run query.
     * @deprecated
     */
    public final function runQuery(...$args)
    {
        Util::generateDeprecatedMessage($this, 'runQuery()', 'doQuery()');

        return call_user_func_array([$this, 'doQuery'], $args);
    }

    /**
     * Cancel.
     * @deprecated
     */
    public final function cancel(...$args)
    {
        Util::generateDeprecatedMessage($this, 'cancel()', 'undo()');

        return call_user_func_array([$this, 'undo'], $args);

    }

    /**
     * Start.
     * @return void
     */
    abstract protected function start(): void;

    /**
     * End.
     * @return void
     */
    abstract protected function end(): void;
}
