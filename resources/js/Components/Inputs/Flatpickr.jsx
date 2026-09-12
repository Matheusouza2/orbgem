import Flatpickr from "react-flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";
import "flatpickr/dist/flatpickr.min.css";
import { HelperText, Label } from "flowbite-react";
import { useEffect, useRef } from "react";
import IMask from "imask";
import { Xmark } from "../Icons/solid";

export default function InputFlatpickr({
    setData,
    onChange: userOnChange,
    options: userOptions,
    className = "",
    errors,
    monthYearOnly = false,
    labelLight = false,
    name,
    value,
    label,
    mode,
    clearable,
    disabled,
    placeholder,
    mindate,
    maxdate,
}) {
    const errorMessage = errors?.[name];
    const hasError = Boolean(errorMessage);

    const maskRef = useRef(null);
    const fpInstanceRef = useRef(null); // guarda a instância do Flatpickr para poder chamar .clear()

    // Modo controlado externamente (ex: TableFilter via onChange)
    const isControlled = typeof userOnChange === "function";

    // Destroi a mask quando monthYearOnly muda, evitando erro de IMask
    // com input DOM stale (ex: alternar entre Diário e Mensal)
    useEffect(() => {
        if (monthYearOnly) {
            maskRef.current?.destroy();
            maskRef.current = null;
        }
    }, [monthYearOnly]);

    const handleClear = () => {
        fpInstanceRef.current?.clear();
        // clear() dispara onChange([], "", instance) → tratado no onChange abaixo
    };

    return (
            <div className={`flatpickr-field${hasError ? " flatpickr-field--error" : ""} ${className}`}>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"
                } ${labelLight ? "!text-orbital-primary-light" : ""}`}
                htmlFor={name}
            >
                {label}
            </Label>

            <Flatpickr
                id={name}
                name={name}
                value={value}
                disabled={disabled}
                placeholder={placeholder}
                className="flatpickr-field__input"
                options={{
                    ...userOptions,
                    allowInput: true,
                    altInput: true,
                    altInputClass: "flatpickr-field__input",
                    dateFormat: monthYearOnly ? "Y-m" : "Y-m-d",
                    altFormat: monthYearOnly ? "F/Y" : "d/m/Y",
                    mode: mode || "single",
                    minRange: 0,
                    plugins: monthYearOnly
                        ? [
                            new monthSelectPlugin({
                                shorthand: true,
                                dateFormat: "Y-m",
                                altFormat: "F/Y",
                            }),
                        ]
                        : [],
                    locale: {
                        firstDayOfWeek: 0,
                        rangeSeparator: " até ",
                        weekdays: {
                            shorthand: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
                            longhand: [
                                "Domingo",
                                "Segunda-feira",
                                "Terça-feira",
                                "Quarta-feira",
                                "Quinta-feira",
                                "Sexta-feira",
                                "Sábado",
                            ],
                        },
                        months: {
                            shorthand: [
                                "Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
                                "Jul", "Ago", "Set", "Out", "Nov", "Dez",
                            ],
                            longhand: [
                                "Janeiro", "Fevereiro", "Março", "Abril",
                                "Maio", "Junho", "Julho", "Agosto",
                                "Setembro", "Outubro", "Novembro", "Dezembro",
                            ],
                        },
                    },
                    minDate: mindate ? new Date(mindate) : undefined,
                    maxDate: maxdate ? new Date(maxdate) : undefined,
                }}
                onReady={(_, __, instance) => {
                    fpInstanceRef.current = instance; // salva a instância assim que o calendário estiver pronto

                    const input = instance.altInput;
                    if (!input) return;

                    if (!monthYearOnly && mode !== "range") {
                        maskRef.current = IMask(input, { mask: "00/00/0000" });
                    } else if (!monthYearOnly && mode === "range") {
                        maskRef.current = IMask(input, { mask: "00/00/0000 até 00/00/0000" });
                    }
                }}
                onClose={(_, __, instance) => {
                    const input = instance.altInput;
                    if (!input?.value) return; // estado vazio — não propaga para o form

                    const rawValue = input.value;

                    if (!monthYearOnly && rawValue.length === 10) {
                        const [day, month, year] = rawValue.split("/");
                        const date = new Date(year, month - 1, day);
                        if (!isNaN(date.getTime())) {
                            instance.setDate(date, true);
                            maskRef.current?.updateValue();
                        }
                    }

                    if (monthYearOnly && rawValue.length === 7) {
                        const [month, year] = rawValue.split("/");
                        const date = new Date(year, month - 1, 1);
                        if (!isNaN(date.getTime())) {
                            instance.setDate(date, true);
                        }
                    }
                }}
                onChange={(selectedDates, dateStr, instance) => {
                    // updateValue só é chamado quando há mask (modo data)
                    if (!monthYearOnly) {
                        maskRef.current?.updateValue();
                    }

                    // Clear event (clear() → onChange([], "", instance))
                    if (selectedDates.length === 0) {
                        if (isControlled) {
                            userOnChange(selectedDates || [], "", false, true);
                        } else {
                            setData(name, null);
                        }
                        return;
                    }

                    // Range mode
                    if (mode === "range") {
                        if (selectedDates.length >= 1) {
                            // handleFilterChange(e, fieldName, false, true) usa e.map(date => ...)
                            if (isControlled) {
                                userOnChange(selectedDates, name, false, true);
                            } else {
                                const values = selectedDates.map((date) => {
                                    const offset = date.getTimezoneOffset();
                                    const adjustedDate = new Date(date.getTime() - (offset * 60 * 1000));
                                    return adjustedDate.toISOString().split("T")[0];
                                });
                                setData(name, values);
                            }
                        }
                        return;
                    }

                    // Mês/ano
                    if (monthYearOnly) {
                        if (isControlled) {
                            userOnChange(selectedDates, dateStr, instance);
                        } else {
                            const date = new Date(selectedDates[0]);
                            date.setDate(1);
                            setData(name, date.toISOString().slice(0, 7));
                        }
                        return;
                    }

                    // Data única
                    if (isControlled) {
                        userOnChange(selectedDates, dateStr, instance);
                    } else {
                        const singleDate = selectedDates[0];
                        const offset = singleDate.getTimezoneOffset();
                        const adjustedSingleDate = new Date(singleDate.getTime() - (offset * 60 * 1000));
                        setData(name, adjustedSingleDate.toISOString().split("T")[0]);
                    }
                }}
                onDestroy={() => {
                    maskRef.current?.destroy();
                    fpInstanceRef.current = null;
                }}
            />

            {value && value.length > 0 && clearable && (
                <button type="button" onClick={handleClear} className="absolute right-0 top-0 h-full">
                    <Xmark className="absolute right-3 top-9 text-orbital-text-secondary" />
                </button>
            )}

            {hasError && (
                <HelperText className="text-orbital-accent-dark">{errorMessage}</HelperText>
            )}
        </div>
    );
}
