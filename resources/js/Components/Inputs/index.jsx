import { lazy } from "react";

function ErrorSummary({ errors = {} }) {
    const messages = Object.values(errors).flat().filter(Boolean);

    if (messages.length === 0) return null;

    return <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert"><ul className="list-disc space-y-1 pl-5">{messages.map((message, index) => <li key={`${message}-${index}`}>{message}</li>)}</ul></div>;
}

const Inputs = {
    'ErrorSummary': ErrorSummary,
    'Validation': lazy(() => import("./InputValidation")),
    'Mask': lazy(() => import("./InputMask")),
    'Select': lazy(() => import("./Select")),
    'Search': lazy(() => import("./InputSearch")),
    'CreatableSelect': lazy(() => import("./CreatableSelect")),
    'Textarea': lazy(() => import("./Textarea")),
    'Flatpickr': lazy(() => import("./Flatpickr")),
    'Number': lazy(() => import("./InputNumber")),
    'File': lazy(() => import("./FileUpload")),
    'FileInput': lazy(() => import("./FileInput")),
    'AddonSelect': lazy(() => import("./AddonSelect")),
    'Color': lazy(() => import("./Color")),
    'Checkbox': lazy(() => import("./Checkbox")),
    'IconPicker': lazy(() => import("./IconPicker")),
};

export { ErrorSummary };
export default Inputs;
