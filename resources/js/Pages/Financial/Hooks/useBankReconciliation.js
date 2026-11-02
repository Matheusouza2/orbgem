import { useState } from 'react';
import FinancialService from '@/Services/FinancialService';

const currentMonth = () => {
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
};

export default function useBankReconciliation(onImported = () => {}) {
    const [open, setOpen] = useState(false);
    const [reviewing, setReviewing] = useState(false);
    const [loading, setLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [month, setMonth] = useState(currentMonth());
    const [accountId, setAccountId] = useState('');
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [selectedEntryIds, setSelectedEntryIds] = useState([]);
    const [acknowledgeWarnings, setAcknowledgeWarnings] = useState(false);
    const [filter, setFilter] = useState('ALL');
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');

    const start = () => {
        setOpen(true);
        setErrors({});
        setMessage('');
    };

    const close = () => {
        if (loading || importing) return;
        setOpen(false);
    };

    const createPreview = async (event) => {
        event.preventDefault();
        if (!file || !accountId || !month) return;
        setLoading(true);
        setErrors({});
        setMessage('');
        try {
            const formData = new FormData();
            formData.append('account_id', accountId);
            formData.append('month', month);
            formData.append('file', file);
            const response = await FinancialService.previewBankReconciliation(formData);
            const data = response.data;
            setPreview(data);
            setSelectedEntryIds((data.entries ?? []).filter((entry) => entry.can_import).map((entry) => entry.id));
            setAcknowledgeWarnings(false);
            setFilter('ALL');
            setReviewing(true);
        } catch (error) {
            setErrors(error?.errors ?? { general: error.message ?? 'Não foi possível ler este extrato.' });
        } finally {
            setLoading(false);
        }
    };

    const importSelected = async () => {
        if (!preview || selectedEntryIds.length === 0) return;
        setImporting(true);
        setErrors({});
        setMessage('');
        try {
            const response = await FinancialService.importBankReconciliation(preview.id, {
                entry_ids: selectedEntryIds,
                acknowledge_warnings: acknowledgeWarnings,
            });
            setPreview(response.data);
            setSelectedEntryIds([]);
            setMessage(`${response.imported} lançamento${response.imported === 1 ? '' : 's'} importado${response.imported === 1 ? '' : 's'}.`);
            try {
                await onImported();
            } catch {
                setErrors({ general: 'Os lançamentos foram importados, mas não foi possível atualizar os saldos da tela.' });
            }
        } catch (error) {
            setErrors(error?.errors ?? { general: error.message ?? 'Não foi possível importar os lançamentos.' });
        } finally {
            setImporting(false);
        }
    };

    const toggleEntry = (entryId) => setSelectedEntryIds((current) => current.includes(entryId)
        ? current.filter((id) => id !== entryId)
        : [...current, entryId]);

    const returnToUpload = () => {
        setReviewing(false);
        setErrors({});
        setMessage('');
    };

    return {
        open, start, close, reviewing, returnToUpload, loading, importing, month, setMonth,
        accountId, setAccountId, file, setFile, preview, selectedEntryIds, toggleEntry,
        acknowledgeWarnings, setAcknowledgeWarnings, filter, setFilter, errors, message,
        createPreview, importSelected,
    };
}
