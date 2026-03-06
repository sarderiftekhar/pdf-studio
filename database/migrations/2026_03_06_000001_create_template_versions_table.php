<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_studio_template_versions', function (Blueprint $table) {
            $table->id();
            $table->string('template_name');
            $table->unsignedInteger('version_number');
            $table->string('view');
            $table->text('description')->nullable();
            $table->json('default_options')->nullable();
            $table->string('data_provider')->nullable();
            $table->string('author')->nullable();
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->unique(['template_name', 'version_number']);
            $table->index('template_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_studio_template_versions');
    }
};
