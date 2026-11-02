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
        borderColor: hasError ? "#9a7a00" : (state.isFocused ? '#e9c23a' : '#e5e2f0'),
        backgroundColor: hasError ? "#efecff" : '#FFFFFF',
        color: '#1a1530',
        padding: '0.150rem',
        fontSize: '0.875rem',
        lineHeight: '1.25rem',
        '&:hover': {
            borderColor: '#e9c23a',
            boxShadow: '0 0 0 1px rgba(244, 179, 33, 0.3)',
        },
        '&:focus': {
            boxShadow: '0 0 0 1px rgba(244, 179, 33, 0.3)',
        }
    }),
    option: (baseStyles, state) => ({
        ...baseStyles,
        backgroundColor: state.isFocused ? '#efecff' : '#FFFFFF',
        color: state.isFocused ? '#170f3d' : '#1a1530',
        cursor: 'pointer',
        '&:active': {
            backgroundColor: '#efecff',
            color: '#1a1530',
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
