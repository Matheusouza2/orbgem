import { HelperText, Label, TextInput } from "flowbite-react";
import { Xmark } from "../Icons/solid";

export default function InputValidation({ setData, onChange, errors, maskRef = null, labelLight, className = '', inputClassName = '', color: _color, ...props }) {

    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    // Modo controlado externamente (ex: TableFilter via onChange)
    const isControlled = typeof onChange === "function";

    const handleChange = (e) => {
        if (isControlled) {
            onChange(e);
        } else {
            setData(props.name, e.target.value);
        }
    };

    const handleClear = () => {
        if (isControlled) {
            // Simula um evento sintético para manter a interface consistente
            const syntheticEvent = { target: { name: props.name, value: "" } };
            onChange(syntheticEvent);
        } else {
            setData(props.name, "");
        }
    };

    return (
        <div className={className}>
            <div className="relative">
                <Label
                    className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"} ${labelLight ? "!text-orbital-primary-light" : ""}`}
                    htmlFor={props.name}
                >
                    {props.label}
                </Label>

                <TextInput
                    {...props}
                    ref={maskRef}
                    type={props.type || "text"}
                    id={props.name}
                    onChange={handleChange}
                    color={hasError ? "failure" : "gray"}
                    className={`!border-orbital-border !bg-orbital-surface !text-orbital-text-primary placeholder:!text-orbital-text-secondary !shadow-none focus:!border-orbital-accent focus:!ring-2 focus:!ring-orbital-accent/30 ${inputClassName}`}
                />

                {(props.value && props.clearable) && (
                    <button type="button" onClick={handleClear} className="absolute right-0 top-0 h-full">
                        <Xmark className="absolute right-3 top-9 text-orbital-text-secondary" />
                    </button>
                )}
            </div>

            {hasError && (
                <HelperText className="text-orbital-accent-dark">
                    {errorMessage}
                </HelperText>
            )}
        </div>
    );
}
