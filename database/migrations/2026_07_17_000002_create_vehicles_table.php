<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate')->unique();
            $table->string('brand');
            $table->string('model')->nullable();
            $table->string('color');
            $table->integer('capacity')->default(4);
            
            $table->string('external_reference_id')->nullable();
            
            $table->string('status')->default('active'); // active, maintenance, inactive
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
