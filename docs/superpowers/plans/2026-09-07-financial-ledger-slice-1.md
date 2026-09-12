# Financial Ledger Slice 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar o primeiro fluxo financeiro utilizável: usuário autenticado cria uma carteira, cria uma conta, registra receita ou despesa e consulta o saldo isolado por carteira na web e na API.

**Architecture:** Implementar um monólito Laravel com React/Inertia para a web e Sanctum para autenticação de sessão e tokens da API. O backend seguirá Route → FormRequest → Controller → DTO → UseCase → Service → RepositoryInterface → Repository → Model → Resource; o frontend seguirá Page → Hook → Service → Components.

**Tech Stack:** PHP 8.5, Laravel 13.30.1, MariaDB, Sanctum, React, Inertia v3, Tailwind CSS, Flowbite React, PHPUnit e Laravel Pint. As versões de dependências devem ser confirmadas antes da instalação.

**Spec:** `docs/superpowers/specs/2026-09-07-financial-core-design.md`

## Global Constraints

- Toda entidade financeira de usuário deve possuir `wallet_id`; categorias globais são a única exceção e têm `wallet_id` nulo.
- Usuários acessam carteiras somente por `WalletMember`.
- Os papéis iniciais são `OWNER`, `EDITOR` e `VIEWER`.
- Valores monetários são inteiros em centavos e nunca `float`.
- `Transaction.amount` é sempre positivo; `effect` define `DEBIT`, `CREDIT` ou `NONE`.
- `INCOME` usa `effect = CREDIT`; despesa de conta usa `effect = DEBIT`; transferência só será implementada no Slice 2.
- Categorias globais são somente leitura para usuários.
- Controllers não acessam Models, Repositories, Eloquent, queries ou transações.
- Toda entrada de domínio usa FormRequest e DTO; toda resposta pública usa Resource.
- Toda alteração multi-entidade ocorre dentro de UseCase com transação.
- Formulários React usam `useForm` e modelos em `resources/js/Models`.
- Componentes não executam chamadas HTTP; Services concentram comunicação e Hooks concentram estado.
- Antes de construir a tela, usar `~/.codex/skills/frontend-design/` para definir tokens visuais e direção de interface.
- Não iniciar o Slice 2 até os testes de isolamento, saldo e permissões do Slice 1 estarem verdes.

---

### Task 1: Confirmar baseline e instalar a fundação web/API

**Files:**
- Modify: `composer.json`
- Modify: `package.json`
- Modify: `bootstrap/app.php`
- Create: `routes/api.php`
- Create: `config/sanctum.php` via publicação do pacote, se a instalação exigir
- Test: `tests/Feature/HealthAndAuthenticationTest.php`

**Interfaces:**
- Consumes: Laravel 13.30.1 e o esqueleto existente.
- Produces: Inertia/React disponível para a web, Sanctum disponível para `/api/v1`, `routes/api.php` carregado e respostas JSON para a API.

- [ ] **Step 1: Confirmar versões instaladas**

  Execute `composer show --direct`, `php artisan about` e leia `package.json`. Registrar no plano de execução as versões efetivas antes de escolher APIs de Sanctum, Inertia, React e Flowbite.

- [ ] **Step 2: Adicionar somente as dependências necessárias**

  Instalar Sanctum, Inertia server adapter, Inertia React adapter, React, React DOM, Flowbite React e o plugin de integração exigido pelo Vite, usando versões compatíveis com Laravel 13 e o `package.json` existente. Não instalar biblioteca de estado global, UI kit adicional ou TypeScript.

- [ ] **Step 3: Configurar o carregamento de API e Sanctum**

  Registrar `routes/api.php` com prefixo `/api/v1`, middleware de API e autenticação Sanctum. Manter a regra existente de respostas JSON para `api/*`.

- [ ] **Step 4: Escrever o teste de baseline**

  Cobrir que `/up` responde com sucesso, que uma rota protegida da API sem token responde `401` e que a rota pública de autenticação não exige sessão.

