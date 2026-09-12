# Financial Movement Slice 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the approved ledger into daily-use financial movement management with wallet-scoped merchants, atomic account transfers and explicit transaction reversals.

**Architecture:** Preserve the existing Laravel layered flow and wallet membership boundary. Merchant creation/listing is wallet-scoped; transfers and reversals are dedicated UseCases that create related ledger rows atomically while keeping `Transaction` as the only financial movement record.

**Tech Stack:** PHP 8.5, Laravel 13.30.1, MariaDB, Sanctum, React, Inertia v3, Tailwind CSS, Flowbite React, PHPUnit and Laravel Pint.

**Spec:** `docs/superpowers/specs/2026-09-07-financial-core-design.md`

## Global Constraints

- Every user-owned financial record has `wallet_id` and is accessed through `WalletMember`.
- Initial roles remain `OWNER`, `EDITOR` and `VIEWER`.
- Money is integer cents and always positive; no negative amount represents an estorno or transfer leg.
- Existing account ledger semantics remain: `INCOME/CREDIT`, `EXPENSE/DEBIT`, `ACCOUNT`.
- A transfer is two `TRANSFER` transactions with the same `transfer_group_id`: one `DEBIT` and one `CREDIT`, both `ACCOUNT`, same wallet, distinct accounts.
- An invoice payment is not implemented in this slice and must not be simulated as an account-to-card transfer.
- A reversal is a new positive transaction linked through `reversal_of_transaction_id`; the original transaction remains immutable and the reversal is excluded from repeated reversal by domain rule.
- A reversal of a posted account income creates a posted account expense; a reversal of a posted account expense creates a posted account income. A projected original is reversed as projected.
- Merchant is wallet-scoped, with normalized searchable name, and transaction `merchant_id` is nullable.
- Global categories remain system-managed/read-only; wallet/category same-wallet-or-global validation stays centralized.
- Controllers use FormRequests, DTOs, one UseCase and Resources only. Services depend on repository interfaces; repositories contain persistence only.
- Multi-entity writes use a transaction in the UseCase and lock the affected rows before deriving or writing related rows.
- API remains under `/api/v1` and uses Sanctum; web uses the existing session/stateful Sanctum path.
- New frontend work, if needed, follows Page → Hook → Service → Components, Flowbite React, `useForm`, accessible states and the existing ledger visual direction.

---

### Task 1: Add Merchant and merchant-aware transaction contracts

**Files:**
- Create: `database/migrations/*_create_merchants_table.php`
- Create: `database/migrations/*_add_merchant_id_to_transactions_table.php`
- Create: `app/Models/Merchant.php`
- Create: `app/Repositories/MerchantRepositoryInterface.php`
- Create: `app/Repositories/MerchantRepository.php`
- Create: `app/Services/MerchantService.php`
- Create: `app/DTO/MerchantDTO.php`
- Create: `app/UseCases/Merchant/CreateMerchantUseCase.php`
- Create: `app/UseCases/Merchant/ListMerchantUseCase.php`
- Create: `app/Http/Requests/Merchant/CreateMerchantRequest.php`
- Create: `app/Http/Requests/Merchant/ListMerchantRequest.php`
- Create: `app/Http/Resources/MerchantResource.php`
- Create: `app/Http/Controllers/Api/MerchantController.php`
- Modify: `app/Models/Transaction.php`
- Modify: `app/DTO/TransactionDTO.php`
- Modify: `app/Http/Requests/Transaction/CreateTransactionRequest.php`
- Modify: `app/Http/Resources/TransactionResource.php`
- Modify: transaction creation/list DTOs and repositories for `merchant_id` and merchant filtering
- Modify: `routes/api.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/MerchantTest.php`
- Test: `tests/Feature/TransactionLedgerTest.php`

**Interfaces:**
- Consumes: wallet membership authorization and existing account transaction contracts.
- Produces: `POST /api/v1/merchants`, `GET /api/v1/merchants?wallet_id=...`, merchant-aware transaction create/list and a nullable transaction `merchant_id`.

