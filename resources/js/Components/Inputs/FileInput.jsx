import { FileInput, HelperText, Label } from "flowbite-react";

export default function FileInputComponent({ setData, errors, ...props }) {

    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    const handleFileChange = (e) => {
        if (e.target.files.length > 1) {
            setData(`${props.name}[]`, Array.from(e.target.files));
        } else if (e.target.files.length === 1) {
            setData(`${props.name}`, e.target.files[0]);

        }
    }

    return (
        <div>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"}`}
                htmlFor={props.name}
            >{props.label}</Label>
            <FileInput
                name={props.name}
                id={props.name}
                color={hasError ? "failure" : undefined}
                className="!border-orbital-border !bg-orbital-surface !text-orbital-text-primary file:!bg-orbital-primary file:!text-white hover:file:!bg-orbital-primary-dark"
                onChange={e => handleFileChange(e)}
                {...props}
            />

            {hasError && (
                <HelperText className="text-orbital-accent-dark">
                    {errorMessage}
                </HelperText>
            )}
        </div>
    )
}
