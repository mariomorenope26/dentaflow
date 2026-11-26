import { MessageCircle } from 'lucide-react';

export function JmsFooter({ className = '' }: { className?: string }) {
    const whatsappHref =
        'https://wa.me/51959199368?text=' +
        encodeURIComponent(
            'Hola Grupo JMSPERU, deseo información / soporte sobre el sistema DentaFlow.',
        );

    return (
        <footer
            className={`border-t bg-card/70 px-4 py-3 text-center text-xs text-muted-foreground ${className}`}
        >
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-1.5">
                <span>Desarrollado por</span>
                <strong className="font-semibold text-foreground">
                    Grupo JMSPERU 2026
                </strong>
                <span>::</span>
                <a
                    href={whatsappHref}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 font-semibold text-[#0E8FA3] transition-colors hover:text-[#0A6577] hover:underline"
                >
                    <MessageCircle className="size-3.5 text-[#2E9E6B]" />
                    Contacto 959199368
                </a>
            </div>
        </footer>
    );
}
