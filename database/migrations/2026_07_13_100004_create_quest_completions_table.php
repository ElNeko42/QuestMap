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
        Schema::create('quest_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('submitted_at');
            $table->string('photo_path')->nullable();
            $table->jsonb('data_payload')->nullable();
            $table->jsonb('ai_validation_result')->nullable();
            $table->decimal('ai_confidence', 3, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'manual_review'])->default('pending')->index();
            $table->integer('xp_awarded')->default(0);
            $table->timestamps();

            // A user can only complete each quest once.
            $table->unique(['quest_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        DB::statement('ALTER TABLE quest_completions ADD COLUMN location_at_submit geography(Point, 4326)');
        DB::statement('CREATE INDEX quest_completions_location_gist ON quest_completions USING GIST (location_at_submit)');
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_completions');
    }
};
