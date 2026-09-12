import { HelperText, Label } from 'flowbite-react';

export default function Color({ name, label, value, setData, errors, className = '', ...props }) {
    const errorMessage = errors?.[name];

    return <div className={className}>
        <Label className={`text-xs font-semibold ${errorMessage ? '!text-orbital-accent-dark' : '!text-orbital-text-primary'}`} htmlFor={name}>{label}</Label>
        <input
            id={name}
            name={name}
            type="color"
            value={value || '#123B8F'}
            onChange={(event) => setData(name, event.target.value)}
            className="mt-1 block h-11 w-full cursor-pointer rounded-lg border border-orbital-border bg-orbital-surface p-1 focus:border-orbital-accent focus:outline-none focus:ring-2 focus:ring-orbital-accent/30"
            {...props}
        />
        {errorMessage && <HelperText className="text-orbital-accent-dark">{errorMessage}</HelperText>}
    </div>;
}
