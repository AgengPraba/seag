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
        Schema::create('external_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 64)->index();
            $table->string('employee_name');
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, approved, rejected, revoked, expired
            
            $table->foreignId('requested_by')->constrained('administrators');
            $table->timestamp('requested_at')->useCurrent();
            
            $table->foreignId('approved_by')->nullable()->constrained('administrators');
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('rejected_by')->nullable()->constrained('administrators');
            $table->timestamp('rejected_at')->nullable();
            
            $table->foreignId('revoked_by')->nullable()->constrained('administrators');
            $table->timestamp('revoked_at')->nullable();
            
            $table->timestamp('expires_at')->nullable()->index();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_access_requests');
    }
};
