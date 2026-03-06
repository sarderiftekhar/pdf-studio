<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_studio_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('pdf_studio_workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->string('prefix', 8);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_studio_api_keys');
    }
};