- [ ] **Step 1: Write failing merchant and transaction association tests**

  Cover editor creation, viewer denial, unauthenticated rejection, cross-wallet isolation, normalized name/search behavior, merchant list isolation, transaction creation with a same-wallet merchant, rejection of a merchant from another wallet, and merchant filter on transaction listing.

- [ ] **Step 2: Create wallet-scoped merchant schema and model**

  Add `wallet_id`, `name`, `normalized_name`, `active`, timestamps, unique `(wallet_id, normalized_name)`, and a wallet-aware key suitable for the transaction foreign key. Add `merchant_id` to transactions as nullable with a same-wallet composite relationship where MariaDB permits it.

- [ ] **Step 3: Implement repository-backed merchant flow**

  Add typed DTO, repository interface/implementation, service, create/list use cases, FormRequests, Resource and thin controller. Normalize names in the domain service before persistence, not in the controller.

- [ ] **Step 4: Extend transaction transport and persistence**

  Add `merchant_id` to the transaction DTO/model/resource/request/filter contract. Validate merchant existence and same-wallet ownership through `MerchantService` and the existing wallet authorization boundary.

- [ ] **Step 5: Run focused verification**

  Execute `php artisan test --compact tests/Feature/MerchantTest.php tests/Feature/TransactionLedgerTest.php`, the full suite and `vendor/bin/pint --format agent`. MariaDB remains an explicit unverified environment until available.

### Task 2: Implement atomic transfers between accounts

**Files:**
- Create: `app/DTO/AccountTransferDTO.php`
- Create: `app/UseCases/Transaction/CreateAccountTransferUseCase.php`
- Create: `app/Http/Requests/Transaction/CreateAccountTransferRequest.php`
- Create: `app/Http/Resources/AccountTransferResource.php`
- Create: `app/Http/Controllers/Api/AccountTransferController.php`
- Modify: `app/Repositories/TransactionRepositoryInterface.php`
- Modify: `app/Repositories/TransactionRepository.php`
- Modify: `app/Services/TransactionService.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/AccountTransferTest.php`

**Interfaces:**
- Consumes: wallet/account authorization, `Transaction` schema and repository.
- Produces: `POST /api/v1/account-transfers`, returning both transfer legs and their `transfer_group_id`.

- [ ] **Step 1: Write failing transfer tests**

  Cover editor success with exactly two rows, debit/credit effects, same group ID, distinct same-wallet accounts, balance changes, viewer/guest denial, cross-wallet rejection, same-account rejection, positive amount validation and atomic rollback when either account is invalid.

- [ ] **Step 2: Implement typed transfer DTO and request**

  Accept `wallet_id`, `from_account_id`, `to_account_id`, positive `amount`, `transaction_date`, `competence_date`, optional `notes` and optional `payment_channel`. Do not accept caller-controlled effects, instrument type, status or audit member IDs; derive those in the UseCase.

- [ ] **Step 3: Implement atomic use case**

  Authorize `EDITOR`/`OWNER`, load both accounts through services, lock them in deterministic ID order, validate same wallet and distinct IDs, generate one group identifier and persist debit/credit transactions with the authenticated member as audit actor inside `DB::transaction()`.

- [ ] **Step 4: Return an explicit transfer resource**

  Return the two transaction resources and group identifier without exposing a model directly. Keep repository methods persistence-only.

- [ ] **Step 5: Run focused verification**

  Execute `php artisan test --compact tests/Feature/AccountTransferTest.php tests/Feature/TransactionLedgerTest.php tests/Unit/AccountBalanceCalculatorTest.php`, the full suite and Pint.

### Task 3: Implement explicit transaction reversals

**Files:**
- Create: `app/DTO/TransactionReversalDTO.php`
- Create: `app/UseCases/Transaction/ReverseTransactionUseCase.php`
- Create: `app/Http/Requests/Transaction/ReverseTransactionRequest.php`
- Modify: `app/Repositories/TransactionRepositoryInterface.php`
- Modify: `app/Repositories/TransactionRepository.php`
- Modify: `app/Services/TransactionService.php`
- Modify: `routes/api.php`
- Modify: `app/Http/Resources/TransactionResource.php`
- Test: `tests/Feature/TransactionReversalTest.php`

