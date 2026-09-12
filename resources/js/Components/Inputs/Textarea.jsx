import { HelperText, Label, Textarea } from "flowbite-react";

export default function TextareaComponent({ setData, errors, ...props }) {
    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    return (
        <div>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"}`}
                htmlFor={props.name}
            >{props.label}</Label>
            <Textarea
                className={`block w-full rounded-lg border !border-orbital-border !bg-orbital-surface !text-orbital-text-primary placeholder-orbital-text-secondary focus:border-orbital-accent focus:outline-none focus:ring-2 focus:ring-orbital-accent/30 disabled:cursor-not-allowed disabled:opacity-50 ${hasError ? "!border-orbital-accent-dark !bg-orbital-primary-light" : ""}`}
                onChange={e => setData(props.name, e.target.value)}
                {...props}
            />

            {hasError && (
                <HelperText className="text-orbital-accent-dark">
                    {errorMessage}
                </HelperText>
            )}
        </div>
    );
}
