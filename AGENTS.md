<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

===

# Laravel Architecture Guidelines

Este projeto segue uma arquitetura baseada em **Controller → UseCase → Service → Repository**, utilizando DTOs para transporte de dados e Resources para serialização das respostas.

Todo código novo deve seguir obrigatoriamente esta arquitetura.

---

## Estrutura de Pastas

```
app/
├── DTO/
│   └── UserDTO.php
│
├── Services/
│   └── UserService.php
│
├── Repositories/
│   ├── UserRepository.php
│   └── UserRepositoryInterface.php
│
├── UseCases/
│   └── User/
│       ├── CreateUserUseCase.php
│       ├── UpdateUserUseCase.php
│       ├── DeleteUserUseCase.php
│       ├── ListUserUseCase.php
│       └── FindUserUseCase.php
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
└── Models/
```

## Fluxo da aplicação

Todo fluxo deve seguir exatamente esta sequência:

```
Route
    ↓
FormRequest
    ↓
Controller
    ↓
DTO
    ↓
UseCase
    ↓
Service(s)
    ↓
RepositoryInterface
    ↓
Repository
    ↓
Model (Eloquent)
```

A resposta retorna no caminho inverso:

```
Model
    ↓
Service
    ↓
UseCase
    ↓
Controller
    ↓
Resource
    ↓
JSON Response
```

Nunca pule uma camada.

---

### Controller

**Responsabilidade:**
- Receber a requisição
- Receber um FormRequest
- Instanciar DTOs
- Chamar um único UseCase
- Retornar Resources ou JsonResponse

Controllers devem ser extremamente pequenos.

**Controllers NÃO podem:**
- acessar Models
- acessar Repository
- executar regras de negócio
- executar queries
- utilizar Eloquent
- iniciar transações

Exemplo:
```php
public function store(
    UserRequest $request,
    CreateUserUseCase $useCase
) {
    $user = $useCase->execute(
        UserDTO::fromArray($request->validated())
    );

    return new UserResource($user);
}
```

---

### FormRequest

Toda entrada deve possuir seu próprio FormRequest.

Nunca utilizar:
```php
$request->all()
```

Sempre utilizar:
```php
$request->validated()
```

Toda validação pertence ao FormRequest.
- Nunca validar dentro do Controller.
- Nunca validar dentro do Service.
- Nunca validar dentro do UseCase.

---

### DTO

DTOs são responsáveis pelo transporte de dados entre as camadas.

- Todo UseCase deve receber um DTO.
- DTOs devem possuir `fromArray()` e `toArray()`.
- Opcionalmente `withExtraInfos()`.
- DTOs devem ser preferencialmente imutáveis.
- Nunca passar Request para o UseCase.
- Nunca passar arrays quando existir DTO.

---

### UseCase

Cada UseCase representa uma única ação do sistema. Ex: `CreateUserUseCase`, `ApproveInvoiceUseCase`, `CancelContractUseCase`.

**Responsabilidades:**
- coordenar Services
- iniciar transações
- executar regras de negócio
- lançar exceções de domínio
- devolver resultado

**UseCases NÃO podem:**
- acessar Repository diretamente
- utilizar Eloquent

UseCases conversam apenas com Services. Um UseCase pode utilizar quantos Services forem necessários.

---

### Service

O Service representa operações atômicas relacionadas ao seu Model. Ex: `UserService`, `InvoiceService`, `ContractService`.

**Responsabilidades:**
- operações CRUD
- pequenas regras de negócio
- encapsular acesso aos Repositories

Services podem chamar apenas Repositories.
Services NÃO chamam outros UseCases.
Services NÃO recebem Request.
Services recebem DTOs ou parâmetros simples.

---

### Repository Interface

Todo Repository deve possuir Interface. Ex: `UserRepositoryInterface`.
O Service depende sempre da Interface, nunca da implementação.

---

### Repository

Responsável exclusivamente pelo acesso ao banco. Pode utilizar Eloquent ou Query Builder.
Não pode possuir regra de negócio.

