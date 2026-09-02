<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('trip_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('emitter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('rating'); // 1 - 5
            $table->text('comment')->nullable();
            $table->timestamps();
            
            
            $table->unique(['trip_id', 'emitter_id', 'receiver_id']);
            $table->index('receiver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_ratings');
    }
};
