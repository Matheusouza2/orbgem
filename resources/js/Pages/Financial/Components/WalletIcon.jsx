import { logoCdnUrl } from 'logos-bancos-br';
import { useEffect, useState } from 'react';
import { WalletCards } from 'lucide-react';

export default function WalletIcon({ bankCode }) {
    const logoUrl = bankCode ? logoCdnUrl(bankCode) : null;
    const [showLogo, setShowLogo] = useState(Boolean(logoUrl));

    useEffect(() => {
        setShowLogo(Boolean(logoUrl));
    }, [logoUrl]);

    return <span className="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl bg-orbital-primary-light text-orbital-primary" aria-hidden="true">
        {showLogo ? <img src={logoUrl} alt="" className="h-full w-full object-contain" onError={() => setShowLogo(false)} /> : <WalletCards className="h-5 w-5" />}
    </span>;
}
