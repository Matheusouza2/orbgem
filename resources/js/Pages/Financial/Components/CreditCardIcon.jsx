import { CreditCard as CardGlyph, Landmark } from 'lucide-react';
import { banks, logoCdnUrl } from 'logos-bancos-br';
import { useEffect, useState } from 'react';

const bankLogosByName = new Map(banks().map((bank) => [bank.name, logoCdnUrl(bank.ispb)]));

export default function CreditCardIcon({ institution }) {
    const logoUrl = institution ? bankLogosByName.get(institution) : null;
    const [showLogo, setShowLogo] = useState(Boolean(logoUrl));

    useEffect(() => setShowLogo(Boolean(logoUrl)), [logoUrl]);

    return <span className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orbital-primary-light text-orbital-primary" aria-hidden="true">
        {showLogo ? <img src={logoUrl} alt="" className="h-full w-full object-contain" onError={() => setShowLogo(false)} /> : institution ? <Landmark className="h-6 w-6" /> : <CardGlyph className="h-6 w-6" />}
    </span>;
}
