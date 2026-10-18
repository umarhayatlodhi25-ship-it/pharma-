<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Database\PostgresConnection;
use Illuminate\Database\Connection;
use DateTime;
use PDO;

class PostgresConnectionTest extends TestCase
{
    public function test_postgres_resolver_is_registered(): void
    {
        $resolver = Connection::getResolver('pgsql');
        $this->assertNotNull($resolver);

        $pdo = function () {
            return new PDO('sqlite::memory:');
        };

        $connection = $resolver($pdo, 'testdb', '', []);
        $this->assertInstanceOf(PostgresConnection::class, $connection);
    }

    public function test_prepare_bindings_formats_booleans_as_strings(): void
    {
        $pdo = function () {
            return new PDO('sqlite::memory:');
        };

        $conn = new PostgresConnection($pdo, 'testdb', '', []);
        
        $bindings = [
            'active' => true,
            'inactive' => false,
            'count' => 42,
            'title' => 'Doctor',
            'date' => new DateTime('2026-10-18 10:00:00'),
        ];

        $prepared = $conn->prepareBindings($bindings);

        // Booleans MUST be converted to 'true'/'false' strings for PostgreSQL compatibility
        // instead of integers (1/0), preventing SQLSTATE[42804] Datatype mismatch
        $this->assertSame('true', $prepared['active']);
        $this->assertSame('false', $prepared['inactive']);
        $this->assertSame(42, $prepared['count']);
        $this->assertSame('Doctor', $prepared['title']);
        $this->assertSame('2026-10-18 10:00:00', $prepared['date']);
    }
}
