import { Button, Label, Textarea } from 'flowbite-react';
import { RotateCcw, Send, Sparkles } from 'lucide-react';
import { Fragment, useEffect, useRef } from 'react';
import { MAX_MESSAGE_LENGTH } from '../Hooks/useAssistant';

const suggestions = [
    'Como estão meus gastos neste mês comparados aos anteriores?',
    'Onde posso economizar com base nas minhas despesas?',
    'Minha carteira de investimentos está bem diversificada?',
    'Qual categoria pesa mais no meu orçamento?',
];

const inline = (text) => text.split(/(\*\*[^*]+\*\*)/g).map((part, index) => part.startsWith('**') && part.endsWith('**') && part.length > 4 ? <strong key={index}>{part.slice(2, -2)}</strong> : <Fragment key={index}>{part}</Fragment>);

/** Renderiza o texto do modelo (parágrafos, listas com "-" e **negrito**) sem HTML bruto. */
function FormattedText({ text }) {
    return text.split(/\n{2,}/).map((block, blockIndex) => {
        const lines = block.split('\n').filter(Boolean);
        if (lines.length > 0 && lines.every((line) => /^\s*([-*•]|\d+\.)\s+/.test(line))) {
            return <ul key={blockIndex} className="my-2 list-disc space-y-1 pl-5">{lines.map((line, index) => <li key={index}>{inline(line.replace(/^\s*([-*•]|\d+\.)\s+/, ''))}</li>)}</ul>;
        }

        return <p key={blockIndex} className="whitespace-pre-line [&:not(:first-child)]:mt-2">{inline(block)}</p>;
    });
}

function Message({ message, onRetry, retryDisabled }) {
    const isUser = message.role === 'user';
    const bubble = isUser ? 'ml-auto bg-orbital-primary text-white' : message.error ? 'border border-orbital-accent-dark/40 bg-[#fff8e7] text-orbital-primary-dark' : 'border border-orbital-border bg-orbital-primary-light/60 text-orbital-text-primary';

    return <li className={`flex max-w-[92%] flex-col gap-1 sm:max-w-[85%] ${isUser ? 'ml-auto items-end' : 'items-start'}`}>
        <span className="text-[11px] font-semibold uppercase tracking-wider text-orbital-text-secondary">{isUser ? 'Você' : 'Assistente'}</span>
        <div className={`min-w-0 break-words rounded-2xl px-4 py-3 text-sm leading-6 ${bubble}`} role={message.error ? 'alert' : undefined}>
            <FormattedText text={message.content} />
            {message.error && <Button color="light" size="xs" className="mt-3" onClick={() => onRetry(message.retry)} disabled={retryDisabled}><RotateCcw className="mr-1.5 h-3.5 w-3.5" aria-hidden="true" />Tentar novamente</Button>}
        </div>
    </li>;
}

export default function AssistantChat({ messages, draft, onDraftChange, onSend, sending, disabled }) {
    const logRef = useRef(null);
    const inputRef = useRef(null);
    const remaining = MAX_MESSAGE_LENGTH - draft.length;
    const canSend = !disabled && !sending && draft.trim() !== '' && remaining >= 0;

    useEffect(() => {
        if (logRef.current) logRef.current.scrollTop = logRef.current.scrollHeight;
    }, [messages, sending]);

    const submit = (event) => {
        event.preventDefault();
        if (!canSend) return;
        onSend();
        inputRef.current?.focus();
    };

    const onKeyDown = (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) submit(event);
    };

    return <section className="ledger-panel flex min-w-0 flex-col gap-4" aria-labelledby="assistant-chat-title">
        <h2 id="assistant-chat-title" className="ledger-title flex items-center gap-2"><Sparkles className="h-5 w-5 text-orbital-accent-dark" aria-hidden="true" />Converse com o assistente</h2>

        <div ref={logRef} className="max-h-[55dvh] min-h-64 overflow-y-auto rounded-xl bg-orbital-background p-3 sm:p-4" tabIndex={0} role="log" aria-label="Conversa com o assistente" aria-live="polite" aria-relevant="additions">
            {messages.length === 0
                ? <div className="grid h-full content-center gap-3 py-4 text-center">
                    <p className="font-semibold text-orbital-primary-dark">Pergunte sobre as finanças desta carteira.</p>
                    <p className="text-sm text-orbital-text-secondary">As respostas usam a análise mais recente dos seus lançamentos e investimentos.</p>
                    <ul className="mx-auto mt-1 flex max-w-xl flex-wrap justify-center gap-2" aria-label="Sugestões de perguntas">
                        {suggestions.map((suggestion) => <li key={suggestion}><button type="button" className="rounded-full border border-orbital-border bg-orbital-surface px-3 py-2 text-left text-xs font-medium text-orbital-primary transition hover:bg-orbital-primary-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orbital-accent disabled:cursor-not-allowed disabled:opacity-50" onClick={() => onSend(suggestion)} disabled={disabled || sending}>{suggestion}</button></li>)}
                    </ul>
                </div>
                : <ol className="grid gap-4">
                    {messages.map((message) => <Message key={message.id} message={message} onRetry={onSend} retryDisabled={sending} />)}
                    {sending && <li className="text-sm text-orbital-text-secondary" role="status">O assistente está analisando…</li>}
                </ol>}
        </div>

        <form onSubmit={submit} className="grid gap-2">
            <Label htmlFor="assistant-message" className="text-xs font-semibold !text-orbital-text-primary">Sua pergunta</Label>
            <Textarea id="assistant-message" ref={inputRef} rows={3} value={draft} onChange={(event) => onDraftChange(event.target.value)} onKeyDown={onKeyDown} disabled={disabled} aria-describedby="assistant-message-help" placeholder="Ex.: posso gastar mais em lazer este mês?" className="block w-full resize-y rounded-lg border !border-orbital-border !bg-orbital-surface !text-orbital-text-primary placeholder-orbital-text-secondary focus:border-orbital-accent focus:outline-none focus:ring-2 focus:ring-orbital-accent/30 disabled:cursor-not-allowed disabled:opacity-50" />
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p id="assistant-message-help" className={`text-xs ${remaining < 0 ? 'font-semibold text-red-600' : 'text-orbital-text-secondary'}`}>Enter envia · Shift+Enter quebra a linha · {remaining} caracteres restantes</p>
                <Button type="submit" color="blue" disabled={!canSend}><Send className="mr-2 h-4 w-4" aria-hidden="true" />{sending ? 'Enviando…' : 'Enviar'}</Button>
            </div>
        </form>
        <p className="text-xs leading-5 text-orbital-text-secondary">Respostas geradas por IA a partir de um resumo dos seus dados, sem nomes, e-mails ou documentos. Não são recomendação personalizada de investimento nem garantia de retorno; confira valores importantes nas telas do app.</p>
    </section>;
}
