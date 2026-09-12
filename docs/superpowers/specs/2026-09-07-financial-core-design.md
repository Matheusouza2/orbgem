# Núcleo financeiro — especificação de domínio

## Objetivo

Construir o núcleo de uma aplicação de gestão financeira pessoal multiusuário. Cada usuário poderá possuir uma carteira própria, compartilhar carteiras com familiares e consultar uma consolidação virtual de carteiras autorizadas. O sistema web será construído primeiro, mas o domínio e os contratos HTTP deverão permitir um cliente mobile posteriormente.

## Escopo inicial

O primeiro núcleo funcional inclui:

- autenticação multiusuário;
- carteiras e membros de carteira;
- contas e cartões de crédito;
- categorias hierárquicas e merchants;
- ledger de transações;
- compras de cartão, parcelas e faturas;
- transações recorrentes;
- compromissos financeiros;
- orçamento mensal e visão de comprometimento futuro;
- dashboard mensal e projeções básicas.

Metas financeiras, tags, anexos, notificações e importação da planilha são extensões posteriores, mas devem poder referenciar o núcleo sem exigir alteração da semântica de `Transaction`.

## Arquitetura e fronteiras

O bounded context inicial é `Financial`, organizado conceitualmente em:

```text
Financial
├── Accounts
│   ├── Account
│   └── Balance
├── Ledger
│   ├── Transaction
│   ├── Category
│   └── Merchant
├── CreditCards
│   ├── CreditCard
│   ├── Purchase
│   ├── Installment
│   └── Invoice
├── Planning
│   ├── RecurringTransaction
│   ├── FinancialCommitment
│   ├── Budget
│   └── FinancialGoal
└── Reporting
    ├── CashFlow
    ├── MonthlySummary
    ├── CategoryAnalysis
    └── FutureCommitments
```

No backend flow deve pular as camadas definidas pelo projeto: Route → FormRequest → Controller → DTO → UseCase → Service → RepositoryInterface → Repository → Model, retornando por Resource ou contrato explícito de resposta. Controllers não acessam Models, queries ou transações.

## Ownership, compartilhamento e consolidação

`Wallet` é o aggregate root do domínio financeiro. Os usuários acessam uma carteira exclusivamente por `WalletMember`.

```text
User
└── WalletMember
    └── Wallet
        ├── Account
        ├── CreditCard
        ├── Category
        ├── Merchant
        ├── Transaction
        ├── Budget
        └── FinancialCommitment
```

`wallet_members` deve conter, no mínimo, `wallet_id`, `user_id`, `role` e `joined_at`. Os papéis iniciais são `OWNER`, `EDITOR` e `VIEWER`:

- `OWNER`: administra carteira, membros e configurações;
- `EDITOR`: cria e altera registros financeiros permitidos;
- `VIEWER`: consulta dados sem alterar o ledger.

Os registros financeiros usam `wallet_id`. Campos de auditoria usam `created_by_member_id` e `updated_by_member_id` quando a ação ocorrer no contexto de uma carteira. Contas e cartões podem ter `owner_wallet_member_id` nulo: nulo significa que o recurso pertence à carteira como um todo.

Uma consolidação virtual é uma seleção de carteiras às quais o usuário tem acesso. Ela somente altera o escopo de consulta e agregação; não copia, transfere, encerra nem altera registros de origem. A interface poderá apresentar visão consolidada, visão de uma carteira e visão por membro.

## Entidades principais

### Account

Representa onde o dinheiro existe ou é mantido.

- `id`, `wallet_id`, `owner_wallet_member_id` nullable;
- `name`, `institution` nullable;
- `type`: `CHECKING`, `SAVINGS`, `CASH`, `INVESTMENT`, `DIGITAL_WALLET`;
- `initial_balance` em centavos;
- `active`.

O saldo atual é derivado do saldo inicial e das transações efetivadas da conta. `Balance` é uma projeção de leitura; não é fonte independente de verdade no primeiro MVP.

### CreditCard

É separado de `Account`, mesmo quando existe uma conta relacionada na mesma instituição.

- `id`, `wallet_id`, `owner_wallet_member_id` nullable;
- `name`, `institution` nullable;
- `limit` em centavos;
- `closing_day`, `due_day`;
- `active`.

`account_id` é opcional e representa uma conta padrão para pagamento da fatura, não o local onde o limite do cartão existe.

### CreditCardPurchase

Representa o compromisso original assumido no cartão.

- `id`, `wallet_id`, `credit_card_id`, `category_id`;
- `merchant_id` nullable;
- `description`, `purchase_date`;
- `total_amount` em centavos;
- `installment_count`.

Uma compra nunca movimenta `Account` e nunca gera uma despesa pelo valor total.

### Installment

Representa uma parcela específica da compra.

