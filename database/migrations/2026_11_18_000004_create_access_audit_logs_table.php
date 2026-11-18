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
        Schema::create('access_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64)->index();
            $table->string('actor_type', 32)->default('system'); // administrator, service, system
            $table->string('actor_id', 64)->nullable();
            $table->string('target_user_id', 64)->nullable()->index();
            $table->string('source_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('event_status', 32)->default('success')->index(); // success, failed, denied
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_audit_logs');
    }
};

