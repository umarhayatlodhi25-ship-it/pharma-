<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE patient_tokens DROP CONSTRAINT IF EXISTS patient_tokens_status_check");
            DB::statement("ALTER TABLE patient_tokens ADD CONSTRAINT patient_tokens_status_check CHECK (status IN ('waiting', 'called', 'in_consultation', 'completed', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE patient_tokens DROP CONSTRAINT IF EXISTS patient_tokens_status_check");
            DB::statement("ALTER TABLE patient_tokens ADD CONSTRAINT patient_tokens_status_check CHECK (status IN ('waiting', 'called', 'completed', 'cancelled'))");
        }
    }
};
