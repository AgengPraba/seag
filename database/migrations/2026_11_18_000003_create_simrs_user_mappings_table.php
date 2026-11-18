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
        Schema::create('simrs_user_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('issuer', 255);
            $table->string('subject', 255);
            $table->string('simrs_user_id', 64)->index();
            $table->foreignId('access_request_id')->nullable()->constrained('external_access_requests')->nullOnDelete();
            $table->string('status', 30)->default('active')->index(); // active, suspended, revoked
            $table->timestamps();

            $table->unique(['issuer', 'subject'], 'unique_issuer_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simrs_user_mappings');
    }
};