- [ ] **Step 5: Rodar a verificação**

  Execute `php artisan test --compact tests/Feature/HealthAndAuthenticationTest.php` e `npm run build`. Corrija apenas problemas introduzidos pela fundação.

### Task 2: Criar ownership de Wallet e WalletMember

**Files:**
- Create: `database/migrations/*_create_wallets_table.php`
- Create: `database/migrations/*_create_wallet_members_table.php`
- Create: `app/Enums/WalletMemberRole.php`
- Create: `app/Models/Wallet.php`
- Create: `app/Models/WalletMember.php`
- Create: `app/Repositories/WalletRepositoryInterface.php`
- Create: `app/Repositories/WalletRepository.php`
- Create: `app/Services/WalletService.php`
- Create: `app/DTO/WalletDTO.php`
- Create: `app/UseCases/Wallet/CreateWalletUseCase.php`
- Create: `app/Http/Requests/Wallet/CreateWalletRequest.php`
- Create: `app/Http/Resources/WalletResource.php`
- Create: `app/Http/Controllers/Api/WalletController.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/WalletOwnershipTest.php`

**Interfaces:**
- Consumes: authenticated `User` and the API middleware from Task 1.
- Produces: `POST /api/v1/wallets`, `GET /api/v1/wallets`, `WalletDTO::fromArray()`, `WalletService` backed by `WalletRepositoryInterface`, and an owner membership created atomically with each wallet.

- [ ] **Step 1: Write failing ownership tests**

  Test that wallet creation creates exactly one `OWNER` membership for the authenticated user, listing returns only wallets where the user is a member, a user cannot see another user's wallet, duplicate membership is rejected by the database, and a wallet cannot remove or demote its last owner.

- [ ] **Step 2: Create migrations and enum**

  Add UUID or ULID consistently with the existing application convention after inspecting the current migration style. Add foreign keys, indexes for `(wallet_id, user_id)`, unique membership constraint, role values, timestamps and `joined_at`.

- [ ] **Step 3: Implement models and relationships**

  Define `User::walletMemberships()`, `Wallet::members()`, `Wallet::ownerMemberships()` and guarded mass assignment according to the Laravel 13 model conventions already present.

- [ ] **Step 4: Implement DTO, repository, service and use case**

  `CreateWalletUseCase::execute(WalletDTO $walletDTO, User $user): Wallet` must create the wallet and owner membership in one transaction. The controller only validates, creates the DTO, invokes one use case and returns `WalletResource`.

- [ ] **Step 5: Add authorization boundary**

  Create a policy or injectable membership authorization service that resolves the current user's membership and enforces owner/editor/viewer permissions without querying from the controller.

- [ ] **Step 6: Run focused tests and migrations**

  Execute `php artisan migrate:fresh --env=testing` only in the configured test database, then `php artisan test --compact tests/Feature/WalletOwnershipTest.php`. Run Pint after PHP changes.

### Task 3: Implement Account and Category foundations

**Files:**
- Create: `database/migrations/*_create_accounts_table.php`
- Create: `database/migrations/*_create_categories_table.php`
- Create: `app/Enums/AccountType.php`
- Create: `app/Enums/TransactionType.php`
- Create: `app/Enums/TransactionEffect.php`
- Create: `app/Models/Account.php`
- Create: `app/Models/Category.php`
- Create: `app/Repositories/AccountRepositoryInterface.php`
- Create: `app/Repositories/AccountRepository.php`
- Create: `app/Repositories/CategoryRepositoryInterface.php`
- Create: `app/Repositories/CategoryRepository.php`
- Create: `app/Services/AccountService.php`
- Create: `app/Services/CategoryService.php`
- Create: `app/DTO/AccountDTO.php`
- Create: `app/DTO/CategoryDTO.php`
- Create: `app/UseCases/Account/CreateAccountUseCase.php`
- Create: `app/UseCases/Category/CreateCategoryUseCase.php`
- Create: `app/Http/Requests/Account/CreateAccountRequest.php`
- Create: `app/Http/Requests/Category/CreateCategoryRequest.php`
- Create: `app/Http/Resources/AccountResource.php`
- Create: `app/Http/Resources/CategoryResource.php`
- Create: `app/Http/Controllers/Api/AccountController.php`
- Create: `app/Http/Controllers/Api/CategoryController.php`
- Test: `tests/Feature/AccountAndCategoryTest.php`

