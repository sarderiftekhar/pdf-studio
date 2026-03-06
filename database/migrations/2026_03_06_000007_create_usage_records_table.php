<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_studio_usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('pdf_studio_workspaces')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('idempotency_key')->unique();
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'event_type']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_studio_usage_records');
    }
};
