<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('helpers.php'))) {
            require_once app_path('helpers.php');
        }

        \Illuminate\Database\Connection::resolverFor('pgsql', function ($connection, $database, $prefix = '', array $config = []) {
            return new \App\Database\PostgresConnection($connection, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Database\Events\ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'pgsql') {
                $event->connection->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

                // Auto-heal production schema on Neon PostgreSQL (prevents missing column errors on serverless deployments)
                static $schemaSynced = false;
                if (!$schemaSynced) {
                    $schemaSynced = true;
                    try {
                        $event->connection->statement('ALTER TABLE medicines ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL');
                        $event->connection->statement("CREATE TABLE IF NOT EXISTS product_audit_logs (
                            id BIGSERIAL PRIMARY KEY,
                            user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
                            user_name VARCHAR(255) NULL,
                            user_email VARCHAR(255) NULL,
                            action VARCHAR(255) NOT NULL,
                            description TEXT NULL,
                            ip_address VARCHAR(45) NULL,
                            user_agent TEXT NULL,
                            affected_product_ids JSONB NULL,
                            summary JSONB NULL,
                            status VARCHAR(50) DEFAULT 'success',
                            created_at TIMESTAMP NULL,
                            updated_at TIMESTAMP NULL
                        )");
                    } catch (\Throwable $e) {
                        // Non-blocking fallback
                    }
                }
            }
        });

        // Centralized Account Profile sharing across all views
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('account_profiles')) {
                    $view->with('accountProfile', account_profile());
                }
            } catch (\Throwable $e) {
                // Ignore during early migrations / setups
            }
        });
    }
}