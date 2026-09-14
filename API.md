# OrbGem API — Guia para o app mobile

## 1. Convenções

### URL base

Use a URL base da API por ambiente, incluindo o prefixo `/api`:

```text
http://localhost:8002/api       # desenvolvimento
http://orbgem.com.br/api        # produção
```

Não fixe domínio, porta ou protocolo no código do app. Os valores devem ser
configuração de ambiente em `EXPO_PUBLIC_API_URL`. Os caminhos abaixo são
relativos a essa base, por exemplo `POST /auth/login`.

### Headers

Para JSON:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

Para upload, use `multipart/form-data` e não defina manualmente o `Content-Type`, deixando o cliente gerar o boundary. Os endpoints públicos de autenticação não exigem `Authorization`.

### Autenticação

O login e o cadastro retornam um token Sanctum. Armazene-o em armazenamento seguro do sistema operacional e envie-o como Bearer em toda rota protegida. O backend não usa refresh token nesta versão; ao expirar ou ser revogado, faça login novamente.

Resposta de login/cadastro:

```json
{
  "data": {
    "token": "1|...",
    "token_type": "Bearer",
    "user": { "id": 1, "name": "Maria", "email": "maria@example.com" }
  }
}
```

Valores monetários são inteiros em centavos: `1250` representa `R$ 12,50`. Datas enviadas e retornadas são ISO 8601 (`YYYY-MM-DD`); meses usam `YYYY-MM`.

### Respostas e erros

Resources de coleção retornam `data`; coleções paginadas também retornam `links` e `meta`. Sucesso sem conteúdo retorna HTTP `204`.

Erros comuns:

```json
{
  "message": "The given data was invalid.",
  "errors": { "field": ["Mensagem de validação"] }
}
```

- `401`: token ausente, inválido ou revogado;
- `403`: usuário não tem papel suficiente na carteira;
- `404`: recurso inexistente ou não acessível;
- `422`: validação ou regra de negócio;
- `429`: limite de requisições;
- `500`: erro interno; o app deve mostrar mensagem genérica e registrar contexto localmente sem expor credenciais.

Papéis de carteira: `OWNER`, `EDITOR`, `VIEWER`. Leitura normalmente aceita os três; criação/edição exige permissão de edição.

## 2. Autenticação e usuário

### `POST /auth/register` — público

```json
{ "name": "Maria", "email": "maria@example.com", "password": "senha-forte", "password_confirmation": "senha-forte", "device_name": "OrbGem Mobile" }
```

