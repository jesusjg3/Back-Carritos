<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->index('state_id');
            $table->index('driver_id');
            $table->index('created_at');
            $table->index(['origin_address', 'destination_address']);
        });

        Schema::table('trip_ratings', function (Blueprint $table) {
            $table->index('rating');
            $table->index('emitter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['state_id']);
            $table->dropIndex(['driver_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['origin_address', 'destination_address']);
        });

        Schema::table('trip_ratings', function (Blueprint $table) {
            $table->dropIndex(['rating']);
            $table->dropIndex(['emitter_id']);
        });
    }
};