- `id`, `credit_card_purchase_id`, `credit_card_invoice_id`;
- `number` único dentro da compra;
- `amount` em centavos;
- `competence_date`, `due_date`;
- `status`: `PENDING`, `INVOICED`, `CANCELLED`.

As parcelas são geradas imediatamente na criação da compra. A soma das parcelas deve ser exatamente igual ao valor total da compra; diferenças de arredondamento ficam na última parcela.

### CreditCardInvoice

Representa o ciclo mensal do cartão.

- `id`, `wallet_id`, `credit_card_id`;
- `reference_month` como mês calendário;
- `closing_date`, `due_date`;
- `status`: `OPEN`, `CLOSED`, `PAID`, `OVERDUE`;
- `paid_at` nullable;
- `calculated_total` obtido pela soma das parcelas vinculadas, sem coluna duplicada na primeira versão.

Faturas futuras podem ser criadas como `OPEN` no momento da compra para receber as parcelas projetadas. Ao chegar o fechamento, as parcelas elegíveis passam a `INVOICED` e a fatura passa a `CLOSED`. O pagamento altera a fatura para `PAID` e cria registros em `InvoicePayment` vinculados a transferências de uma `Account`.

`InvoicePayment` possui `id`, `credit_card_invoice_id`, `transaction_id`, `amount` em centavos e `paid_at`. A relação entre invoice e pagamentos é 1:N, permitindo pagamento parcial mesmo que a interface inicial ofereça somente pagamento integral.

### Transaction

É o ledger financeiro central e representa uma movimentação reconhecida pelo sistema, inclusive quando ainda projetada.

- `id`, `wallet_id`;
- `account_id` nullable;
- `category_id` nullable conforme o tipo;
- `description`;
- `type`: `INCOME`, `EXPENSE`, `TRANSFER`;
- `effect`: `DEBIT`, `CREDIT`, `NONE`;
- `amount` em centavos, sempre positivo, com o sentido definido por `type` e pelo vínculo;
- `financial_instrument_type`: `ACCOUNT`, `CREDIT_CARD`, `NONE`;
- `transaction_date`;
- `competence_date`;
- `due_date` nullable;
- `paid_at` nullable;
- `status`: `PROJECTED`, `POSTED`, `CANCELLED`;
- `payment_channel` nullable: `PIX`, `DEBIT_CARD`, `CASH`, `BANK_TRANSFER`, `BOLETO`, `OTHER`;
- `notes` nullable;
- `recurring_transaction_id` nullable;
- `credit_card_invoice_id` nullable;
- `installment_id` nullable;
- `transfer_group_id` nullable;
- `reversal_of_transaction_id` nullable;
- `created_by_member_id`, `updated_by_member_id`.

Uma parcela possui exatamente uma transação `EXPENSE`, com `financial_instrument_type = CREDIT_CARD` e `effect = NONE`. A transação da parcela pertence à fatura e usa a competência da parcela. Ela não altera o saldo de uma `Account` enquanto a fatura não for paga.

Uma despesa paga diretamente por conta possui `financial_instrument_type = ACCOUNT` e `effect = DEBIT`. Uma receita recebida em conta possui `effect = CREDIT`. O saldo de uma conta é calculado por `initial_balance + créditos - débitos` considerando somente transações aplicáveis à conta.

O pagamento de uma fatura é uma transação `TRANSFER` com `effect = DEBIT`, `financial_instrument_type = ACCOUNT`, `account_id` preenchido e vínculo em `InvoicePayment`. Não cria nova despesa e não exige uma conta de destino artificial. Transferências entre contas possuem duas pernas relacionadas pelo mesmo `transfer_group_id`, uma saída e uma entrada.

Um estorno não usa valor negativo. Ele cria uma nova transação positiva, com `reversal_of_transaction_id` apontando para a transação original e efeito econômico compatível com a reversão. O estorno de parcela também deve refletir a fatura correspondente.

### Category e Merchant

`Category` não será texto livre dentro da transação. Categorias podem ser globais (`wallet_id` nulo) ou específicas da carteira, possuem `parent_id` nulo ou apontando para outra categoria, `name`, `type`, `icon` e `active`. A categoria pai deve ser compatível com o tipo da transação. Categorias globais são gerenciadas pelo sistema e somente leitura para usuários; categorias da carteira podem ser administradas conforme a permissão do membro.

`Merchant` pertence à carteira, possui nome normalizado para busca e pode ser referenciado por compras e transações.

### RecurringTransaction

Modela uma regra de recorrência, não uma despesa já lançada.

- `id`, `wallet_id`, `account_id`, `category_id`;
- `description`, `type`, `amount`;
- `frequency`: `WEEKLY`, `MONTHLY`, `YEARLY`;
- `start_date`, `end_date` nullable, `due_day`;
- `auto_create`, `active`.

Cada ocorrência criada deve apontar para a regra de origem por `recurring_transaction_id`.

### FinancialCommitment

Representa obrigações de longo prazo que não são compras parceladas de cartão.

