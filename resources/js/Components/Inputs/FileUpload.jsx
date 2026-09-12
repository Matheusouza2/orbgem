import { FileInput, Label } from "flowbite-react";
import { useState } from "react";

export default function FileUploadComponent({ setData, errors, ...props }) {

    const [files, setFiles] = useState([]);
    const errorMessage = errors?.[props.name];
    const hasError = Boolean(errorMessage);

    const handleFileChange = (event) => {
        const selectedFiles = Array.from(event.target.files);
        const errorMessage = errors?.[props.name];
        const hasError = Boolean(errorMessage);

        setFiles(selectedFiles);

        setData(props.name, Array.from(event.target.files))
    };

    return (
        <div>
            <Label
                className={`text-xs font-semibold ${hasError ? "!text-orbital-accent-dark" : "!text-orbital-text-primary"}`}
                htmlFor={props.name}
            >{props.label}</Label>
            <div className="flex items-center justify-center w-full">
                <Label
                    htmlFor="dropzone-file"
                    className={`flex h-64 w-full cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed ${hasError ? "border-orbital-accent-dark bg-orbital-primary-light" : "border-orbital-accent bg-orbital-background hover:bg-orbital-primary-light"}`}
                >
                    <div className="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg
                            className="mb-4 h-8 w-8 text-orbital-primary"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 16"
                        >
                            <path
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
                            />
                        </svg>
                        <p className="mb-2 text-sm text-orbital-text-secondary">
                            Clique ou arraste e solte os arquivos
                        </p>
                        <p className="text-xs text-orbital-text-secondary">PNG, JPG ou PDF</p>
                    </div>

                    {files.length > 0 && (
                        <div>
                            <h3 className="text-sm text-orbital-text-primary">Arquivos selecionados:</h3>
                            <ul className="pl-5">
                                {files.map((file, index) => (
                                    <li key={index} className="mb-3 text-sm text-orbital-text-secondary">
                                        {file.name} ({(file.size / 1024).toFixed(2)} KB)
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <FileInput multiple id="dropzone-file" className="hidden" onChange={e => handleFileChange(e)} {...props} />
                </Label>
            </div>
            {hasError && <p className="mt-1 text-sm text-orbital-accent-dark">{errorMessage}</p>}
        </div>
    )
}
