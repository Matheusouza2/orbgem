import { useEffect, useState } from "react";
import InputValidation from "./InputValidation";
import { useMask } from "@react-input/mask";

export default function InputMask({ ...props }) {

    const [mask, setMask] = useState("_______________________");
    const [replacement, setReplacement] = useState({ _: /\d/ });

    useEffect(() => {
        switch (props.mask) {
            case "cpf":
                setMask("___.___.___-__");
                break;
            case "cnpj":
                setMask("__.___.___/____-__");
                break;
            case "cep":
                setMask("__.___-___");
                break;
            case "tel":
                setMask("(__) _____-____");
                break;
            case "date":
                setMask("__/__/____");
                break;
            case "cgc":
                props.value?.length > 13 ? setMask("__.___.___/____-__") : setMask("___.___.___-__");
                break;
            case "aleatoria":
                setMask("________-____-____-____-____________");
                setReplacement({ _: /[a-zA-Z0-9]/ });
                break;
            case "placa":
                setMask("___-____");
                setReplacement({ _: /[a-zA-Z0-9]/ });
                break;
            case "ano":
                setMask("____");
                setReplacement({ _: /\d/ });
                break;
            case "cartao":
                setMask("____ ____ ____ ____");
                setReplacement({ _: /\d/ });
                break;
            case "boleto":
                props?.value?.substring(0, 1) == 8 ? setMask("___________-_ ___________-_ ___________-_ ___________-_") : setMask("_____._____ _____.______ _____.______ _ ______________");
                setReplacement({ _: /\d/ });
                break;
            default:
                setMask("__________________________________________________");
                setReplacement({ _: /[a-zA-Z0-9@._-]/ });
                break;
        }
    }, [props.value]);

    let inputRef = useMask({ mask: mask, replacement: replacement });


    return (
        <InputValidation
            maskRef={inputRef}
            {...props}

        />
    );

}
