import test from 'node:test';
import assert from 'node:assert/strict';
import { canEditCreditCardTransaction } from '../../resources/js/Pages/Financial/Hooks/transactionActions.js';

test('allows editing an open internal card movement from its installment and purchase data', () => {
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        can_edit: false,
        invoice: { status: 'OPEN' },
    }), true);
});

test('allows editing when the API marks the movement as editable', () => {
    assert.equal(canEditCreditCardTransaction({
        can_edit: true,
        invoice: { status: 'OPEN' },
    }), true);
});

test('allows editing a movement from a closed or overdue card invoice', () => {
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        can_edit: true,
        invoice: { status: 'CLOSED' },
    }), true);
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        can_edit: true,
        invoice: { status: 'OVERDUE' },
    }), true);
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        invoice: { status: 'open' },
    }), true);
});

test('does not allow editing a movement from a paid card invoice', () => {
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        can_edit: true,
        invoice: { status: 'PAID' },
    }), false);
    assert.equal(canEditCreditCardTransaction({
        purchase_id: 21,
        purchase: { id: 21 },
        installment: { number: 1, total: 1 },
        invoice: { status: 'paid' },
    }), false);
});
