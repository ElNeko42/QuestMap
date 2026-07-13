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
        Schema::create('user_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('recorded_at');

            // Query pattern for anti-cheat: latest point(s) for a user.
            $table->index(['user_id', 'recorded_at']);
        });

        DB::statement('ALTER TABLE user_location_history ADD COLUMN location geography(Point, 4326) NOT NULL');
        DB::statement('CREATE INDEX user_location_history_location_gist ON user_location_history USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_location_history');
    }
};
