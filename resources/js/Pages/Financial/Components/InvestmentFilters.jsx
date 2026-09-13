import { Suspense } from 'react';
import Inputs from '@/Components/Inputs';

const typeOptions = [
    { value: 'STOCK', label: 'Ação' },
    { value: 'FUND', label: 'Fundo' },
    { value: 'FII', label: 'FII' },
    { value: 'FIXED_INCOME', label: 'Renda fixa' },
    { value: 'CRYPTO', label: 'Cripto' },
    { value: 'OTHER', label: 'Outro' },
];

export default function InvestmentFilters({ investments, filters, onChange }) {
    const institutionOptions = [...new Set(investments.map((investment) => investment.institution).filter(Boolean))]
        .sort()
        .map((institution) => ({ value: institution, label: institution }));

    return <Suspense fallback={<div className="mb-6 h-16" aria-hidden="true" />}><div className="mb-6 grid gap-3 rounded-2xl border border-orbital-border bg-orbital-surface p-4 sm:grid-cols-2"><Inputs.Select name="investment_type_filter" label="Tipo" value={typeOptions.find((option) => option.value === filters.type) ?? null} onChange={(option) => onChange('type', option?.value ?? '')} options={typeOptions} /><Inputs.Select name="investment_institution_filter" label="Instituição" value={institutionOptions.find((option) => option.value === filters.institution) ?? null} onChange={(option) => onChange('institution', option?.value ?? '')} options={institutionOptions} /></div></Suspense>;
}
