import { Label } from "flowbite-react";
import AsyncCreatableSelect from 'react-select/async-creatable';

export default function CreatableSelectComponent({ setData, errors, ...props }) {

    const errorMessage = errors?.[props.altName || props.name];
    const hasError = Boolean(errorMessage);
    const handleChange = (selectedOption) => {

        if (props.altName) {
            setData(props.name, Number.isInteger(selectedOption?.value) ? selectedOption.value : null);
            setData(props.altName, selectedOption);
        } else {
            setData(props.name, selectedOption);
        }
    }

    return (
        <div>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"}`}
                htmlFor={props.name}
            >{props.label}</Label>
            <AsyncCreatableSelect
                cacheOptions
                defaultOptions
                menuPortalTarget={document.body}
                menuPlacement="auto"
                styles={selectStyles(hasError)} placeholder="Selecione..."
                noOptionsMessage={() => 'Nenhum resultado encontrado'}
                formatCreateLabel={(e) => `Criar categoria "${e}"`}
                onChange={e => handleChange(e)}
                loadingMessage={() => "Buscando ..."}
                isClearable={props.isClearable == undefined ? true : false}
                loadOptions={props.loadOptions}
                value={props.value || null}
                {...props}
            />
        </div>
    )
}

const selectStyles = (hasError) => ({
    control: (baseStyles, state) => ({
        ...baseStyles,
        borderRadius: '0.5rem',
        boxShadow: "none",
        width: '100%',
        borderWidth: '1px',
        borderColor: hasError ? "#C98A00" : (state.isFocused ? '#F4B321' : '#E4E7EC'),
        backgroundColor: hasError ? "#E8F0FF" : '#FFFFFF',
        color: '#172033',
        padding: '0.150rem',
        fontSize: '0.875rem',
        lineHeight: '1.25rem',
        '&:hover': {
            borderColor: '#F4B321',
            boxShadow: '0 0 0 1px rgba(244, 179, 33, 0.3)',
        },
        '&:focus': {
            boxShadow: '0 0 0 1px rgba(244, 179, 33, 0.3)',
        }
    }),
    option: (baseStyles, state) => ({
        ...baseStyles,
        backgroundColor: state.isFocused ? '#E8F0FF' : '#FFFFFF',
        color: state.isFocused ? '#0B2454' : '#172033',
        cursor: 'pointer',
        '&:active': {
            backgroundColor: '#E8F0FF',
            color: '#172033',
            boxShadow: '0 0 0 1px rgba(244, 179, 33, 0.3)',
        },
    }),
    menu: (baseStyles) => ({
        ...baseStyles,
        width: 'max-content',
        minWidth: '100%',
    }),
    menuPortal: (baseStyles) => ({
        ...baseStyles,
        zIndex: 9999,
    }),
})
