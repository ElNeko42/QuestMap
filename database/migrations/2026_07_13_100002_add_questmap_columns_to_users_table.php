<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')
                ->constrained('tenants')->nullOnDelete();
            $table->unsignedBigInteger('xp')->default(0)->after('password');
            $table->unsignedInteger('level')->default(1)->after('xp');
            $table->timestamp('last_location_at')->nullable()->after('level');
        });

        DB::statement('ALTER TABLE users ADD COLUMN last_location geography(Point, 4326)');
        DB::statement('CREATE INDEX users_last_location_gist ON users USING GIST (last_location)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_last_location_gist');
        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS last_location');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['xp', 'level', 'last_location_at']);
        });
    }
};