**Interfaces:**
- Consumes: `Wallet` membership authorization.
- Produces: account/category creation endpoints, typed enums, wallet-scoped records and read-only system categories.

- [ ] **Step 1: Write failing tests**

  Test editor creation succeeds, viewer creation returns `403`, records cannot be created in another wallet, a child category must match its parent's type, and a global category cannot be edited or deleted by a user.

- [ ] **Step 2: Create migrations and enums**

  Store balances and limits as signed integer cents. Add `wallet_id`, optional `owner_wallet_member_id`, account type, active flag, category hierarchy and indexes for wallet/type/active.

- [ ] **Step 3: Implement repository-backed services**

  Services expose typed operations such as `create(AccountDTO $accountDTO): Account` and `create(CategoryDTO $categoryDTO): Category`; repository implementations contain only persistence.

- [ ] **Step 4: Implement use cases and resources**

  Use cases verify membership through the authorization boundary, create records atomically where ownership and resource creation are coupled, and return Resources from controllers.

- [ ] **Step 5: Run focused verification**

  Execute `php artisan test --compact tests/Feature/AccountAndCategoryTest.php` and `vendor/bin/pint --dirty --format agent`.

### Task 4: Implement the central Transaction ledger and derived balance

**Files:**
- Create: `database/migrations/*_create_transactions_table.php`
- Create: `app/Enums/FinancialInstrumentType.php`
- Create: `app/Enums/TransactionStatus.php`
- Create: `app/Enums/PaymentChannel.php`
- Create: `app/Models/Transaction.php`
- Create: `app/Repositories/TransactionRepositoryInterface.php`
- Create: `app/Repositories/TransactionRepository.php`
- Create: `app/Services/TransactionService.php`
- Create: `app/DTO/TransactionDTO.php`
- Create: `app/UseCases/Transaction/CreateTransactionUseCase.php`
- Create: `app/UseCases/Transaction/MonthlySummaryUseCase.php`
- Create: `app/Http/Requests/Transaction/CreateTransactionRequest.php`
- Create: `app/Http/Requests/Transaction/MonthlySummaryRequest.php`
- Create: `app/Http/Resources/TransactionResource.php`
- Create: `app/Http/Resources/MonthlySummaryResource.php`
- Create: `app/Http/Controllers/Api/TransactionController.php`
- Create: `app/Http/Controllers/Api/MonthlySummaryController.php`
- Test: `tests/Feature/TransactionLedgerTest.php`
- Test: `tests/Unit/AccountBalanceCalculatorTest.php`

**Interfaces:**
- Consumes: wallet, account and category repositories/services.
- Produces: `POST /api/v1/transactions`, `GET /api/v1/transactions`, monthly summary, and a balance calculation based on `initial_balance + CREDIT - DEBIT`.

- [ ] **Step 1: Write failing ledger tests**

  Cover income credit, account expense debit, projected expense with `effect = NONE`, cancellation exclusion, category compatibility, wallet isolation, and monthly separation between `actual_expenses` (`POSTED`) and `forecast_expenses` (`POSTED + PROJECTED`).

- [ ] **Step 2: Create schema and casts**

  Add `wallet_id`, nullable `account_id`, nullable `category_id`, amount in cents, transaction/effect/instrument/status enums, three dates, optional payment channel, notes and audit member IDs. Include nullable `reversal_of_transaction_id` now, even though the reversal use case belongs to Slice 2.

- [ ] **Step 3: Implement transaction creation rules**

  `CreateTransactionUseCase::execute(TransactionDTO $transactionDTO): Transaction` must validate the target account and category through services, derive the allowed effect from the transaction type and instrument, enforce editor access, and persist atomically. It must reject a credit-card instrument in Slice 1 because credit cards are not available yet.

