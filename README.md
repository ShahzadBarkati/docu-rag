# Docu-RAG

A production-ready, **RAG-based chat application** that lets users upload documents and chat with them using AI. Built as a guided learning project.

Upload a PDF, DOCX, text file, HTML page, CSV, **or image/scan** → the app parses/chunks/embeds (and OCRs scanned pages via Gemini Vision) → ask questions in plain language → get streaming answers with citations, grounded only in your documents.

## Tech Stack

- **Backend:** Laravel 13 (API-only) + `laravel/ai` + Laravel Sanctum + PostgreSQL with **pgvector**
- **Frontend:** Vue 3 (Composition API) + Vite + Tailwind CSS + Vue Router (plain `fetch` streaming, no AI SDK middleware)
- **AI Provider:** Google Gemini — chat (`gemini-3.6-flash`) + `gemini-embedding-001` embeddings (3072-dims) + **Vision OCR**
- **Auth:** Multi-user (register / login / logout), per-user document & conversation ownership
- **Document support:** PDF, DOCX, TXT, HTML, CSV, JPG/PNG/WebP (images OCR'd via Gemini Vision only when the text layer is empty/boilerplate; sha256 content-hash dedupe)
- **Chat:** Streaming via SSE (`conversation` → `text_delta` → `[DONE]`), semantic retrieval on cosine distance `< 0.4`, top-5 chunks with citations
- **Local dev:** Docker Compose (bind-mounted `backend` + `frontend`, separate `queue` worker, `pgvector/pgvector:pg17` DB)
- **Deploy:** GitHub Actions CI → Docker Hub → Backend on Render (Docker, supervisord nginx+php-fpm+queue), Frontend on Vercel, DB on Neon/PGVector

## Architecture / Data Flow

```
Upload → Parse/OCR → Chunk (2000 chars, 20% overlap, sentence-boundary split) → Embed (gemini-embedding-001) → pgvector
Question → embed query → cosine similarity top-k (embedding <=> ?::vector < 0.4) → context with citations
        → Gemini (gemini-3.6-flash) answers → SSE stream → Vue 3 plain fetch
```

Vectors live in Postgres via pgvector — `vector(3072)` column with HNSW index (`vector_cosine_ops`), queried natively with Postgres `<=>` cosine distance.

## Run Locally

```bash
# 1. Env files
cp .env.template .env                       # root (used by docker compose)
cp backend/.env.example backend/.env        # backend (add your GEMINI_API_KEY)

# 2. Start the stack (backend:8000, frontend:5173, queue worker, pgvector db)
docker compose up -d --build

# 3. Inside the backend container (first run)
docker compose exec backend php artisan migrate

# 4. Open http://localhost:5173, register, upload a document, chat
```

## Status

- **Phase 0–8 implemented.** 17+ tests green (Pint clean), OCR/doc-jobs/dedupe verified, streaming hardened.
- **Phase 9 (stretch)** — partial start: rate-limit/429 handling, worker timeouts, upload caps; pending Horizon/Pulse, small-to-big + hybrid (FTS+vector) retrieval.

Full details, decisions, and the chunking evolution ladder live in `OVERVIEW.md`.