- `id`, `wallet_id`;
- `description`;
- `type`: `LOAN`, `FINANCING`, `CONSORTIUM`, `EDUCATION`, `OTHER`;
- `original_amount`, `installment_amount` em centavos;
- `installment_count`, `current_installment`;
- `start_date`, `end_date`;
- `creditor`.

O compromisso pode gerar ocorrências no ledger, mas sua existência não deve ser confundida com uma compra de cartão.

### Budget

Representa o limite planejado de uma carteira para uma categoria em um período. O consumo é calculado pelas transações de despesa da competência, respeitando o escopo de carteira consolidada selecionado.

## Semântica temporal e métricas

O domínio separa três conceitos:

1. **Compromisso financeiro:** nasce na criação da compra e considera todas as parcelas futuras restantes.
2. **Despesa reconhecida:** nasce quando a parcela pertence à competência da fatura e sua transação está `POSTED`. A previsão inclui também transações `PROJECTED`.
3. **Saída de caixa:** ocorre quando a fatura é paga por uma `Account` ou quando uma saída comum é efetivada.

`transaction_date` representa quando o fato ocorreu, `competence_date` representa o mês de reconhecimento no orçamento e `due_date` representa quando o pagamento é esperado. Relatórios devem declarar qual dessas datas utilizam; nunca inferir uma data a partir de outra.

O dashboard mensal exibirá separadamente:

- receitas previstas e efetivadas;
- despesas da competência;
- parcelas de cartão da competência;
- compromissos financeiros;
- renda comprometida;
- valor livre estimado;
- parcelas futuras agrupadas por mês.

Relatórios devem usar nomes explícitos para evitar divergência entre telas:

- `actual_expenses`: despesas `POSTED`;
- `forecast_expenses`: despesas `POSTED` + `PROJECTED`;
- `future_commitments`: parcelas futuras não canceladas.

## Invariantes de domínio

1. Toda entidade financeira pertencente ao usuário tem `wallet_id`; categorias globais são a única exceção explícita e podem ter `wallet_id` nulo.
2. Usuários acessam carteiras exclusivamente por `WalletMember`.
3. `CreditCardPurchase` nunca movimenta uma `Account`.
4. Uma compra gera exatamente `N` parcelas.
5. Uma parcela pertence a exatamente uma competência e uma fatura.
6. Uma parcela possui exatamente uma transação de despesa.
7. O total da compra é igual à soma exata das parcelas.
8. A fatura agrega parcelas, e seu total é derivado delas em `calculated_total`.
9. Uma invoice pode possuir vários `InvoicePayment`, e a soma não pode exceder o total da fatura.
10. O pagamento da fatura gera transferência, não despesa.
11. O orçamento mensal usa `competence_date` e separa efetivo de projetado.
12. O comprometimento futuro usa parcelas projetadas ainda não canceladas.
13. A liquidação da fatura não altera semanticamente a despesa da parcela.
14. Transferências entre contas possuem origem e destino relacionados pelo mesmo `transfer_group_id`.
15. `effect = DEBIT` reduz saldo, `effect = CREDIT` aumenta saldo e `effect = NONE` não afeta saldo de conta.
16. Uma reversão aponta para uma transação original e não usa valor negativo.
17. Valores monetários não usam `float`.
18. Operações de criação de compra, parcelas, transações e faturas são atômicas.
19. `UNIQUE(wallet_id, user_id)` impede membership duplicada.
20. Toda wallet mantém pelo menos um `OWNER`; o último owner não pode sair ou ser rebaixado.

## Fases de implementação

### Slice 1 — mínimo ledger funcional

Autenticação, API Sanctum versionada, `Wallet`, `WalletMember`, papéis, `Account`, `Category`, `Transaction`, saldo derivado, criação de receita e criação de despesa. Objetivo: substituir a parte simples da planilha.

### Slice 2 — movimentação financeira

`Merchant`, transferências entre contas, `effect`, estornos, filtros, extrato e resumo mensal.

### Slice 3 — cartões

`CreditCard`, faturas, compras, parcelas, geração de transações projetadas, fechamento e pagamento.

### Slice 4 — planejamento e dashboard

Recorrências, compromissos financeiros, budgets, visão mensal e comprometimento futuro.

### Slice 5 — expansão

Consolidação virtual persistida, metas, tags, anexos, notificações e importação da planilha.

Cada fase deve entregar fluxo web testável e endpoints API equivalentes para os casos de uso implementados.

## Decisões fora do escopo imediato

- `ADMIN` e `MEMBER` não serão papéis iniciais; serão avaliados depois dos três papéis aprovados.
- A consolidação não unifica fisicamente carteiras.
- O saldo materializado e eventos de auditoria detalhados podem ser adicionados quando o volume justificar.
- Integrações bancárias, notificações externas e sincronização automática não fazem parte do primeiro núcleo.
