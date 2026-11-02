<?php

namespace App\Services\BankReconciliation;

use App\DTO\BankStatementData;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReconciliationMatcher
{
    /**
     * @param Collection<int, Transaction> $transactions
     * @return array{statement_entries: array<int, array<string, mixed>>, system_entries: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function analyze(BankStatementData $statement, Collection $transactions): array
    {
        $statementEntries = [];
        $claimedTransactionIds = [];
        $seenOperationIds = [];
        $seenSignatures = [];

        foreach ($statement->rows as $index => $row) {
            $availableTransactions = $transactions->reject(fn (Transaction $transaction): bool => isset($claimedTransactionIds[$transaction->id]));
            $normalized = $this->normalize($row['description']);
            $signature = implode('|', [$row['date'], $row['signed_amount'], $normalized]);
            $duplicateIndex = null;
            if ($row['external_id'] !== null && isset($seenOperationIds[$row['external_id']])) {
                $duplicateIndex = $seenOperationIds[$row['external_id']];
            } elseif (isset($seenSignatures[$signature])) {
                $duplicateIndex = $seenSignatures[$signature];
            }

            if ($row['external_id'] !== null) {
                $seenOperationIds[$row['external_id']] ??= $index;
            }
            $seenSignatures[$signature] ??= $index;

            $entry = [
                'entry_kind' => 'STATEMENT',
                'external_id' => $row['external_id'],
                'transaction_date' => $row['date'],
                'description' => $row['description'],
                'signed_amount' => $row['signed_amount'],
                'balance_after' => $row['balance_after'],
                'match_status' => 'ONLY_IN_STATEMENT',
                'matched_transaction_id' => null,
                'suggested_transaction_id' => null,
                'duplicate_of_index' => $duplicateIndex,
                'import_row_hash' => $this->importHash($row['external_id'], $row['date'], $row['signed_amount'], $normalized),
                'flags' => [],
            ];

            if ($duplicateIndex !== null) {
                $entry['match_status'] = 'DUPLICATE_IN_STATEMENT';
            } else {
                $exactPosted = $availableTransactions->filter(fn (Transaction $transaction): bool => $this->signedAmount($transaction) === $row['signed_amount']
                    && $transaction->transaction_date?->toDateString() === $row['date']
                    && $transaction->status->value === 'POSTED');
                $exactProjected = $availableTransactions->first(fn (Transaction $transaction): bool => $this->signedAmount($transaction) === $row['signed_amount']
                    && $transaction->transaction_date?->toDateString() === $row['date']
                    && $transaction->status->value === 'PROJECTED');

                if ($exactPosted->count() === 1) {
                    $transaction = $exactPosted->first();
                    $entry['match_status'] = 'MATCHED';
                    $entry['matched_transaction_id'] = $transaction->id;
                    $claimedTransactionIds[$transaction->id] = true;
                } elseif ($exactPosted->count() > 1) {
                    $transaction = $exactPosted->first();
                    $entry['match_status'] = 'DUPLICATE_IN_SYSTEM';
                    $entry['suggested_transaction_id'] = $transaction->id;
                    foreach ($exactPosted as $duplicate) {
                        $claimedTransactionIds[$duplicate->id] = true;
                    }
                } elseif ($exactProjected !== null) {
                    $entry['match_status'] = 'NOT_POSTED_IN_SYSTEM';
                    $entry['suggested_transaction_id'] = $exactProjected->id;
                    $claimedTransactionIds[$exactProjected->id] = true;
                } else {
                    $candidate = $this->dateCandidate($row, $availableTransactions);
                    if ($candidate !== null) {
                        $entry['match_status'] = 'DATE_DIVERGENCE';
                        $entry['suggested_transaction_id'] = $candidate->id;
                        $claimedTransactionIds[$candidate->id] = true;
                    } else {
                        $candidate = $this->amountCandidate($row, $availableTransactions);
                        if ($candidate !== null) {
                            $entry['match_status'] = 'AMOUNT_DIVERGENCE';
                            $entry['suggested_transaction_id'] = $candidate->id;
                            $claimedTransactionIds[$candidate->id] = true;
                        }
                    }
                }
            }

            $statementEntries[] = $entry;
        }

        $this->appendBalanceFlags($statement, $statementEntries);
        $systemEntries = [];
        $seenSystemSignatures = [];
        foreach ($transactions as $transaction) {
            if (isset($claimedTransactionIds[$transaction->id])) {
                $signature = implode('|', [$transaction->transaction_date?->toDateString(), $this->signedAmount($transaction), $this->normalize($transaction->description)]);
                $seenSystemSignatures[$signature] = true;
                continue;
            }

            $signature = implode('|', [$transaction->transaction_date?->toDateString(), $this->signedAmount($transaction), $this->normalize($transaction->description)]);
            $isDuplicate = isset($seenSystemSignatures[$signature]);
            $seenSystemSignatures[$signature] = true;
            $systemEntries[] = [
                'entry_kind' => 'SYSTEM',
                'external_id' => null,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'description' => $transaction->description,
                'signed_amount' => $this->signedAmount($transaction),
                'balance_after' => null,
                'match_status' => $isDuplicate ? 'DUPLICATE_IN_SYSTEM' : ($transaction->status->value === 'PROJECTED' ? 'PROJECTED_IN_SYSTEM' : 'ONLY_IN_SYSTEM'),
                'matched_transaction_id' => $transaction->id,
                'suggested_transaction_id' => null,
                'duplicate_of_index' => null,
                'import_row_hash' => null,
                'flags' => [],
            ];
        }

        $warnings = [];
        if ($statement->openingBalance !== null && $statement->closingBalance !== null && $statementEntries !== []) {
            $lastBalance = $statementEntries[array_key_last($statementEntries)]['balance_after'];
            if ($lastBalance !== null && $lastBalance !== $statement->closingBalance) {
                $warnings[] = 'CLOSING_BALANCE_MISMATCH';
            }
        }
        if ($statement->totalCredits !== null) {
            $credits = array_sum(array_map(fn (array $row): int => max(0, $row['signed_amount']), $statement->rows));
            if ($credits !== $statement->totalCredits) {
                $warnings[] = 'CREDITS_TOTAL_MISMATCH';
            }
        }
        if ($statement->totalDebits !== null) {
            $debits = abs(array_sum(array_map(fn (array $row): int => min(0, $row['signed_amount']), $statement->rows)));
            if ($debits !== abs($statement->totalDebits)) {
                $warnings[] = 'DEBITS_TOTAL_MISMATCH';
            }
        }

        return ['statement_entries' => $statementEntries, 'system_entries' => $systemEntries, 'warnings' => array_values(array_unique($warnings))];
    }

    /** @param array{date: string, description: string, signed_amount: int} $row
     * @param Collection<int, Transaction> $transactions
     */
    private function dateCandidate(array $row, Collection $transactions): ?Transaction
    {
        $candidates = $transactions->filter(function (Transaction $transaction) use ($row): bool {
            $difference = abs(Carbon::parse($transaction->transaction_date)->diffInDays(Carbon::parse($row['date']), false));

            return $difference > 0 && $difference <= 3
                && $this->signedAmount($transaction) === $row['signed_amount']
                && $this->descriptionSimilarity($transaction->description, $row['description']) >= 0.35;
        });

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /** @param array{date: string, description: string, signed_amount: int} $row
     * @param Collection<int, Transaction> $transactions
     */
    private function amountCandidate(array $row, Collection $transactions): ?Transaction
    {
        $candidates = $transactions->filter(fn (Transaction $transaction): bool => $transaction->transaction_date?->toDateString() === $row['date']
            && $this->signedAmount($transaction) !== $row['signed_amount']
            && $this->descriptionSimilarity($transaction->description, $row['description']) >= 0.35);

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function appendBalanceFlags(BankStatementData $statement, array &$entries): void
    {
        $expected = $statement->openingBalance;
        foreach ($entries as &$entry) {
            if ($expected !== null && $entry['balance_after'] !== null && $expected + $entry['signed_amount'] !== $entry['balance_after']) {
                $entry['flags'][] = 'RUNNING_BALANCE_MISMATCH';
            }
            if ($entry['balance_after'] !== null) {
                $expected = $entry['balance_after'];
            }
        }
        unset($entry);
    }

    private function signedAmount(Transaction $transaction): int
    {
        return $transaction->effect->value === 'CREDIT' ? $transaction->amount : -$transaction->amount;
    }

    private function descriptionSimilarity(string $first, string $second): float
    {
        $left = array_values(array_unique(explode(' ', $this->normalize($first))));
        $right = array_values(array_unique(explode(' ', $this->normalize($second))));
        $union = array_unique([...$left, ...$right]);
        $intersection = array_intersect($left, $right);

        return count($union) === 0 ? 0 : count($intersection) / count($union);
    }

    private function normalize(string $description): string
    {
        $ascii = Str::ascii(mb_strtolower($description));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? $ascii);
    }

    private function importHash(?string $externalId, string $date, int $amount, string $description): string
    {
        return hash('sha256', implode('|', [$externalId ? 'operation:'.$externalId : 'row', $date, $amount, $description]));
    }
}
