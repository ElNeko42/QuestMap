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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('creator_type', ['ai', 'business', 'admin'])->default('ai');
            $table->string('title');
            $table->text('description');
            $table->string('category')->index();
            $table->integer('geofence_radius_m')->default(50);
            $table->enum('validation_type', ['photo_ai', 'checkin', 'data_input']);
            $table->text('validation_prompt')->nullable();
            $table->integer('xp_reward');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_completions')->nullable();
            $table->integer('completions_count')->default(0);
            $table->string('dedup_hash')->nullable()->unique();
            $table->enum('status', ['active', 'paused', 'expired', 'exhausted'])->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        DB::statement('ALTER TABLE quests ADD COLUMN location geography(Point, 4326) NOT NULL');
        DB::statement('CREATE INDEX quests_location_gist ON quests USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