**Métodos típicos:** `create()`, `update()`, `delete()`, `find()`, `findById()`, `paginate()`, `all()`, `first()`, `exists()`, `where()`, `with()`.

Nunca chamar Service, UseCase ou Controller.

---

### Model

Os Models representam as entidades Eloquent. Responsabilidades: relacionamentos, casts, scopes, atributos.
Evitar regras de negócio dentro dos Models.

---

### Resource

Toda resposta pública deve utilizar Resources.
Nunca retornar Models diretamente.

Exemplo:
```php
return new UserResource($user);
// ou
return UserResource::collection($users);
```

Resources são responsáveis apenas pela serialização. Nunca executar consultas ou regras de negócio.

---

### Transactions

Sempre que um UseCase alterar múltiplas entidades, utilizar `DB::transaction()`. A transação pertence ao UseCase — nunca ao Controller ou Repository.

---

### Exceptions

Regras de negócio devem lançar Exceptions específicas. Ex: `ContractNotFoundException`, `InvoiceAlreadyApprovedException`, `UserInactiveException`.
Nunca lançar Exception genérica quando existir uma exceção de domínio apropriada.

---

### Dependency Injection

Toda dependência deve ser injetada via construtor.
```php
public function __construct(
    private UserService $userService
) {}
```

Nunca utilizar `new UserService()`.

---

### Regras Gerais

Sempre utilizar: DTO, FormRequest, Resource, RepositoryInterface, Repository, Services, UseCases.
Nunca pular camadas.

---

### Fluxo Correto (Resumo)

Request:
```
Route → FormRequest → Controller → DTO → UseCase → Service → RepositoryInterface → Repository → Model
```

Response:
```
Model → Service → UseCase → Controller → Resource → Response
```

Este fluxo é obrigatório para toda funcionalidade implementada no projeto.

===

# Frontend Architecture (React + Inertia v3)

## Design tokens oficiais da aplicação

Toda interface nova ou refatorada deve utilizar a paleta Orbital abaixo. Não substituir essas cores por paletas locais sem aprovação explícita.

| Papel | Nome | Hex | Uso |
|---|---|---|---|
| Primary | Azul Orbital | `#123B8F` | botões principais, links, estados ativos, gráficos |
| Primary Dark | Azul Profundo | `#0B2454` | sidebar, header escuro, textos de destaque |
| Primary Light | Azul Celeste | `#E8F0FF` | backgrounds selecionados, badges, hover |
| Accent | Dourado Orbital | `#F4B321` | destaques, CTA secundário, metas, elementos premium |
| Accent Dark | Âmbar | `#C98A00` | hover e contraste do dourado |
| Background | Branco Azulado | `#F8FAFC` | fundo geral |
| Surface | Branco | `#FFFFFF` | cards, modais, tabelas |
| Surface Dark | Azul Noturno | `#08162F` | dark mode |
| Text Primary | Azul Quase Preto | `#172033` | texto principal |
| Text Secondary | Cinza Azulado | `#667085` | labels, descrições |
| Border | Cinza Frio | `#E4E7EC` | divisórias, inputs, cards |

Ao usar Tailwind, preferir tokens semânticos derivados dessa paleta e manter contraste, foco de teclado, responsividade e suporte a `prefers-reduced-motion`.

## Stack e design do frontend

O frontend web deve utilizar React com Inertia v3, Tailwind CSS e Flowbite React para os componentes visuais compartilhados. Antes de construir ou redesenhar telas, utilizar a skill `~/.codex/skills/frontend-design/` para definir uma direção visual intencional, incluindo paleta, tipografia, layout, responsividade e um elemento de identidade do produto.

Ao implementar a interface, consultar a versão instalada no `package.json`, reutilizar componentes do Flowbite React quando forem adequados e preservar a direção visual definida pela skill. O uso do Flowbite React não elimina a necessidade de avaliar composição, hierarquia, acessibilidade, estados vazios, foco de teclado, reduced motion e comportamento responsivo para a futura experiência mobile.

## Estrutura de Diretórios

