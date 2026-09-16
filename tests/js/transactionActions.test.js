import test from 'node:test';
import assert from 'node:assert/strict';
import { canEditCreditCardTransaction, canEffectivateTransaction } from '../../resources/js/Pages/Financial/Hooks/transactionActions.js';
import { canManageTransaction } from '../../resources/js/Pages/Financial/Hooks/dashboardState.js';

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

test('allows managing an account transaction even when it has no effect', () => {
    assert.equal(canManageTransaction({
        financial_instrument_type: 'ACCOUNT',
        effect: 'NONE',
        type: 'EXPENSE',
    }), true);
});

test('shows effectivation only for manageable projected transactions', () => {
    assert.equal(canEffectivateTransaction({ status: 'PROJECTED', canManage: true }), true);
    assert.equal(canEffectivateTransaction({ status: 'PROJECTED', canManage: false, recurring_transaction_id: 12 }), true);
    assert.equal(canEffectivateTransaction({ status: 'POSTED', canManage: true }), false);
    assert.equal(canEffectivateTransaction({ status: 'PROJECTED', canManage: false }), false);
    assert.equal(canEffectivateTransaction({ status: 'PROJECTED', can_edit: false }), false);
});

test('allows editing a standalone card transaction from an unpaid invoice', () => {
    assert.equal(canEditCreditCardTransaction({
        can_edit: true,
        can_delete: true,
        invoice: { status: 'OPEN' },
    }), true);
});
