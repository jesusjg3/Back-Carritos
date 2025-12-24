<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('rols')->cascadeOnDelete();
            $table->string('tab_name');
            $table->string('tab_icon')->nullable();
            $table->integer('tab_order');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tabs');
    }
};