```
resources/js/
│
├── Components/
│   ├── DataTable/
│   ├── Form/
│   ├── Modal/
│   ├── Button/
│   └── ...
│
├── Pages/
│   ├── Users/
│   │   ├── Index.jsx
│   │   ├── Create.jsx
│   │   ├── Edit.jsx
│   │   ├── Show.jsx
│   │   │
│   │   ├── Components/
│   │   │   ├── UserForm.jsx
│   │   │   ├── UserTable.jsx
│   │   │   └── ...
│   │   │
│   │   └── Hooks/
│   │       ├── useIndex.js
│   │       ├── useCreate.js
│   │       ├── useEdit.js
│   │       └── useShow.js
│   │
│   └── Dashboard/
│
├── Services/
│   ├── UserService.js
│   ├── AuthService.js
│   └── ...
│
├── Hooks/
│   ├── useDebounce.js
│   ├── usePagination.js
│   ├── useToast.js
│   ├── usePermissions.js
│   └── ...
│
├── Layouts/
├── Contexts/
├── Utils/
├── Constants/
├── Theme/
└── Types/
```

## Fluxo da aplicação

```
Route → Page → Page Hook → Service → Laravel
```

Retorno: `Laravel → Service → Page Hook → Page`

## Responsabilidades

| Camada | Faz | Não faz |
|---|---|---|
| **Components** | Renderização visual, estado interno de UI | ❌ API/axios/router ❌ regras de negócio |
| **Pages** | Montar tela, importar componentes, chamar hook | ❌ lógica pesada ❌ useEffect enormes ❌ chamadas HTTP |
| **Page Hooks** | Estados, filtros, paginação, modais, formulários, chamar Services | ❌ renderizar componentes |
| **Services** | Comunicação com backend (router Inertia, axios, fetch, Wayfinder) | ❌ renderizar JSX ❌ acessar DOM |
| **Global Hooks** | Lógica reutilizável (useDebounce, useToast, etc.) | — |

## Princípio

> Quanto mais próximo da pasta `Pages`, mais visual é o código. Quanto mais distante, mais lógica ele concentra.

Pages devem ter 30–80 linhas. Toda complexidade fica no Hook correspondente.

### Regra obrigatória para novos módulos e refatorações

Toda tela React de domínio deve seguir o padrão de `resources/js/Pages/Ecs`:

- `Index.jsx` deve apenas compor a tela, conectar o Hook e renderizar componentes.
- Estado, carregamento, filtros, seleção, paginação, abertura/fechamento de modais e ações de CRUD devem ficar em `Hooks/useIndex.js` ou em Hooks específicos do fluxo.
- Cada modal deve possuir seu próprio arquivo em `Components/*Modal.jsx`; não criar funções `Modal` locais dentro de `Index.jsx`.
- Componentes em `Components/` devem cuidar somente da apresentação e receber dados e callbacks por propriedades.
- Services devem concentrar exclusivamente a comunicação com o backend.
- Ao criar ou refatorar uma tela, preservar a separação `Page → Hook → Service` e validar que a página não contém chamadas HTTP, regras de negócio ou estado complexo.

## Organização por domínio

Cada domínio (ex: Users, Orders, Products) fica isolado em sua pasta dentro de `Pages/`, com seus próprios `Components/` e `Hooks/`.

## Estratégias de Design e Formulários

1. **Componentes de entrada obrigatórios:** todo formulário deve usar os componentes compartilhados de `resources/js/Components/Inputs`, importados pelo agregador `@/Components/Inputs`. Reutilize `Validation`, `Flatpickr`, `Number`, `Select`, `Textarea`, `FileInput` e demais componentes disponíveis antes de criar controles HTML nativos.

2. **Formulários Inertia:** todo formulário React deve usar `useForm` de `@inertiajs/react` para estado, processamento, erros e submissão. Não criar estado paralelo com `useState` para os dados principais do formulário.

3. **Modelos de formulário:** os valores iniciais devem vir de um modelo em `resources/js/Models`. O padrão obrigatório é:

   ```jsx
   import { useForm } from '@inertiajs/react';
   import EstruturaControle from '@/Models/EstruturaControle';

   const { data, setData, processing } = useForm(EstruturaControle);
   ```

   Cada domínio deve criar seu próprio modelo JavaScript, usando nomes PascalCase e contendo somente os valores iniciais do formulário. Os componentes `Inputs` devem receber `data`, `setData` e `errors` desse `useForm`.

