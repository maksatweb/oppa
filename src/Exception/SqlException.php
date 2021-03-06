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

namespace Oppa\Exception;

/**
 * @package Oppa
 * @object  Oppa\Exception\SqlException
 * @author  Kerem Güneş <k-gun@mail.com>
 */
class SqlException extends \Exception
{
    /**
     * Sql state.
     * @var ?string
     */
    protected $sqlState;

    /**
     * Constructor.
     * @param ?string     $message
     * @param ?int        $code
     * @param ?string     $sqlState
     * @param ?\Throwable $previous
     */
    public final function __construct(?string $message = '', ?int $code = 0, ?string $sqlState = null,
        ?\Throwable $previous = null)
    {
        // set state
        $this->sqlState = $sqlState;

        // prepend state to message
        if ($this->sqlState) {
            $message = sprintf('SQLSTATE[%s]: %s', $this->sqlState, $message);
        }

        parent::__construct((string) $message, (int) $code, $previous);
    }

    /**
     * Get sql state.
     * @return ?string
     */
    public final function getSqlState(): ?string
    {
        return $this->sqlState;
    }
}
