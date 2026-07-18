<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->decimal('origin_lat', 10, 8);
            $table->decimal('origin_lng', 11, 8);
            $table->string('origin_address')->nullable();
            $table->decimal('destination_lat', 10, 8);
            $table->decimal('destination_lng', 11, 8);
            $table->string('destination_address')->nullable();
            $table->decimal('distance', 10, 2);
            $table->unsignedInteger('passengers_count')->default(1);
            $table->unsignedInteger('request_attempt')->default(1);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('state_id');
            $table->index('driver_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
