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
        Schema::create('data', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status', 32)->default('ready');
            $table->json('raw_data')->nullable();
            $table->json('digital_data')->nullable();
            $table->text('extraction_failure_message')->nullable();
            $table->string('ai_provider', 64)->nullable();
            $table->string('ai_model', 128)->nullable();
            $table->timestamp('extraction_started_at')->nullable();
            $table->unsignedInteger('extraction_duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};
