const today = new Date().toISOString().slice(0, 10);

const AccountTransfer = {
    from_account_id: '',
    to_account_id: '',
    amount: '',
    transaction_date: today,
    competence_date: today,
    payment_channel: 'PIX',
    notes: '',
};

export default AccountTransfer;
