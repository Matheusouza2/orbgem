import test from 'node:test';
import assert from 'node:assert/strict';
import { buildTransactionUpdatePayload } from '../../resources/js/Pages/Financial/Hooks/transactionUpdatePayload.js';

test('removes recurrence and installment fields from account transaction updates', () => {
    const payload = buildTransactionUpdatePayload({
        wallet_id: 7,
        account_id: 9,
        description: 'Compra corrigida',
        type: 'EXPENSE',
        amount: 12500,
        financial_instrument_type: 'ACCOUNT',
        transaction_date: '2026-09-14',
        competence_date: '2026-09-14',
        recurrence_type: 'INSTALLMENT',
        installment_initial: 2,
        installment_count: 12,
        installment_periodicity: 'MONTHLY',
        effect: 'DEBIT',
        credit_card_id: null,
    });

    assert.equal(payload.wallet_id, 7);
    assert.equal(payload.account_id, 9);
    assert.equal(payload.description, 'Compra corrigida');
    assert.equal('recurrence_type' in payload, false);
    assert.equal('installment_initial' in payload, false);
    assert.equal('installment_count' in payload, false);
    assert.equal('installment_periodicity' in payload, false);
    assert.equal('effect' in payload, false);
    assert.equal('credit_card_id' in payload, false);
});
