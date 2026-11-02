import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { Button, Modal, ModalBody, ModalFooter, ModalHeader, ThemeProvider } from 'flowbite-react';
import { CircleAlert, ShieldAlert } from 'lucide-react';
import Theme from '@/Themes';

const ConfirmContext = createContext(null);

export function useConfirm() {
    const confirm = useContext(ConfirmContext);

    if (!confirm) throw new Error('useConfirm deve ser usado dentro de ConfirmProvider.');

    return confirm;
}

export default function ConfirmProvider({ children }) {
    const [request, setRequest] = useState(null);
    const resolver = useRef(null);
    const cancelButton = useRef(null);

    const confirm = useCallback((options) => new Promise((resolve) => {
        resolver.current?.(false);
        resolver.current = resolve;
        setRequest({
            title: options.title,
            message: options.message,
            confirmLabel: options.confirmLabel ?? 'Confirmar',
            cancelLabel: options.cancelLabel ?? 'Cancelar',
            tone: options.tone ?? 'danger',
        });
    }), []);

    const finish = useCallback((accepted) => {
        const resolve = resolver.current;
        resolver.current = null;
        setRequest(null);
        resolve?.(accepted);
    }, []);

    useEffect(() => () => resolver.current?.(false), []);

    return <ConfirmContext.Provider value={confirm}>
        {children}
        <ThemeProvider theme={Theme}>
            <Modal
                show={Boolean(request)}
                onClose={() => finish(false)}
                dismissible
                size="md"
                initialFocus={cancelButton}
                className={`action-confirmation-modal action-confirmation-modal--${request?.tone ?? 'danger'}`}
                aria-labelledby="action-confirmation-title"
                aria-describedby="action-confirmation-description"
            >
                <ModalHeader className="action-confirmation__header">
                    <span className="action-confirmation__mark" aria-hidden="true">
                        {request?.tone === 'warning' ? <CircleAlert className="h-5 w-5" /> : <ShieldAlert className="h-5 w-5" />}
                    </span>
                    <span className="action-confirmation__heading">
                        <span className="action-confirmation__eyebrow">Confirme esta ação</span>
                        <span id="action-confirmation-title" className="action-confirmation__title">{request?.title}</span>
                    </span>
                </ModalHeader>
                <ModalBody className="action-confirmation__body">
                    <p id="action-confirmation-description">{request?.message}</p>
                </ModalBody>
                <ModalFooter className="action-confirmation__footer">
                    <Button ref={cancelButton} type="button" color="light" className="action-confirmation__cancel" onClick={() => finish(false)}>{request?.cancelLabel}</Button>
                    <Button type="button" color="blue" className="action-confirmation__accept" onClick={() => finish(true)}>{request?.confirmLabel}</Button>
                </ModalFooter>
            </Modal>
        </ThemeProvider>
    </ConfirmContext.Provider>;
}
