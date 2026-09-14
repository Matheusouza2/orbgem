import test from 'node:test';
import assert from 'node:assert/strict';
import { buildCreditCardInstallmentUpdatePayload } from '../../resources/js/Pages/Financial/Hooks/creditCardTransactionPayload.js';

test('builds the installment payload expected by imported Pluggy card transactions', () => {
    const payload = buildCreditCardInstallmentUpdatePayload({
        description: 'POSTO MARACAYPE',
        amount: '205.39',
        transaction_date: '2026-08-31',
        category_id: '3',
        merchant_id: '',
        is_third_party: false,
        purchase_date: '2026-08-31',
        total_amount: 20539,
    });

    assert.deepEqual(payload, {
        description: 'POSTO MARACAYPE',
        amount: 20539,
        transaction_date: '2026-08-31',
        category_id: 3,
        merchant_id: null,
        is_third_party: false,
    });
});