**Interfaces:**
- Consumes: transaction lookup/locking, wallet membership authorization and existing transaction semantics.
- Produces: `POST /api/v1/transactions/{transaction}/reversal`, returning the new reversal transaction.

- [ ] **Step 1: Write failing reversal tests**

  Cover posted expense reversal, posted income reversal, projected reversal, viewer/guest denial, cross-wallet access denial, cancelled/original reversal rejection, repeated reversal rejection, amount/effect inversion, original immutability, audit actor and balance/summary consequences.

- [ ] **Step 2: Implement reversal DTO and request**

  Accept only optional `transaction_date`, `competence_date` and `notes`; derive wallet, account, category, amount, type-compatible inverse effect, instrument and status from the locked original. Do not allow a caller to change the amount or account.

- [ ] **Step 3: Implement locked atomic use case**

  Resolve the original inside the authorized wallet, lock it and check it is not cancelled and has no existing reversal. Create a positive inverse transaction linked by `reversal_of_transaction_id`, preserving the original's financial instrument and account while assigning the current member as creator/updater.

- [ ] **Step 4: Add explicit reversal resource route**

  Use the route parameter only as an identifier; the controller passes a typed DTO and ID to one use case. Return a `TransactionResource`.

- [ ] **Step 5: Run focused verification**

  Execute `php artisan test --compact tests/Feature/TransactionReversalTest.php tests/Feature/TransactionLedgerTest.php tests/Unit/AccountBalanceCalculatorTest.php`, the full suite and Pint.

### Task 4: Extend the web dashboard for merchants, transfers and reversals

**Files:**
- Modify: `resources/js/Models/Transaction.js`
- Modify: `resources/js/Services/FinancialService.js`
- Modify: `resources/js/Pages/Financial/Hooks/useDashboard.js`
- Modify: `resources/js/Pages/Financial/Components/TransactionForm.jsx`
- Modify: `resources/js/Pages/Financial/Components/TransactionList.jsx`
- Modify: `resources/js/Pages/Financial/Dashboard.jsx`
- Modify: `tests/Feature/FinancialDashboardTest.php`

**Interfaces:**
- Consumes: merchant, transfer and reversal API contracts from Tasks 1–3.
- Produces: merchant selection in the transaction form, transfer action, reversal action for eligible rows, and refreshed summary/list state.

- [ ] **Step 1: Write failing dashboard contract tests**

  Cover merchant data in the page flow, transfer/reversal action visibility by transaction state, and refresh after a successful action. Keep backend feature tests as the primary proof of financial behavior.

- [ ] **Step 2: Extend service and hook actions**

  Add merchant loading, merchant-aware create payloads, transfer submission and reversal submission. Reuse the existing AbortController/request identity guards and clear stale errors after successful reloads.

- [ ] **Step 3: Extend presentational components**

  Add accessible merchant field, transfer form/action and reversal confirmation. Keep Flowbite React components and the existing ledger visual direction; do not put HTTP or business rules in components.

- [ ] **Step 4: Verify web behavior**

  Run the focused dashboard test, full PHPUnit suite, `npm run build` and Pint. Browser visual/runtime remains a separate evidence boundary if no browser is available.

### Task 5: Slice 2 final verification

**Files:**
- Modify: only files identified by the final review
- Test: all Slice 2 tests plus the complete existing suite

**Interfaces:**
- Consumes: all contracts from Tasks 1–4.
- Produces: a tested daily-movement slice ready for credit-card modeling.

- [ ] **Step 1: Run the complete suite**

  Execute `php artisan test --compact` and record tests/assertions, then run `npm run build`, `vendor/bin/pint --format agent`, `composer validate --strict` and route listing.

- [ ] **Step 2: Review invariants**

  Confirm wallet isolation, exact two-leg transfers, effect-driven balances, immutable originals, single reversals, positive amounts, normalized merchants and no controller/repository business logic.

- [ ] **Step 3: Record environmental boundaries**

  Report MariaDB, browser and Git limitations separately. Do not claim schema compatibility or visual correctness without their respective evidence.
