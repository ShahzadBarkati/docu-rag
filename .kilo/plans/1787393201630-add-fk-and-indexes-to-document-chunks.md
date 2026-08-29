# Plan: Add FK Constraint and Indexes to document_chunks Migration

## Goal
Add referential integrity and query performance optimizations to the `document_chunks` table for the self-referential `parent_chunk_id` relationship.

## Changes Required

### Migration: `backend/database/migrations/2026_08_16_102343_create_document_chunks_table.php`

Add inside `Schema::create()` after line 23 (`$table->unsignedBigInteger('parent_chunk_id')->nullable();`):

```php
$table->foreign('parent_chunk_id')
      ->references('id')
      ->on('document_chunks')
      ->nullOnDelete();

$table->index('parent_chunk_id');
$table->index(['document_id', 'chunk_index']);
```

### Rationale
- **FK constraint**: Enforces referential integrity; `nullOnDelete()` sets child chunks' `parent_chunk_id` to NULL when parent is deleted (prevents orphaned rows while preserving children)
- **Index on `parent_chunk_id`**: Speeds up `children()` relationship queries
- **Composite index on `[document_id, chunk_index]`**: Optimizes ordered chunk retrieval per document (common access pattern)

## Validation Steps
1. Run migration fresh: `php artisan migrate:fresh --seed` (if seeders exist)
2. Verify FK exists in DB: `\d document_chunks` (PostgreSQL) or `SHOW CREATE TABLE document_chunks` (MySQL)
3. Test relationship: Create parent + child chunks, verify `parentChunk()` and `children()` work
4. Test cascade: Delete parent, verify child's `parent_chunk_id` becomes NULL

## Out of Scope
- Model changes (already correct)
- Down migration adjustments (handled by `Schema::dropIfExists`)
- pgvector extension setup (already handled)