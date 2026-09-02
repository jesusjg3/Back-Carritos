<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->string('result', 30)->default('success');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
        });

        DB::table('permissions')->updateOrInsert(
            ['name' => 'view_audit_log'],
            ['description' => 'Permite consultar la auditoría de acciones administrativas']
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        DB::table('permissions')->where('name', 'view_audit_log')->delete();
    }
};
