<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_table_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_id')->constrained('data')->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            $table->text('search_content')->nullable();
            $table->json('cells');
            $table->timestamps();

            $table->index(['data_id', 'row_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_table_rows');
    }
};