## Tabelas e carregamento de dados

Toda tabela de dados do sistema deve obrigatoriamente utilizar:

- o componente React compartilhado `resources/js/Components/DataTable` para renderização;
- uma classe backend em `app/DataTables` que estenda `app/DataTables/BaseDataTable.php`;
- endpoint paginado, filtrável e ordenável, carregando os registros sob demanda;
- colunas declaradas no `DataTable`, sem implementar tabelas HTML locais para dados persistidos.

Tabelas pivot ou relatórios com colunas dinâmicas também devem possuir uma implementação backend baseada em `BaseDataTable`, estendendo o componente compartilhado quando necessário para preservar a estrutura visual específica.

===

# Backend Architecture Standards — Project Rules

Estas regras foram definidas pelo projeto e são obrigatórias para todo código novo e para toda refatoração do backend.

## Enums

- Todo arquivo PHP que declarar um `enum` deve ficar em `app/Enums`.
- O namespace de Enums deve ser `App\Enums`.
- Enums existentes fora de `app/Enums` devem ser movidos para essa pasta e todas as referências devem ser atualizadas.

## Controllers

- Controllers não processam informações.
- A única responsabilidade do Controller é receber a requisição, encaminhar os dados para um Service ou UseCase e despachar uma Response ou View.
- Controllers não podem conter queries, regras de negócio, transações, acesso direto a Models, manipulação de arquivos, validação manual ou transformação de payloads.
- Toda dependência de Service ou UseCase deve ser recebida por injeção de dependência.

## Requests e Responses

- Toda entrada HTTP deve ser validada por um FormRequest próprio.
- É proibido usar `Request` genérico para entrada de domínio quando um FormRequest puder ser criado.
- É proibido usar `$request->all()`; utilizar exclusivamente `$request->validated()`.
- Toda resposta de domínio deve utilizar um `JsonResource` ou `ResourceCollection` próprio.
- Respostas de sucesso, erro e ações especiais devem manter um contrato explícito e consistente com o Resource correspondente.

## DTOs e fluxo obrigatório

- Toda informação que atravessar camadas de domínio deve ser transportada por um DTO tipado.
- Controllers devem converter dados validados em DTOs antes de chamar Services ou UseCases.
- O fluxo obrigatório é:

  `Route → FormRequest → Controller → DTO → Service/UseCase → RepositoryInterface → Repository → Model`

- Services e UseCases não devem receber objetos Request.
- Repositories não devem conter regras de negócio, validação ou serialização.
- Services encapsulam operações atômicas e dependem de interfaces de Repository.
- UseCases coordenam ações, regras de negócio e transações; não acessam Models ou Repositories diretamente.
- Alterações que envolvam múltiplas entidades devem usar transação no UseCase.

--

# Manual dos Componentes de Input

## Importação

```jsx
import Inputs from "@/Components/Inputs";
```

Todos os componentes usam `React.lazy()`. Acesse via dot notation: `Inputs.Validation`, `Inputs.Mask`, etc.

## Dois modos de operação

**Modo Form** (padrão para Inertia `useForm`):

```jsx
const { data, setData, errors } = useForm({ nome: "" });

<Inputs.Validation name="nome" label="Nome" value={data.nome} setData={setData} errors={errors} />
```

**Modo Controlado** (para filtros de tabela, estados externos):

```jsx
<Inputs.Validation name="nome" label="Nome" value={inputValue.nome} onChange={(e) => handleFilterChange(e)} labelLight />
```

---

## Inputs.Validation — Input de texto