- [ ] **Step 4: Implement balance and monthly summary**

  Aggregate only transactions with `account_id` matching the account and `status != CANCELLED`; apply `effect` rather than inspecting negative amounts or installment-specific conditions. The summary must return actual and forecast totals separately.

- [ ] **Step 5: Run focused tests**

  Execute `php artisan test --compact tests/Feature/TransactionLedgerTest.php tests/Unit/AccountBalanceCalculatorTest.php`, then run Pint and `git diff --check` once repository metadata is available.

### Task 5: Build the first web vertical slice

**Files:**
- Modify: `resources/js/app.js`
- Create: `resources/js/Models/Wallet.js`
- Create: `resources/js/Models/Account.js`
- Create: `resources/js/Models/Transaction.js`
- Create: `resources/js/Pages/Financial/Dashboard.jsx`
- Create: `resources/js/Pages/Financial/Hooks/useDashboard.js`
- Create: `resources/js/Pages/Financial/Components/BalanceSummary.jsx`
- Create: `resources/js/Pages/Financial/Components/TransactionForm.jsx`
- Create: `resources/js/Pages/Financial/Components/TransactionList.jsx`
- Create: `resources/js/Services/FinancialService.js`
- Modify: `resources/css/app.css`
- Create: `resources/views/app.blade.php`
- Modify: `vite.config.js`
- Modify: `routes/web.php`
- Test: `tests/Feature/FinancialDashboardTest.php`

**Interfaces:**
- Consumes: Inertia page props/resources and the API or named web actions from Tasks 2–4.
- Produces: an authenticated dashboard with wallet/account selection, balance, monthly actual/forecast summary, and a form for income/expense.

- [ ] **Step 1: Define the visual direction before coding**

  Use `~/.codex/skills/frontend-design/` to choose a financial-management-specific palette, typography roles, layout rhythm and one restrained signature element. Check the available Flowbite React components and use them where they fit the direction.

- [ ] **Step 2: Write the page contract test**

  Assert that an authenticated user receives the dashboard, an unauthenticated user is redirected to login, and a user cannot render data from a wallet where they are not a member.

- [ ] **Step 3: Implement the page composition**

  Keep `Dashboard.jsx` limited to composing the hook and presentational components. Put filters, selected wallet/account, form state, loading and submission behavior in `useDashboard.js`.

- [ ] **Step 4: Implement forms and service calls**

  Use `useForm` with `Wallet`, `Account` and `Transaction` model defaults. Put HTTP/Inertia calls in `FinancialService.js`; use Flowbite React components and visible validation states.

- [ ] **Step 5: Verify the web slice**

  Run `php artisan test --compact tests/Feature/FinancialDashboardTest.php`, `npm run build`, `vendor/bin/pint --dirty --format agent` and inspect the rendered dashboard at the resolved local URL. Compilation alone is not sufficient evidence for the visual flow.

### Task 6: Final Slice 1 review and handoff

**Files:**
- Modify: only files identified by the preceding verification failures
- Test: all Slice 1 tests

**Interfaces:**
- Consumes: all contracts from Tasks 1–5.
- Produces: a working, tenant-isolated minimum ledger ready for the Merchant/Transfer Slice 2 plan.

- [ ] **Step 1: Run the complete focused suite**

  Execute `php artisan test --compact tests/Feature/HealthAndAuthenticationTest.php tests/Feature/WalletOwnershipTest.php tests/Feature/AccountAndCategoryTest.php tests/Feature/TransactionLedgerTest.php tests/Feature/FinancialDashboardTest.php tests/Unit/AccountBalanceCalculatorTest.php`.

- [ ] **Step 2: Run project checks**

  Execute `vendor/bin/pint --dirty --format agent`, `npm run build` and `git diff --check` when Git metadata is repaired.

- [ ] **Step 3: Review against the specification**

  Confirm every Slice 1 invariant is covered by a test, no controller contains domain logic, no repository contains business rules, no amount is represented as a floating point value, and no cross-wallet record is exposed.

- [ ] **Step 4: Record blockers honestly**

  Report database, package-installation, browser or Git-environment failures separately from application failures. Do not claim the mobile/API contract is integrated until an authenticated API request has been exercised.
