<?php

namespace App\Services\BankReconciliation;

use App\DTO\BankStatementData;
use App\DTO\TransactionDTO;
use App\Enums\FinancialInstrumentType;
use App\Enums\TransactionEffect;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletMembershipAuthorization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    public function __construct(
        private StatementParser $parser,
        private ReconciliationMatcher $matcher,
        private TransactionService $transactions,
        private WalletMembershipAuthorization $membershipAuthorization,
    ) {}

    public function preview(int $accountId, string $month, \Illuminate\Http\UploadedFile $file, User $user): BankReconciliation
    {
        $account = Account::query()->with('wallet')->find($accountId);
        if ($account === null || $account->wallet === null) {
            throw ValidationException::withMessages(['account_id' => 'A conta selecionada não está disponível.']);
        }
        $this->membershipAuthorization->authorize($user, $account->wallet, \App\Enums\WalletMemberRole::EDITOR, \App\Enums\WalletMemberRole::OWNER);

        $statement = $this->parser->parse($file, $month);
        $this->validateStatement($statement);
        $warnings = $this->documentWarnings($statement, $account, $month);
        $period = Carbon::createFromFormat('!Y-m', $month);
        $systemTransactions = $this->transactions->forBankReconciliation(
            $account->id,
            $period->copy()->startOfMonth()->toDateString(),
            $period->copy()->endOfMonth()->toDateString(),
        );
        $analysis = $this->matcher->analyze($statement, $systemTransactions);
        $warnings = array_values(array_unique([...$warnings, ...$analysis['warnings']]));

        return DB::transaction(function () use ($account, $month, $file, $statement, $analysis, $warnings, $user): BankReconciliation {
            $reconciliation = BankReconciliation::query()->create([
                'wallet_id' => $account->wallet_id,
                'account_id' => $account->id,
                'user_id' => (int) $user->getAuthIdentifier(),
                'month' => $month,
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'status' => 'PREVIEW',
                'document_account_number_last4' => $statement->accountNumber === null ? null : substr($statement->accountNumber, -4),
                'document_period_start' => $statement->periodStart,
                'document_period_end' => $statement->periodEnd,
                'opening_balance' => $statement->openingBalance,
                'closing_balance' => $statement->closingBalance,
                'total_credits' => $statement->totalCredits,
                'total_debits' => $statement->totalDebits,
                'statement_row_count' => count($statement->rows),
                'warnings' => $warnings,
            ]);

            $entryIdsByIndex = [];
            foreach ($analysis['statement_entries'] as $index => $attributes) {
                $duplicateIndex = $attributes['duplicate_of_index'];
                unset($attributes['duplicate_of_index']);
                $attributes['import_row_hash'] = hash('sha256', $account->id.'|'.$attributes['import_row_hash']);
                $attributes['duplicate_of_entry_id'] = $duplicateIndex === null ? null : ($entryIdsByIndex[$duplicateIndex] ?? null);
                $entry = $reconciliation->entries()->create($attributes);
                $entryIdsByIndex[$index] = $entry->id;
            }
            foreach ($analysis['system_entries'] as $attributes) {
                unset($attributes['duplicate_of_index']);
                $reconciliation->entries()->create($attributes);
            }

            return $reconciliation->load($this->resourceRelations());
        });
    }

    public function findAuthorized(int $reconciliationId, User $user): BankReconciliation
    {
        $reconciliation = BankReconciliation::query()->with($this->resourceRelations())->findOrFail($reconciliationId);
        $this->membershipAuthorization->authorize($user, $reconciliation->wallet, \App\Enums\WalletMemberRole::EDITOR, \App\Enums\WalletMemberRole::OWNER);

        return $reconciliation;
    }

    /** @param array<int, int> $entryIds
     * @return array{reconciliation: BankReconciliation, imported: int, already_imported: int, skipped: int}
     */
    public function import(int $reconciliationId, array $entryIds, bool $acknowledgeWarnings, User $user): array
    {
        return DB::transaction(function () use ($reconciliationId, $entryIds, $acknowledgeWarnings, $user): array {
            $reconciliation = BankReconciliation::query()->with('wallet')->lockForUpdate()->find($reconciliationId);
            if ($reconciliation === null) {
                throw (new ModelNotFoundException)->setModel(BankReconciliation::class, [$reconciliationId]);
            }
            $member = $this->membershipAuthorization->authorize($user, $reconciliation->wallet, \App\Enums\WalletMemberRole::EDITOR, \App\Enums\WalletMemberRole::OWNER);
            $warnings = $reconciliation->warnings ?? [];
            $requiresAcknowledgment = array_intersect($warnings, ['ACCOUNT_NUMBER_MISMATCH', 'PERIOD_MISMATCH']) !== [];
            if ($requiresAcknowledgment && ! $acknowledgeWarnings) {
                throw ValidationException::withMessages(['acknowledge_warnings' => 'Confirme que deseja importar mesmo com a conta ou competência diferente do extrato.']);
            }

            $entries = $reconciliation->entries()->whereIn('id', $entryIds)->lockForUpdate()->get();
            if ($entries->count() !== count(array_unique($entryIds))) {
                throw ValidationException::withMessages(['entry_ids' => 'Uma ou mais linhas não pertencem a esta conciliação. Atualize a prévia e tente novamente.']);
            }

            $imported = 0;
            $alreadyImported = 0;
            $skipped = 0;
            foreach ($entries as $entry) {
                if ($entry->entry_kind !== 'STATEMENT' || $entry->match_status !== 'ONLY_IN_STATEMENT') {
                    $skipped++;
                    continue;
                }
                if ($entry->imported_transaction_id !== null) {
                    $alreadyImported++;
                    continue;
                }

                $existing = Transaction::query()
                    ->where('wallet_id', $reconciliation->wallet_id)
                    ->where('import_row_hash', $entry->import_row_hash)
                    ->first();
                if ($existing !== null) {
                    $entry->imported_transaction_id = $existing->id;
                    $entry->save();
                    $alreadyImported++;
                    continue;
                }

                $type = $entry->signed_amount > 0 ? TransactionType::INCOME : TransactionType::EXPENSE;
                try {
                    $transaction = $this->transactions->create(TransactionDTO::fromArray([
                        'wallet_id' => $reconciliation->wallet_id,
                        'account_id' => $reconciliation->account_id,
                        'description' => Str::limit($entry->description, 255, ''),
                        'type' => $type->value,
                        'effect' => ($type === TransactionType::INCOME ? TransactionEffect::CREDIT : TransactionEffect::DEBIT)->value,
                        'amount' => abs($entry->signed_amount),
                        'financial_instrument_type' => FinancialInstrumentType::ACCOUNT->value,
                        'transaction_date' => $entry->transaction_date->toDateString(),
                        'competence_date' => $entry->transaction_date->toDateString(),
                        'due_date' => $entry->transaction_date->toDateString(),
                        'status' => TransactionStatus::POSTED->value,
                        'paid_at' => $entry->transaction_date->toDateString().' 12:00:00',
                        'notes' => $entry->external_id ? 'ID do extrato: '.$entry->external_id : null,
                        'import_row_hash' => $entry->import_row_hash,
                    ], $member->id));
                } catch (QueryException $exception) {
                    if (($exception->errorInfo[1] ?? null) !== 1062) {
                        throw $exception;
                    }

                    $transaction = Transaction::query()
                        ->where('wallet_id', $reconciliation->wallet_id)
                        ->where('import_row_hash', $entry->import_row_hash)
                        ->first();
                    if ($transaction === null) {
                        throw $exception;
                    }
                    $alreadyImported++;
                }
                $entry->imported_transaction_id = $transaction->id;
                $entry->save();
                $imported++;
            }

            $reconciliation->imported_count = $reconciliation->entries()
                ->where('entry_kind', 'STATEMENT')
                ->whereNotNull('imported_transaction_id')
                ->count();
            $remaining = $reconciliation->entries()
                ->where('entry_kind', 'STATEMENT')
                ->where('match_status', 'ONLY_IN_STATEMENT')
                ->whereNull('imported_transaction_id')
                ->exists();
            $reconciliation->status = $remaining ? ($reconciliation->imported_count > 0 ? 'PARTIAL' : 'PREVIEW') : 'COMPLETED';
            $reconciliation->save();

            return [
                'reconciliation' => $reconciliation->load($this->resourceRelations()),
                'imported' => $imported,
                'already_imported' => $alreadyImported,
                'skipped' => $skipped,
            ];
        });
    }

    /** @return array<int, string> */
    private function documentWarnings(BankStatementData $statement, Account $account, string $month): array
    {
        $warnings = [];
        $accountNumber = preg_replace('/\D/', '', (string) $account->account_number);
        if ($statement->accountNumber !== null && $accountNumber !== '' && $accountNumber !== $statement->accountNumber) {
            $warnings[] = 'ACCOUNT_NUMBER_MISMATCH';
        }
        if (($statement->periodStart !== null && substr($statement->periodStart, 0, 7) !== $month)
            || ($statement->periodEnd !== null && substr($statement->periodEnd, 0, 7) !== $month)
            || collect($statement->rows)->contains(fn (array $row): bool => substr($row['date'], 0, 7) !== $month)) {
            $warnings[] = 'PERIOD_MISMATCH';
        }

        return $warnings;
    }

    private function validateStatement(BankStatementData $statement): void
    {
        if ($statement->rows === []) {
            throw ValidationException::withMessages(['file' => 'O extrato não possui lançamentos reconhecidos. Confira o mês informado e o arquivo.']);
        }
        foreach ($statement->rows as $row) {
            if ($row['signed_amount'] === 0) {
                throw ValidationException::withMessages(['file' => 'O extrato contém um lançamento de valor zero, que não pode ser importado.']);
            }
        }
    }

    /** @return array<int, string> */
    private function resourceRelations(): array
    {
        return [
            'account:id,name,account_number,bank_code',
            'entries.matchedTransaction:id,description,amount,effect,status,transaction_date',
            'entries.suggestedTransaction:id,description,amount,effect,status,transaction_date',
            'entries.importedTransaction:id,description,amount,effect,status,transaction_date',
        ];
    }
}