```jsx
<Inputs.Validation
  name="razao_social"      // obrigatório — id, htmlFor e chave em errors
  label="Razão Social"     // obrigatório
  value={data.razao_social} // obrigatório
  setData={setData}        // modo form — use OU onChange, nunca ambos
  errors={errors}          // objeto de erros do useForm
  onChange={handleFilter}   // modo controlado
  type="text"              // text | password | email | number (default: text)
  labelLight               // label claro para fundo escuro (filtros)
  clearable                // botão X para limpar
  readOnly
  disabled
  maxLength={4}
  className="w-full"
/>
```

Exemplos reais:
```jsx
// Formulário
<Inputs.Validation name="razao_social" label="Razão Social" value={data.razao_social} setData={setData} errors={errors} />
<Inputs.Validation type="password" name="password" label="Senha" setData={setData} errors={errors} />

// Filtro
<Inputs.Validation name="descricao" label="Descrição" value={inputValue.descricao} onChange={(e) => handleFilterChange(e)} labelLight />

// Aninhado com dot notation
<Inputs.Validation name="endereco.logradouro" label="Logradouro" value={data.endereco?.logradouro} setData={setData} errors={errors} />

// Dinâmico em array
<Inputs.Validation name={`equipamentos[${index}].unidade`} value={equipamento.unidade} setData={setData} errors={errors} />
```

---

## Inputs.Mask — Input com máscara

Extende Validation. Adicione a prop `mask`:

```jsx
<Inputs.Mask mask="tel" name="telefone" label="Telefone" value={data.telefone} setData={setData} errors={errors} />
```

### Máscaras disponíveis

| mask | Formato | Exemplo |
|------|---------|---------|
| `cpf` | `___.___.___-__` | `123.456.789-00` |
| `cnpj` | `__.___.___/____-__` | `12.345.678/0001-90` |
| `cgc` | auto-detecta CPF/CNPJ | `123.456.789-00` ou `12.345.678/0001-90` |
| `cep` | `__.___-___` | `01.001-000` |
| `tel` | `(__) _____-____` | `(11) 91234-5678` |
| `date` | `__/__/____` | `01/12/2026` |
| `cartao` | `____ ____ ____ ____` | `1234 5678 9012 3456` |
| `placa` | `___-____` | `ABC-1234` |
| `ano` | `____` | `2026` |
| `boleto` | formato boleto | `23793.38128 60000...` |
| `aleatoria` | UUID alfanumérico | `a1b2c3d4-e5f6...` |
| *(default)* | aceita `[a-zA-Z0-9@._-]` | — |

Aceita todas as props de `Inputs.Validation`.

---

## Inputs.Flatpickr — Datepicker

Locale pt_BR pré-configurado. Suporta data única, range e mês/ano.

```jsx
<Inputs.Flatpickr
  name="data_inicio"       // obrigatório
  label="Data de início"   // obrigatório
  value={data.data_inicio} // "YYYY-MM-DD" | ["YYYY-MM-DD","YYYY-MM-DD"] | "YYYY-MM"
  setData={setData}
  errors={errors}
  mode="single"            // "single" (default) | "range"
  monthYearOnly            // selector mês/ano
  clearable                // botão de limpar
  disabled
  labelLight               // label claro para fundo escuro
  mindate="2026-01-01"
  maxdate="2026-12-31"
  placeholder="Selecione..."
  className="w-64"
  onChange={callback}       // modo controlado
/>
```

**Formatos de value:**
- `single`: `"2026-09-01"`
- `range`: `["2026-09-01", "2026-09-30"]`
- `monthYearOnly`: `"2026-09"`

```jsx
// Data simples
<Inputs.Flatpickr label="Data de início" name="data_inicio" value={data.data_inicio} setData={setData} errors={errors} />

// Mês/ano
<Inputs.Flatpickr label="Validade" name="validade" value={data.validade} setData={setData} errors={errors} monthYearOnly />

// Range
<Inputs.Flatpickr label="Período" name="periodo" value={data.periodo} setData={setData} mode="range" clearable />

// Range em filtro
<Inputs.Flatpickr name="vencimento" label="Vencimento" value={inputValue.vencimento} onChange={(e) => handleFilterChange(e, 'vencimento', false, true)} mode="range" clearable labelLight />
```

---

## Inputs.Number — Input numérico formatado

