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
        // PostGIS is required for the geography columns below.
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('radius_km');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // geography(Point,4326) is not supported by the schema builder.
        DB::statement('ALTER TABLE tenants ADD COLUMN center_point geography(Point, 4326)');
        DB::statement('CREATE INDEX tenants_center_point_gist ON tenants USING GIST (center_point)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
