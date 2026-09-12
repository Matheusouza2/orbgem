import { Button } from 'flowbite-react';

export default function MovementActions({ onTransfer }) {
    return <section className="ledger-actions" aria-labelledby="daily-actions-title"><div><p className="ledger-eyebrow">Movimentação diária</p><h2 id="daily-actions-title" className="ledger-title">Dinheiro em trânsito</h2><p className="mt-1 text-sm text-slate-500">Mova valores entre suas contas sem registrar uma nova despesa.</p></div><Button color="blue" onClick={onTransfer}>Transferir entre contas</Button></section>;
}
