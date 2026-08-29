<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');  // because Laravel's schema builder doesn't support the vector column type from the pgvector extension
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->unsignedInteger('char_start');
            $table->unsignedInteger('char_end');
            $table->unsignedBigInteger('parent_chunk_id')->nullable();
            $table->foreign('parent_chunk_id')
                ->references('id')
                ->on('document_chunks')
                ->nullOnDelete();
            $table->unsignedInteger('token_count')->nullable();
            $table->timestamps();

            $table->index('parent_chunk_id');
            $table->index(['document_id', 'chunk_index']);
        });
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(768)'); // Laravel's Blueprint provides standard column types (string, text, integer, json, etc.), but vector(768) is a custom PostgreSQL type added by the pgvector extension for storing embeddings. Since there's no native $table->vector() method in Laravel, you must use DB::statement() to execute raw SQL for this column type.

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
