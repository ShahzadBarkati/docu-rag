# Docu-RAG

A production-ready, **RAG-based chat application** that lets users upload documents and chat with them using AI. Built as a guided learning project with a senior Laravel mentor.

Upload a PDF, DOCX, text file, HTML page, or CSV → the app parses, chunks, and embeds it → ask questions in plain language → get streaming answers with citations, grounded only in your documents.

## Tech Stack

- **Backend:** Laravel 13 (API-only) + `laravel/ai` SDK + Laravel Sanctum + PostgreSQL with **pgvector**
- **Frontend:** Vue 3 (Composition API) + Vite + Tailwind CSS + Pinia + Vue Router + `@ai-sdk/vue`
- **AI Provider:** Google Gemini — chat (`gemini-2.5-flash`) + `text-embedding-004` embeddings (768-dims)
- **Auth:** Multi-user (register / login / logout), per-user document & conversation ownership
- **Document support:** PDF, DOCX, TXT/MD, HTML, CSV (5 parsers)
- **Chat:** Streaming via SSE using the Vercel AI SDK stream protocol, consumed in Vue with `useChat`
- **Local dev:** Laravel Sail (`pgvector/pgvector:pg17` image)
- **Deploy (planned):** GitHub Actions CI → Frontend on Vercel, Backend (Docker) on Render, DB on Neon

## Architecture / Data Flow

```
Upload → Parse → Chunk (20% overlap) → Embed (Gemini text-embedding-004) → Store in pgvector
Question → embed query → cosine similarity top-k (whereVectorSimilarTo) → build context with citations
        → Gemini answers → SSE stream → Vue 3 (useChat)
```

Vectors live in Postgres via pgvector — `vector(768)` column with an HNSW index (`vector_cosine_ops`), queried natively with Laravel's `whereVectorSimilarTo()`. Chunking uses a fixed-size baseline with 20% overlap, designed to evolve: contextual headers → small-to-big (parent-chunk) retrieval → hybrid Postgres FTS + vector search with RRF.

## Roadmap

| Phase | Deliverable |
|---|---|
| 0 | Foundations (tooling, concepts) |
| 1 | Monorepo scaffold — `backend/` (Laravel 13 via Sail) + `frontend/` (Vue 3 + Vite) |
| 2 | Database + Sanctum auth (register → login → dashboard) |
| 3 | Ingestion pipeline — upload, 5 parsers, chunking service, queued jobs |
| 4 | Embeddings + pgvector (HNSW index, similarity search) |
| 5 | RAG chat + SSE streaming (citations, abort, regenerate) |
| 6 | Hardening — validation, policies, rate limiting, tests, contextual chunking |
| 7 | Docker Compose (separate frontend/backend services) |
| 8 | CI/CD + free hosting (GitHub Actions → Vercel + Render + Neon) |
| 9 | Production polish — Pulse, Horizon, small-to-big + hybrid retrieval |

Full details, decisions, and the chunking evolution ladder live in `OVERVIEW.md` (kept out of git).

## Status

**Phase 0 — in progress.** No application code yet; tooling verification comes first.

## Project Structure (planned)

```
docu-rag/
├── backend/    # Laravel 13 API (Sail, pgvector)
└── frontend/   # Vue 3 SPA (Vite, Tailwind, @ai-sdk/vue)
```