import { useMask } from "@react-input/mask";
import { HelperText, Label } from "flowbite-react";

export default function AddonSelect({ setData, errors, options = [], mask = "__", labelLight, ...props }) {

    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    const inputRef = useMask({ mask: mask, replacement: { _: /\d/ } });

    return (
        <div>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"} ${labelLight ? "!text-orbital-primary-light" : ""}`}
                htmlFor={props.name}
            >
                {props.label}
            </Label>

            <div className="flex">
                <div className="relative w-full inline-flex">
                    <select
                        id={props.name}
                        className="w-1/4 rounded-l-lg border border-orbital-border bg-orbital-surface text-orbital-text-primary placeholder-orbital-text-secondary focus:border-orbital-accent focus:ring-2 focus:ring-orbital-accent/30"
                        onChange={e => setData(props.name, e.target.value)}
                        {...props}
                    >
                        <option value=""></option>
                        {options.map((option, index) => (
                            <option key={index} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <input ref={inputRef} type="text" className="w-3/4 rounded-r-lg border border-l-0 border-orbital-border bg-orbital-surface text-orbital-text-primary placeholder-orbital-text-secondary focus:border-orbital-accent focus:ring-2 focus:ring-orbital-accent/30" />
                </div>
            </div>

            {hasError && (
                <HelperText className="text-orbital-accent-dark">
                    {errorMessage}
                </HelperText>
            )}
        </div>
    );
}
