# Slice 5 Layered Architecture Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor Slice 5 so every HTTP flow follows FormRequest → Controller → DTO → UseCase → Service → RepositoryInterface → Repository → Model while preserving its current API contracts and authorization behavior.

**Architecture:** Replace the aggregate Slice5Controller/UseCase/Service with focused action flows grouped by consolidation, goals, tags, attachments, notifications and imports. Route-bound identifiers are converted into typed DTOs; persistence is moved behind focused repositories; multi-record writes remain transactional in their UseCases.

**Tech Stack:** PHP 8.5, Laravel 13.30.1, PHPUnit 12, Laravel Pint, Sanctum.

**Spec:** `docs/superpowers/specs/2026-09-07-financial-core-design.md`

## Global Constraints

- Preserve all existing `/api/v1` paths, status codes and Resource payloads.
- Preserve wallet membership authorization and the virtual consolidation model.
- Controllers do not import Models, query the database, validate manually or receive generic Request objects for domain input.
- UseCases receive DTOs and coordinate authorization, transactions and Services only.
- Services depend on RepositoryInterface contracts and contain atomic model operations only.
- Every changed endpoint has feature coverage for success, authorization and cross-wallet isolation.

---

### Task 1: Establish DTO and repository contracts

**Files:**
- Create: focused DTOs, repository interfaces and repository implementations under `app/DTO`, `app/Repositories`
- Test: `tests/Feature/Slice5Test.php`

- [ ] Write failing architectural and response-contract tests.
- [ ] Add typed input DTOs for consolidation, goal, tag, attachment, notification and import actions.
- [ ] Add persistence-only repository methods for each aggregate.
- [ ] Run the focused Slice 5 tests.

### Task 2: Split consolidation, goal and tag flows

**Files:**
- Create: focused UseCases and Services under `app/UseCases`, `app/Services`
- Modify: `app/Http/Controllers/Api/Slice5Controller.php`, `routes/api.php`
- Create: dedicated FormRequests where query/action input currently uses `Request`

- [ ] Route each action through its DTO and dedicated UseCase.
- [ ] Keep consolidation wallet selection atomic and membership-scoped.
- [ ] Keep goal contribution and tag attachment authorization wallet-scoped.
- [ ] Run `tests/Feature/Slice5Test.php`.

### Task 3: Split attachment and notification flows

**Files:**
- Create: focused attachment/notification UseCases, Services and Repository interfaces/implementations.
- Modify: Slice 5 controller and requests.

- [ ] Move attachable lookup and file persistence behind repositories/services.
- [ ] Preserve download/delete authorization and notification side effects.
- [ ] Run attachment and notification feature coverage.

### Task 4: Split import flow

**Files:**
- Create: import DTO, UseCase, Service and Repository interface/implementation.
- Modify: `app/Jobs/ProcessImportBatch.php`, Slice 5 controller and import requests.

- [ ] Keep queued import creation and batch status transitions atomic.
- [ ] Keep duplicate-row identity, invalid-row reporting and transaction semantics unchanged.
- [ ] Run import and transaction feature coverage.

### Task 5: Remove aggregate Slice 5 classes and verify

**Files:**
- Delete: `app/UseCases/Slice5UseCase.php`, `app/Services/Slice5Service.php` after all consumers migrate.
- Modify: `app/Http/Controllers/Api/Slice5Controller.php`, routes and tests.

- [ ] Verify no Slice 5 controller imports `App\Models` or `Illuminate\Http\Request`.
- [ ] Verify no Slice 5 UseCase imports Models or Repositories directly.
- [ ] Run PHPUnit, Pint, Composer validation and Vite build.
- [ ] Record MariaDB/browser validation boundaries separately.
