<?php

namespace App\Database;

use DateTimeInterface;
use Illuminate\Database\PostgresConnection as BasePostgresConnection;

class PostgresConnection extends BasePostgresConnection
{
    /**
     * Prepare the query bindings for execution.
     *
     * In PostgreSQL, integer 1/0 cannot be implicitly cast to boolean.
     * When PDO::ATTR_EMULATE_PREPARES is enabled (such as with connection poolers like Neon PgBouncer),
     * binding PHP booleans as integers results in:
     * "SQLSTATE[42804]: Datatype mismatch: 7 ERROR: column ... is of type boolean but expression is of type integer"
     * Converting booleans to 'true' / 'false' string literals ensures PostgreSQL correctly parses them.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return $bindings;
    }
}
