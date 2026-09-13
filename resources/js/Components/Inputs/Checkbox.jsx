import { Checkbox as FlowbiteCheckbox, HelperText, Label } from 'flowbite-react';

export default function Checkbox({ name, label, value, setData, onChange, errors, className = '' }) {
    const errorMessage = errors?.[name];

    return <div className={className}>
        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-orbital-border bg-orbital-background px-3 py-3 transition hover:border-orbital-primary-light">
            <FlowbiteCheckbox
                id={name}
                name={name}
                checked={Boolean(value)}
                onChange={(event) => onChange ? onChange(event.target.checked) : setData(name, event.target.checked)}
                color={errorMessage ? 'failure' : 'blue'}
            />
            <span>
                <Label htmlFor={name} className="cursor-pointer text-sm font-semibold !text-orbital-text-primary">{label}</Label>
            </span>
        </label>
        {errorMessage && <HelperText className="text-orbital-accent-dark">{errorMessage}</HelperText>}
    </div>;
}
