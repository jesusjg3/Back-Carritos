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
