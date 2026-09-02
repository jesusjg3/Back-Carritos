<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trip_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('passenger_id')->constrained('users')->cascadeOnDelete();
            
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->string('pickup_address')->nullable();
            $table->integer('passengers_count')->default(1);
            
            $table->enum('status', ['requested', 'accepted', 'boarded', 'dropped_off', 'cancelled'])
                  ->default('requested');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['trip_id', 'passenger_id']);
            
            $table->index('passenger_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_passengers');
    }
};