Exibe valor formatado (`R$ 1.234,56`) mas envia valor numérico puro ao form.

```jsx
<Inputs.Number
  name="valor"
  label="Valor"
  value={data.valor}        // string ou number
  setData={setData}
  errors={errors}
  format="currency"         // "currency" (default) | "decimal"
  maxDigits={2}             // casas decimais (default: 2)
  readOnly
  disabled
  clearable
  labelLight
  onChange={callback}        // modo controlado
  onBlur={calculateTotal}   // callback ao perder foco
/>
```

**Valores enviados:**
- `format="currency"`, `maxDigits=2` → `"1234.56"`
- `format="decimal"`, `maxDigits=0` → `"1234"`
- `format="decimal"`, `maxDigits=4` → `"1234.5678"`

```jsx
// Moeda
<Inputs.Number name="valor" label="Valor" value={data.valor} setData={setData} errors={errors} format="currency" />

// Decimal 4 casas
<Inputs.Number name="litros" label="Litros" value={data.litros} setData={setData} errors={errors} format="decimal" maxDigits={4} />

// Inteiro
<Inputs.Number name="km" label="Km" value={data.km} setData={setData} errors={errors} format="decimal" maxDigits={0} />

// Com onBlur
<Inputs.Number name="qtd" label="Qtd" value={data.qtd} setData={setData} errors={errors} format="decimal" maxDigits={4} onBlur={calculateTotal} />
```

---

## Inputs.Select — Select síncrono ou async

Usa `react-select`. Para async, passe `async` + `loadOptions`.

```jsx
<Inputs.Select
  name="fornecedor_id"      // obrigatório
  label="Fornecedor"         // obrigatório
  value={data.fornecedor}    // { value, label } ou null
  setData={setData}
  errors={errors}
  async                      // habilita AsyncSelect
  loadOptions={buscarFornecedores}  // (inputValue, callback) => void
  altName="fornecedor"       // salva objeto completo em altName, value em name
  isMulti                    // seleção múltipla
  isClearable                // default: true
  options={[{ value: 1, label: "Opção" }]} // opções estáticas (sem async)
  labelLight                 // label claro
  menuPlacement="auto"
  readOnly
  disabled
  required
  onChange={callback}         // modo controlado
  key={fornecedorId}          // força re-render
/>
```

**altName:** Quando definido, `setData(name, selectedOption.value)` + `setData(altName, selectedOption)`.

```jsx
// Async com altName
<Inputs.Select async label="Fornecedor" altName="fornecedor" name="fornecedor_id" value={data.fornecedor} setData={setData} errors={errors} loadOptions={buscarFornecedor} />

// Síncrono
<Inputs.Select name="passivo" label="Passivo" value={inputValue.passivo} onChange={(e) => handleFilterChange(e, 'passivo')} options={[{ value: 1, label: "Circulante" }]} labelLight />

// Múltiplo
<Inputs.Select async altName="ordem_compra" name="ordem_compra_id" label="Ordem de compra" errors={errors} setData={setData} value={data.ordem_compra} loadOptions={loadOrdens} isMulti />
```

---

## Inputs.Search — Input com botão de busca

InputMask + botão de lupa à direita. Ideal para CEP, CNPJ, etc.

```jsx
<Inputs.Search
  clickButton={handleCnpj}   // obrigatório — callback ao clicar
  mask="cgc"                  // obrigatório — mesma tabela de Inputs.Mask
  name="cnpj"
  label="CNPJ/CPF"
  value={data.cnpj}
  setData={setData}
  errors={errors}
  loading={false}             // true = exibe ícone de loading ao invés da lupa
/>
```

```jsx
// CNPJ
<Inputs.Search clickButton={handleCnpj} mask="cgc" name="cnpj" label="CNPJ/CPF" value={data.cnpj} setData={setData} errors={errors} />

// CEP com loading
<Inputs.Search clickButton={handleCep} mask="cep" name="endereco.cep" label="CEP" value={data.endereco?.cep} setData={setData} errors={errors} loading={loadingCep} />
```

---

## Inputs.CreatableSelect — Select que permite criar opções

