<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'score')) {
                $table->decimal('score', 3, 2)->default(5.00)->comment('Rating average');
            }
            if (!Schema::hasColumn('users', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->comment('Total number of ratings received');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'score')) {
                $table->dropColumn('score');
            }
            if (Schema::hasColumn('users', 'rating_count')) {
                $table->dropColumn('rating_count');
            }
        });
    }
};
