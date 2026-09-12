import { useEffect, useState } from "react";
import { HelperText, Label, TextInput } from "flowbite-react";
import { Xmark } from "../Icons/solid";

export default function InputNumber({ setData, onChange, errors, labelLight, maxDigits, className = '', inputClassName = '', format = 'currency', clearable, ...props }) {

    const digits = maxDigits ?? 2;
    const divider = Math.pow(10, digits);

    const formatted = new Intl.NumberFormat("pt-BR", {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
        style: format,
        currency: "BRL"
    });

    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    const isControlled = typeof onChange === "function";

    // Converte qualquer valor recebido (número, string numérica ou vazio) para número seguro
    const toNumeric = (val) => {
        if (val === null || val === undefined || val === "") return 0;
        const num = Number(val);
        return isNaN(num) ? 0 : num;
    };

    const [display, setDisplay] = useState(() => formatted.format(toNumeric(props.value)));

    // Sincroniza quando props.value muda externamente (ex: clearFilters zera para "")
    useEffect(() => {
        setDisplay(formatted.format(toNumeric(props.value)));
    }, [props.value]);

    const handleChange = (e) => {
        const raw = e.target.value.replace(/\D/g, "");
        const numeric = (Number(raw) / divider).toFixed(digits);

        setDisplay(formatted.format(Number(numeric)));

        if (isControlled) {
            const syntheticEvent = { target: { name: props.name, value: numeric } };
            onChange(syntheticEvent);
        } else {
            setData(props.name, numeric);
        }
    };

    const handleClear = () => {
        setDisplay(formatted.format(0));

        if (isControlled) {
            const syntheticEvent = { target: { name: props.name, value: "" } };
            onChange(syntheticEvent);
        } else {
            setData(props.name, "");
        }
    };

    return (
        <div className={className}>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"} ${labelLight ? "!text-orbital-primary-light" : ""}`}
                htmlFor={props.name}
            >
                {props.label}
            </Label>

            <TextInput
                {...props}
                onChange={handleChange}
                value={display}
                name={props.name}
                id={props.name}
                type={props.type || "text"}
                color={hasError ? "failure" : "gray"}
                className={`!border-orbital-border !bg-orbital-surface !text-orbital-text-primary placeholder:!text-orbital-text-secondary !shadow-none focus:!border-orbital-accent focus:!ring-2 focus:!ring-orbital-accent/30 ${inputClassName}`}
            />

            {(props.value && clearable && Number(props.value) !== 0) ? (
                <button type="button" onClick={handleClear} className="absolute right-0 top-0 h-full">
                    <Xmark className="absolute right-3 top-9 text-orbital-text-secondary" />
                </button>
            ) : null}

            {hasError && (
                <HelperText className="text-orbital-accent-dark">
                    {errorMessage}
                </HelperText>
            )}
        </div>
    );
}