AsyncCreatableSelect do `react-select`. Permite criar novas opções inline.

```jsx
<Inputs.CreatableSelect
  name="unidade_id"
  label="Unidade"
  value={data.unidade}       // { value, label } ou null
  setData={setData}
  errors={errors}
  loadOptions={buscarUnidade}
  altName="unidade"
  async
  formatCreateLabel={(e) => `Criar "${e}"`}  // default: 'Criar categoria "${e}"'
  onChange={callback}
/>
```

```jsx
// Criar unidade
<Inputs.CreatableSelect altName="unidade" name="unidade_id" label="Unidade" value={data.unidade} setData={setData} errors={errors} loadOptions={buscarUnidade} />

// Criar categoria
<Inputs.CreatableSelect label="Categoria" name="categoria_id" value={data.categoria} loadOptions={buscarCategoria} onChange={(opt) => handleSelectChange("categoria", opt, "categoria_id")} />
```

---

## Inputs.Textarea — Área de texto

Wrapper do Flowbite `Textarea`. **Sempre usa `setData`** (não suporta `onChange` separado).

```jsx
<Inputs.Textarea
  name="observacoes"
  label="Observações"
  value={data.observacoes}
  setData={setData}
  errors={errors}
  rows={8}                   // linhas visíveis
  disabled
/>
```

```jsx
<Inputs.Textarea label="Observações" name="observacoes" value={data.observacoes} setData={setData} errors={errors} />
<Inputs.Textarea label="Descrição" name="descricao" rows={8} setData={setData} errors={errors} value={data.descricao} />
```

---

## Inputs.FileInput — Upload de arquivo (recomendado)

Input de arquivo nativo estilizado.

```jsx
<Inputs.FileInput
  name="anexo"
  label="Anexar Nota"
  setData={setData}
  errors={errors}
  multiple                   // aceita múltiplos arquivos
  value=""
/>
```

**Comportamento:** 1 arquivo → `setData(name, file)`. Múltiplos → `setData(name + "[]", Array.from(files))`.

```jsx
<Inputs.FileInput label="Assinatura" name="assinatura" setData={setData} />
<Inputs.FileInput name="anexo" label="Anexar Nota" errors={errors} setData={setData} />
<Inputs.FileInput label="Anexos" name="anexo" setData={setData} errors={errors} multiple />
```

---

## Inputs.File — Upload com drag-and-drop

Área de upload com drag-and-drop. **Não utilizado no código atual.**

```jsx
<Inputs.File label="Documentos" name="documentos" setData={setData} errors={errors} multiple />
```

---

## Inputs.AddonSelect — Select + Input lado a lado

Select à esquerda + input com máscara à direita. **Não utilizado no código atual.**

```jsx
<Inputs.AddonSelect
  name="ddd_telefone"
  label="Telefone"
  setData={setData}
  errors={errors}
  mask="(__) _____-____"
  options={[{ value: "11", label: "11" }, { value: "21", label: "21" }]}
/>
```

---

## Referência rápida

| Componente | Usar para | Props-chave |
|---|---|---|
| `Validation` | Texto, senha, email | `name`, `label`, `value`, `setData`/`onChange`, `errors` |
| `Mask` | CPF, CNPJ, CEP, tel, placa, cartão | `mask` + mesmas do Validation |
| `Flatpickr` | Datepicker, MonthPicker, RangePicker | `mode`, `monthYearOnly`, `clearable`, `mindate`, `maxdate` |
| `Number` | Moeda, decimais, inteiros | `format`, `maxDigits`, `onBlur` |
| `Select` | Dropdown (sync/async) | `async`, `loadOptions`, `altName`, `isMulti`, `options` |
| `Search` | Input + botão de busca | `clickButton`, `mask`, `loading` |
| `CreatableSelect` | Select criável | `loadOptions`, `formatCreateLabel` |
| `Textarea` | Texto longo | `rows` |
| `FileInput` | Upload de arquivo | `multiple` |
| `File` | Upload drag-and-drop | `multiple` |
| `AddonSelect` | Select + input lado a lado | `mask`, `options` |