Retorna `201` com [AuthResource](#formato-de-recursos-principais).

### `POST /auth/login` — público

Payload: `email`, `password` e `device_name` opcional. Retorna `200` com token e usuário.

### `POST /auth/forgot-password` — público

Payload `{ "email": "maria@example.com" }`. Retorna `202`. Não revele ao usuário se o e-mail existe.

### `POST /auth/reset-password` — público

Payload: `email`, `token`, `password`, `password_confirmation`. Retorna `200` quando redefinido.

### `GET /user`

Retorna `data: { id, name, email, ... }` do usuário autenticado.

### `POST /auth/logout` / `POST /auth/logout-all`

Sem payload, retornam `204`. Use logout-all para revogar todos os dispositivos.

### `GET /auth/tokens` e `DELETE /auth/tokens/{token}`

Lista ou revoga tokens do usuário. Token contém `id`, `name`, `abilities`, `last_used_at`, `created_at` e `expires_at`.

## 3. Carteiras e membros

### `GET /wallets`

Lista as carteiras do usuário. Cada item possui `id`, `name` e `created_at`. Todo usuário novo inicia com uma carteira padrão criada pelo backend.

### `POST /wallets` / `PATCH /wallets/{wallet}`

Payload: `{ "name": "Pessoal" }`. Retorna a carteira criada ou atualizada.

### `GET /wallets/{wallet}/members`

Lista membros com `id`, `wallet_id`, `user { id, name, email }`, `role` e `joined_at`.

### `POST /wallets/{wallet}/members`

Payload: `{ "user_id": 2, "role": "EDITOR" }`.

### `PATCH /wallets/{wallet}/members/{member}`

Payload: `{ "role": "VIEWER" }`.

### `DELETE /wallets/{wallet}/members/{member}`

Sem payload; retorna `204`.

## 4. Contas

### `GET /accounts?wallet_id={walletId}`

Lista contas da carteira. Campos: `id`, `wallet_id`, `owner_wallet_member_id`, `name`, `institution`, `bank_code`, `account_number`, `type`, `initial_balance`, `pluggy_item_id`, `pluggy_account_id`, `pluggy_balance`, `is_default`, `show_in_dashboard`, `ignore_in_totals`, `active`, `created_at`.

Tipos de conta: `CHECKING`, `SAVINGS`, `CASH`, `INVESTMENT`, `DIGITAL_WALLET`.

### `POST /accounts` / `PATCH /accounts/{account}`

```json
{
  "wallet_id": 1,
  "name": "Conta corrente",
  "institution": "Banco Exemplo",
  "bank_code": "00000000",
  "account_number": "12345-6",
  "type": "CHECKING",
  "initial_balance": 150000,
  "is_default": true,
  "show_in_dashboard": true,
  "ignore_in_totals": false,
  "active": true
}
```

`bank_code` é opcional, mas quando informado deve conter exatamente 8 dígitos. `initial_balance` é centavos.

## 5. Categorias e estabelecimentos

### `GET /categories?wallet_id={walletId}`

Retorna `id`, `wallet_id`, `parent_id`, `name`, `type`, `icon`, `icon_color`, `active`, `created_at`. Tipos: `INCOME` e `EXPENSE`.

### `POST /categories` / `PATCH /categories/{category}`

Payload: `wallet_id` opcional na criação, `parent_id` opcional, `name`, `type`, `icon`, `icon_color` (`#RRGGBB`) e `active`.

### `GET /merchants?wallet_id={walletId}`

Retorna `id`, `wallet_id`, `name`, `normalized_name`, `active`, `created_at`.

### `POST /merchants`

Payload: `{ "wallet_id": 1, "name": "Mercado", "active": true }`. `active` é opcional.

## 6. Transações

### `GET /transactions`

Parâmetros obrigatórios: `wallet_id`. Filtros opcionais:

| Parâmetro | Valores |
|---|---|
| `account_id`, `merchant_id` | inteiro |
| `type` | `INCOME`, `EXPENSE`, `TRANSFER` |
| `status` | `POSTED`, `PROJECTED`, `CANCELLED` |
| `month` | `YYYY-MM` |
| `page`, `per_page` | `per_page` entre 1 e 100 |
| `sort_by` | `id`, `amount`, `transaction_date`, `competence_date`, `created_at` |
| `sort_direction` | `asc`, `desc` |
| `transaction_date_from/to` | data |
| `competence_date_from/to` | data |
| `include_third_party` | `0` ou `1`; padrão `1` |

O resultado é paginado. Para conta, use `wallet_id`, `account_id`, `month`, `status`, `page`, `per_page` e `include_third_party`.

### `GET /transactions/{transaction}`

Retorna os dados completos do lançamento em `data`, usando o contrato base de
`TransactionResource` e os relacionamentos carregados para o detalhe:
`wallet`, `account`, `category` e `merchant`. A categoria inclui `id`,
`wallet_id`, `parent_id`, `name`, `type`, `icon`, `icon_color`, `active` e
`created_at`, permitindo compor a identificação visual da transação. As
relações sem vínculo retornam `null`.

A consulta exige que o usuário tenha acesso à carteira da transação.

### `POST /transactions`

```json
{
  "wallet_id": 1,
  "account_id": 3,
  "category_id": 4,
  "merchant_id": 8,
  "description": "Supermercado",
  "type": "EXPENSE",
  "effect": "DEBIT",
  "amount": 18990,
  "financial_instrument_type": "ACCOUNT",
  "transaction_date": "2026-09-13",
  "competence_date": "2026-09-13",
  "due_date": "2026-09-13",
  "recurrence_type": "NONE",
  "auto_post_on_due_date": false,
  "status": "POSTED",
  "payment_channel": "PIX",
  "notes": "",
  "is_third_party": false
}
```

`financial_instrument_type` pode ser `ACCOUNT`, `CREDIT_CARD` ou `NONE`; o lançamento comum exige conta. Recorrências: `NONE`, `FIXED_MONTHLY`, `INSTALLMENT`. Em parcelamento, informe `installment_initial`, `installment_count` e `installment_periodicity` (`MONTHLY`, `BIMONTHLY`, `QUARTERLY`, `YEARLY`). Não envie campos controlados pelo servidor: `recurring_transaction_id`, `credit_card_invoice_id`, `installment_id`, `transfer_group_id` e `reversal_of_transaction_id`.

Resposta de transação inclui os campos enviados e também `id`, `paid_at`, vínculos, `has_reversal`, membros criador/editor e `created_at`.

### `PUT|PATCH /transactions/{transaction}`

Atualiza os campos editáveis da transação. O `wallet_id` deve permanecer na
carteira do usuário autenticado; não envie vínculos controlados pelo servidor.

### `DELETE /transactions/{transaction}`

Remove a transação autorizada e retorna `204`.

### `POST /transactions/{transaction}/reversal`

Payload opcional: `transaction_date`, `competence_date`, `notes`. Cria o estorno e retorna a transação resultante.

### `POST /account-transfers`

```json
{ "wallet_id": 1, "from_account_id": 3, "to_account_id": 4, "amount": 50000, "transaction_date": "2026-09-13", "competence_date": "2026-09-13", "notes": "Reserva", "payment_channel": "BANK_TRANSFER" }
```

As contas devem ser diferentes e pertencer à carteira. Retorna a transferência criada.

### `GET /monthly-summary`

Parâmetros `wallet_id`, `month` e opcional `include_third_party=0|1`. Retorna resumo mensal com balanço, receitas, despesas, transferências, economia e dados de planejamento. Contas marcadas `ignore_in_totals` e, quando solicitado, despesas de terceiros ficam fora dos totais.

### `GET /planning-summary`

Consulta o planejamento da carteira. Envie os filtros aceitos pelo endpoint, principalmente `wallet_id` e `month` conforme a tela de planejamento. O retorno contém consolidado de receitas/despesas e orçamentos.

## 7. Cartões de crédito

### `GET /credit-cards?wallet_id={walletId}`

Campos: `id`, `wallet_id`, `name`, `institution`, `limit`, `current_invoice_amount`, `current_invoice_reference_month`, `limit_usage_percentage`, `closing_day`, `due_day`, `active`, `created_at`.

### `POST /credit-cards` / `PUT /credit-cards/{creditCard}`

Payload: `wallet_id`, `name`, `institution`, `limit` em centavos, `closing_day`, `due_day`, `active` opcional e `account_id`/`owner_wallet_member_id` opcionais.

### `DELETE /credit-cards/{creditCard}`

Retorna `204`.

### `POST /credit-card-purchases`

```json
{
  "wallet_id": 1,
  "credit_card_id": 5,
  "category_id": 4,
  "merchant_id": 8,
  "description": "Compra parcelada",
  "purchase_date": "2026-09-13",
  "due_date": "2026-10-12",
  "total_amount": 120000,
  "installment_count": 3,
  "is_third_party": false
}
```

Retorna a compra e suas parcelas. O valor total é em centavos. `due_date` é
opcional e, quando informado, determina a fatura de referência da compra.

### `PUT /credit-card-purchases/{purchase}`

Atualiza os dados editáveis da compra e retorna a compra atualizada.

### `DELETE /credit-card-purchases/{purchase}`

Remove a compra e suas parcelas e retorna `204`.

### `PUT /credit-card-transactions/{transaction}`

Atualiza uma parcela individual da compra do cartão e retorna a parcela
atualizada.

### `DELETE /credit-card-transactions/{transaction}`

Remove uma parcela individual da compra do cartão e retorna `204`.

### `GET /credit-cards/{creditCard}/transactions`

Parâmetros: `wallet_id`, `month` (`YYYY-MM`), `status`, `page`, `per_page` e `include_third_party=0|1`. Retorna coleção paginada com `id`, descrição, valor, tipo/efeito, status, datas, flag de terceiro, origem, parcela e fatura.

### `GET /credit-card-invoices`

Parâmetros: `wallet_id`, `credit_card_id` opcional e `status` (`OPEN`, `CLOSED`, `PAID`, `OVERDUE`). Fatura inclui referência, datas, status, total, pagamento e parcelas.

### `POST /credit-card-invoices/{invoice}/close`

Sem payload. Fecha a fatura e retorna a fatura atualizada.

### `POST /credit-card-invoices/{invoice}/payments`

Payload `{ "account_id": 3, "amount": 120000, "payment_date": "2026-09-20" }`. Registra pagamento usando uma conta da mesma carteira e utiliza `payment_date` como a data efetiva do pagamento. O valor deve ser informado em centavos e a data no formato `YYYY-MM-DD`.

## 8. Investimentos

### `GET /investments?wallet_id={walletId}`

Campos: `id`, `wallet_id`, `name`, `ticker`, `type`, `institution`, `quantity`, `average_price`, `invested_amount`, `current_value`, `profit_amount`, `profit_percentage`, `acquired_at`, `active`, `cdi_linked`, `cdi_percentage`, `last_yield_date`.

Tipos: `STOCK`, `FUND`, `FII`, `FIXED_INCOME`, `CRYPTO`, `OTHER`.

### `POST /investments` / `PUT|PATCH /investments/{investment}`

Payload mínimo: `wallet_id`, `name`, `type`, `quantity`, `average_price`, `invested_amount`, `current_value`. Também aceita `ticker`, `institution`, `acquired_at`, `active`, `cdi_linked`, `cdi_percentage` e `last_yield_date`. Para CDI, `cdi_percentage` é obrigatório quando `cdi_linked=true`.

### `DELETE /investments/{investment}`

Retorna `204`.

### `GET /investments/{investment}/yields`

Filtros opcionais `from` e `to`. Retorna `id`, investimento, data, taxa CDI diária, percentual contratado, saldo inicial, rendimento e saldo final.

### `GET /investment-income`

Parâmetros `wallet_id`, `investment_id`, `from` e `to`. Retorna rendimentos/proventos com investimento, ticker, descrição, `event_type`, `amount`, `transaction_date` e `source`.

### `GET /market/quote?symbol=B3SA3`

Retorna a cotação consultada pelo backend. O token da BRAPI nunca deve ser enviado pelo app mobile.

## 9. Metas, orçamento e recorrências

### `GET|POST /financial-goals`

GET aceita `wallet_id`; POST recebe `wallet_id`, `name`, `target_amount`, `current_amount` opcional, `deadline`, `status` (`ACTIVE`, `COMPLETED`, `CANCELLED`) e `active`. Valores em centavos.

### `PUT /financial-goals/{goal}` / `DELETE /financial-goals/{goal}`

Atualiza os mesmos campos ou remove a meta (`204`).

### `POST /financial-goals/{goal}/contributions`

Payload: `{ "amount": 50000, "contributed_at": "2026-09-13", "note": "Aporte" }`. Retorna meta atualizada.

### `GET|POST /budgets`

GET aceita `wallet_id`, `month` e filtros disponíveis. POST recebe `wallet_id`, `category_id`, `reference_month`, `amount` e `active` opcional.

### `PUT /budgets/{budget}` / `DELETE /budgets/{budget}`

Atualiza ou remove um orçamento.

### `GET|POST /recurring-transactions`

GET aceita `wallet_id`. POST recebe `wallet_id`, `account_id`, `category_id`, `description`, `type` (`INCOME` ou `EXPENSE`), `amount`, `frequency` (`WEEKLY`, `MONTHLY`, `YEARLY`), `start_date`, `end_date`, `due_day`, `auto_create` e `active`.

### `PUT /recurring-transactions/{recurring}` / `DELETE /recurring-transactions/{recurring}`

Atualiza ou remove a regra.

### `POST /recurring-transactions/{recurring}/generate`

Payload `{ "until": "2026-12-31" }`. Gera ocorrências até a data e retorna o resultado da operação.

### `POST /financial-commitments` / `GET /financial-commitments`

POST recebe `wallet_id`, `description`, `type` (`LOAN`, `FINANCING`, `CONSORTIUM`, `EDUCATION`, `OTHER`), `original_amount`, `installment_amount`, `installment_count`, `current_installment`, `start_date`, `end_date`, `creditor` e `active`. GET aceita `wallet_id`.

### `PUT /financial-commitments/{commitment}` / `DELETE /financial-commitments/{commitment}`

Atualiza ou remove o compromisso financeiro. A remoção retorna `204`.

### `POST /financial-commitments/{commitment}/generate`

Payload `{ "until": "2026-12-31" }`.

## 10. Open Finance

### `GET /open-finance/items`

Lista conexões financeiras do usuário. Cada conexão pode conter `id`, `item_id`, `wallet_id`, `connector_name`, `connector_logo`, `status`, `last_synced_at` e contas vinculadas.

### `POST /open-finance/connect-token`

Payload `{ "wallet_id": 1, "item_id": null }`. Retorna `{ "accessToken": "..." }` para o fluxo Pluggy Connect compatível. O token é temporário e não deve ser persistido como credencial.

### `POST /open-finance/items`

Registra um Item Pluggy já existente. Payload `{ "wallet_id": 1, "item_id": "uuid" }`.

### `POST /open-finance/connections/{connection}/sync`

Solicita sincronização assíncrona. Payload opcional `{ "from": "2026-01-01", "to": "2026-09-13" }`. Retorna `{ "status": "queued" }`. O app deve atualizar a conexão depois, não esperar os lançamentos na mesma resposta.

### `DELETE /open-finance/items/{item}`

Remove a conexão legada/compatível; retorna `204`.

### `POST /open-finance/webhook` — servidor Pluggy

Não é endpoint para o app mobile. O dashboard da Pluggy envia eventos `item/created`, `item/updated`, `item/error`, `transactions/created` e `transactions/updated`, com `itemId`, `accountId` e/ou `transactionIds`. O servidor localiza `financial_connections` pelo par `provider=pluggy` e `external_id=itemId`, então dispara os jobs de sincronização.

## 11. Anexos, tags e notificações

### `POST /transactions/{transaction}/attachments`

Multipart com `wallet_id` e `file` de até 10 MB. Retorna anexo.

### `GET /transactions/{transaction}/attachments`

Lista anexos do lançamento.

### `POST /attachments`

Multipart com `wallet_id`, `attachable_type`, `attachable_id` e `file`. Tipos aceitos: `transaction`, `financial_goal`, `financial_commitment`, `recurring_transaction`, `budget`, `import_batch`, `credit_card`.

### `GET /attachments`

Filtros opcionais `wallet_id`, `attachable_type` e `attachable_id`.

### `GET /attachments/{attachment}/download`

Retorna o download autorizado; tratar a resposta como arquivo, não JSON.

### `DELETE /attachments/{attachment}`

Remove o anexo e retorna `204`.

### `POST /tags`

Payload `{ "wallet_id": 1, "name": "Reembolsável", "color": "#123B8F" }`.

### `GET /tags`

Aceita `wallet_id`; retorna `id`, `wallet_id`, `name` e `color`.

### `POST /transactions/{transaction}/tags`

Payload `{ "tag_ids": [1, 2] }`. Retorna a transação atualizada.

### `GET /notifications`

Lista notificações do usuário.

### `PATCH /notifications/{notification}/read`

Sem payload; marca como lida e retorna a notificação.

## 12. Consolidações

### `GET|POST /consolidations`

POST recebe `{ "name": "Todas as carteiras", "wallet_ids": [1, 2] }`. GET lista consolidações.

### `PUT /consolidations/{consolidation}` / `DELETE /consolidations/{consolidation}`

Atualiza os mesmos campos ou remove a consolidação.

### `GET /consolidations/{consolidation}/summary`

Retorna o resumo consolidado dentro de `data`.

## 13. Importação CSV

### `POST /imports`

Multipart com `wallet_id` e arquivo `file` CSV/TXT de até 50 MB. Retorna `202` e um `ImportBatchResource`; o processamento pode ser assíncrono.

### `GET /imports/{import}`

Consulta o lote e seu status. O app deve fazer polling moderado até estado final.

## 14. Estratégia de consumo mobile

1. Faça login e armazene o Bearer token em cofre seguro.
2. Carregue `/user` e `/wallets`; selecione a carteira ativa.
3. Carregue contas, cartões, categorias e investimentos em paralelo quando a tela exigir.
4. Para listas, use `meta.current_page`, `meta.last_page`, `meta.total`, `links.next` e `links.prev`.
5. Após POST/PUT/DELETE, atualize o recurso local com a resposta ou invalide o cache da tela.
6. Após sincronização Open Finance, mostre status “em fila” e atualize por nova consulta.
7. Em falhas de rede, preserve o formulário localmente apenas se o produto implementar fila offline; nunca envie duplicadamente sem uma estratégia de idempotência do cliente.
8. Nunca coloque `PLUGGY_CLIENT_SECRET`, `PLUGGY_API_KEY`, `BRAPI_TOKEN` ou qualquer segredo em código, storage ou bundle mobile.
