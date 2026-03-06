<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_studio_render_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('workspace_id')->constrained('pdf_studio_workspaces')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('view')->nullable();
            $table->text('html')->nullable();
            $table->json('data')->nullable();
            $table->json('options')->nullable();
            $table->string('driver')->nullable();
            $table->string('output_path')->nullable();
            $table->string('output_disk')->nullable();
            $table->integer('bytes')->nullable();
            $table->float('render_time_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_studio_render_jobs');
    }
};
