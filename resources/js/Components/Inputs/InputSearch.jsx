import InputMask from "./InputMask";

function SearchIcon() {
    return <svg aria-hidden="true" className="h-4 w-4" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" /></svg>;
}

function LoadingIcon() {
    return <svg aria-hidden="true" className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeLinecap="round" strokeWidth="4" /></svg>;
}

export default function InputSearch({ loading, clickButton, ...props }) {
    return (
        <div className="w-full min-w-[200px]">
            <div className="relative">
                <InputMask
                    {...props} />
                <button
                    className="absolute right-1 top-7 rounded bg-orbital-primary px-2.5 py-1.5 text-center text-lg text-orbital-accent shadow-sm transition-all hover:bg-orbital-primary-dark hover:shadow focus:bg-orbital-primary-dark focus:shadow-none active:bg-orbital-primary-dark"
                    type="button"
                    onClick={clickButton}
                >
                    {
                        !loading ? <SearchIcon /> : <LoadingIcon />
                    }
                </button>
            </div>
        </div>
    );

}
