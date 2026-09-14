import test from 'node:test';
import assert from 'node:assert/strict';
import { canPayCreditCardInvoice } from '../../resources/js/Pages/Financial/Hooks/invoiceActions.js';

test('allows payment for an unpaid invoice with a positive balance', () => {
    assert.equal(canPayCreditCardInvoice({ status: 'OPEN', amount: 1000 }), true);
    assert.equal(canPayCreditCardInvoice({ status: 'open', amount: 1000 }), true);
    assert.equal(canPayCreditCardInvoice({ status: 'OVERDUE', amount: 1000 }), true);
});

test('does not allow payment for a paid or empty invoice', () => {
    assert.equal(canPayCreditCardInvoice({ status: 'PAID', amount: 1000 }), false);
    assert.equal(canPayCreditCardInvoice({ status: 'OPEN', amount: 0 }), false);
});
