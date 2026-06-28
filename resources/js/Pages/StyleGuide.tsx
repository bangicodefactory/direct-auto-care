import { Head } from '@inertiajs/react';
import {
    Wrench,
    Droplet,
    SprayCan,
    Car,
    Fuel,
    Cog,
    Gauge,
    ShieldCheck,
    CalendarCheck,
    Receipt,
    Clock,
    CircleUser,
    User,
    Bell,
    Search,
    Settings,
    IdCard,
    Plus,
    Check,
    CheckCircle2,
    XCircle,
    ArrowRight,
    TriangleAlert,
    Info,
    X,
    ChevronDown,
    MoreHorizontal,
    LayoutGrid,
    ClipboardList,
    Users,
    CalendarDays,
    Flame,
    type LucideIcon,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';

/**
 * Direct Auto Care — Design System (living documentation).
 *
 * Faithful adaptation of "Direct Auto Care - Design System.dc.html".
 * Foundation sections render the brand palette literally; component sections
 * use the project's real shadcn/ui components so the page stays a living
 * reference of what ships. Bilingual FR / العربية.
 *
 * Note: the source design specifies Phosphor icons; this page renders the
 * project's existing lucide-react set as the closest dependency-free match.
 */

// ── Brand foundation (literal hexes mirror the .dc source) ──────────────────
const INK = '#15130F';
const INK_800 = '#211E1A';
const INK_700 = '#312D27';
const PAPER = '#F6F2EA';
const AMBER = '#F4A81C';
const GREEN = '#2FA84F';

const hexagon = 'polygon(50% 0,100% 25%,100% 75%,50% 100%,0 75%,0 25%)';

function SectionLabel({ n, label }: { n: string; label: string }) {
    return (
        <div className="mb-2 flex items-baseline gap-[18px]">
            <span className="font-mono text-[13px] font-semibold tracking-[0.2em] text-[#F4A81C]">
                {n}
            </span>
            <span className="font-mono text-xs uppercase tracking-[0.2em] text-[#8A837A]">
                {label}
            </span>
        </div>
    );
}

function MiniLabel({ children }: { children: React.ReactNode }) {
    return (
        <div className="mb-4 mt-[30px] font-mono text-[11px] uppercase tracking-[0.16em] text-[#8A837A]">
            {children}
        </div>
    );
}

const NAV = [
    ['logo', 'Logo'],
    ['couleurs', 'Couleurs'],
    ['typo', 'Type'],
    ['icones', 'Icônes'],
    ['composants', 'Composants'],
    ['rdv', 'Rendez-vous'],
] as const;

const AMBER_SCALE = [
    ['300', '#FBE3B5'],
    ['400', '#F8C25A'],
    ['500', '#F4A81C'],
    ['600', '#D98E0A'],
    ['700', '#A66A07'],
];

const NEUTRALS = [
    ['950', '#15130F'],
    ['800', '#312D27'],
    ['500', '#6B655E'],
    ['300', '#B6AFA2'],
    ['100', '#E4DECF'],
    ['Paper', '#F6F2EA'],
];

const SEMANTIC: [string, string, LucideIcon, string][] = [
    ['Succès', '#2FA84F', Check, '#fff'],
    ['Alerte', '#F4A81C', TriangleAlert, '#15130F'],
    ['Erreur', '#E5484D', X, '#fff'],
    ['Info', '#3B82C4', Info, '#fff'],
];

const ICONS: [LucideIcon, string, boolean][] = [
    [Wrench, 'Mécanique', true],
    [Droplet, 'Vidange', true],
    [SprayCan, 'Lavage', true],
    [Car, 'Véhicule', true],
    [Fuel, 'Carburant', true],
    [Cog, 'Moteur', true],
    [Gauge, 'Diagnostic', true],
    [ShieldCheck, 'Garantie', false],
    [CalendarCheck, 'Rendez-vous', false],
    [Receipt, 'Facture', false],
    [Clock, 'Atelier', false],
    [CircleUser, 'Client', false],
    [Bell, 'Alerte', false],
    [Search, 'Recherche', false],
    [Settings, 'Réglages', false],
    [IdCard, 'Immatric.', false],
];

const SERVICES: [LucideIcon, string, string, string, string, boolean][] = [
    [
        Wrench,
        'Mécanique générale',
        'الميكانيك العامة',
        'Diagnostic, freinage, distribution et réparations moteur par nos techniciens.',
        '250',
        false,
    ],
    [
        Droplet,
        'Vidange & entretien',
        'تغيير الزيت والصيانة',
        'Huile, filtres et points de contrôle pour préserver votre moteur.',
        '180',
        false,
    ],
    [
        SprayCan,
        'Lavage auto',
        'غسيل السيارات',
        'Lavage intérieur et extérieur, lustrage et traitement des sièges.',
        '60',
        false,
    ],
    [
        IdCard,
        'Immatriculation',
        'التسجيل والترقيم',
        'Démarches administratives et plaques, prises en charge de A à Z.',
        '—',
        true,
    ],
];

type AppointmentRow = {
    time: string;
    duration: string;
    icon: LucideIcon;
    title: string;
    customer: string;
    plate: string;
    edge: string;
    badge: React.ReactNode;
    initials: string;
    avatarBg: string;
    avatarFg?: string;
};

export default function StyleGuide() {
    return (
        <>
            <Head title="Design System — Direct Auto Care" />

            <div
                className="w-full overflow-hidden font-sans text-[#211E1A]"
                style={{ background: PAPER }}
            >
                {/* ░░ STICKY NAV ░░ */}
                <div
                    className="sticky top-0 z-50 flex h-16 items-center justify-between gap-6 px-10 backdrop-blur-[10px]"
                    style={{
                        background: 'rgba(21,19,15,.92)',
                        borderBottom: `1px solid ${INK_700}`,
                    }}
                >
                    <div className="flex items-center gap-[14px]">
                        <div
                            className="flex h-[34px] w-[34px] items-center justify-center"
                            style={{ background: AMBER, color: INK, clipPath: hexagon }}
                        >
                            <Wrench size={17} strokeWidth={2.5} />
                        </div>
                        <div className="font-heading text-[15px] font-semibold uppercase tracking-[0.14em] text-[#F6F2EA]">
                            Direct<span style={{ color: AMBER }}>·</span>Auto Care
                        </div>
                    </div>
                    <div className="hidden items-center gap-[26px] font-mono text-[11px] uppercase tracking-[0.12em] md:flex">
                        {NAV.map(([id, label]) => (
                            <a
                                key={id}
                                href={`#${id}`}
                                className="text-[#6B655E] transition-colors hover:text-[#F4A81C]"
                            >
                                {label}
                            </a>
                        ))}
                    </div>
                    <div className="font-arabic text-[13px] font-bold text-[#6B655E]" dir="rtl">
                        دليل الهوية
                    </div>
                </div>

                {/* ░░ HERO ░░ */}
                <section
                    className="relative px-10 pb-[88px] pt-24 text-[#F6F2EA]"
                    style={{ background: INK }}
                >
                    <div
                        className="absolute inset-x-0 top-0 h-[6px]"
                        style={{ background: AMBER }}
                    />
                    <div className="relative mx-auto max-w-[1240px]">
                        <div
                            className="mb-[34px] flex items-center gap-[14px] font-mono text-xs uppercase tracking-[0.22em]"
                            style={{ color: AMBER }}
                        >
                            <span className="h-px w-[34px]" style={{ background: AMBER }} />
                            Design System · v1.0 · 2026
                        </div>
                        <h1 className="m-0 font-heading text-[72px] font-bold uppercase leading-[0.92] tracking-[0.01em] sm:text-[96px] lg:text-[120px]">
                            Direct
                            <br />
                            <span style={{ color: AMBER }}>Auto Care</span>
                        </h1>
                        <div className="mt-10 flex flex-wrap items-end justify-between gap-8">
                            <p className="m-0 max-w-[560px] text-[19px] leading-[1.55] text-[#C9C3B8]">
                                Le langage visuel du système de gestion d'atelier — couleurs,
                                typographie et composants pour une expérience robuste, premium et
                                lisible à l'établi comme à l'écran.
                            </p>
                            <p
                                className="m-0 max-w-[420px] text-right font-arabic text-[21px] font-bold leading-[1.7] text-[#C9C3B8]"
                                dir="rtl"
                            >
                                اللغة البصرية لنظام إدارة الورشة — ألوان وخطوط ومكوّنات لتجربة متينة
                                وأنيقة وواضحة.
                            </p>
                        </div>
                        <div className="mt-12 flex flex-wrap gap-[14px]">
                            {[
                                ['#F4A81C', AMBER],
                                ['#15130F', INK],
                                ['#2FA84F', GREEN],
                            ].map(([hex, sw]) => (
                                <div
                                    key={hex}
                                    className="flex items-center gap-[10px] rounded px-[18px] py-[11px] font-mono text-xs tracking-[0.08em] text-[#C9C3B8]"
                                    style={{ border: `1px solid ${INK_700}` }}
                                >
                                    <span
                                        className="h-[13px] w-[13px] rounded-[3px]"
                                        style={{
                                            background: sw,
                                            border:
                                                hex === '#15130F' ? '1px solid #4a443c' : undefined,
                                        }}
                                    />
                                    {hex}
                                </div>
                            ))}
                            <div
                                className="flex items-center gap-[10px] rounded px-[18px] py-[11px] font-mono text-xs tracking-[0.08em] text-[#C9C3B8]"
                                style={{ border: `1px solid ${INK_700}` }}
                            >
                                Oswald · IBM Plex · Cairo
                            </div>
                        </div>
                    </div>
                </section>

                {/* ░░ 01 · LOGO ░░ */}
                <section id="logo" className="px-10 py-[88px]" style={{ background: PAPER }}>
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="01" label="Marque · العلامة" />
                        <h2 className="m-0 mb-11 font-heading text-[46px] font-semibold uppercase tracking-[0.01em]">
                            Logo &amp; signature
                        </h2>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-[1.4fr_1fr]">
                            {/* primary lockup on dark */}
                            <div
                                className="relative flex items-center justify-center overflow-hidden rounded p-14"
                                style={{ background: INK }}
                            >
                                <div className="relative flex flex-col items-center gap-[18px]">
                                    <div
                                        className="flex h-[90px] w-[84px] items-center justify-center shadow-[0_10px_30px_rgba(0,0,0,.4)]"
                                        style={{ background: GREEN, clipPath: hexagon }}
                                    >
                                        <Wrench size={40} strokeWidth={2.5} color="#fff" />
                                    </div>
                                    <div className="text-center">
                                        <div className="font-heading text-[54px] font-bold uppercase leading-[0.9] tracking-[0.04em] text-[#F6F2EA]">
                                            Direct
                                        </div>
                                        <div className="mt-2 flex items-center justify-center gap-[10px]">
                                            <span
                                                className="h-[2px] w-6"
                                                style={{ background: AMBER }}
                                            />
                                            <span
                                                className="pl-[0.42em] font-heading text-[16px] font-medium uppercase tracking-[0.42em]"
                                                style={{ color: AMBER }}
                                            >
                                                Auto Care
                                            </span>
                                            <span
                                                className="h-[2px] w-6"
                                                style={{ background: AMBER }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/* badge alone */}
                            <div className="flex flex-col items-center justify-center gap-5 rounded border border-[#E4DECF] bg-white p-10">
                                <div
                                    className="flex h-[104px] w-24 items-center justify-center"
                                    style={{ background: GREEN, clipPath: hexagon }}
                                >
                                    <Wrench size={46} strokeWidth={2.5} color="#fff" />
                                </div>
                                <div className="text-center font-mono text-[11px] uppercase tracking-[0.14em] text-[#8A837A]">
                                    Emblème seul · isotype
                                    <br />
                                    الشعار المجرّد
                                </div>
                            </div>
                        </div>

                        {/* variants row */}
                        <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <LogoChip
                                bg={AMBER}
                                dir="Direct"
                                sub="Auto Care"
                                subColor={INK}
                                dirColor={INK}
                                note="SUR AMBRE"
                                noteColor="rgba(21,19,15,.6)"
                            />
                            <LogoChip
                                bg={INK}
                                dir="Direct"
                                sub="Auto Care"
                                subColor={AMBER}
                                dirColor="#F6F2EA"
                                note="SUR NOIR"
                                noteColor="#6B655E"
                            />
                            <LogoChip
                                bg="#fff"
                                border
                                dir="Direct"
                                sub="Auto Care"
                                subColor="#8A837A"
                                dirColor={INK}
                                note="SUR BLANC"
                                noteColor="#B6AFA2"
                            />
                            <LogoChip
                                bg={INK_800}
                                dir="Direct"
                                sub="Auto Care"
                                subColor="#F6F2EA"
                                dirColor="#F6F2EA"
                                note="MONO INVERSÉ"
                                noteColor="#C9C3B8"
                            />
                        </div>

                        {/* rules */}
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                className="flex gap-[14px] rounded border border-[#E4DECF] bg-white px-6 py-[22px]"
                                style={{ borderLeft: `3px solid ${GREEN}` }}
                            >
                                <CheckCircle2 size={22} className="shrink-0" color={GREEN} />
                                <div>
                                    <div className="mb-1 text-[15px] font-semibold">À faire</div>
                                    <div className="text-[13.5px] leading-[1.5] text-[#6B655E]">
                                        Conserver une zone de protection ≥ la hauteur de l'emblème.
                                        Toujours sur fond contrasté.
                                    </div>
                                </div>
                            </div>
                            <div
                                className="flex gap-[14px] rounded border border-[#E4DECF] bg-white px-6 py-[22px]"
                                style={{ borderLeft: '3px solid #E5484D' }}
                            >
                                <XCircle size={22} className="shrink-0" color="#E5484D" />
                                <div>
                                    <div className="mb-1 text-[15px] font-semibold">À éviter</div>
                                    <div className="text-[13.5px] leading-[1.5] text-[#6B655E]">
                                        Ne pas déformer, recolorer l'emblème hors palette, ni
                                        ajouter d'ombres ou de contours.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ░░ 02 · COULEURS ░░ */}
                <section
                    id="couleurs"
                    className="px-10 py-[88px] text-[#F6F2EA]"
                    style={{ background: INK }}
                >
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="02" label="Fondations · الألوان" />
                        <h2 className="m-0 mb-3 font-heading text-[46px] font-semibold uppercase">
                            Couleurs
                        </h2>
                        <p className="m-0 mb-11 max-w-[620px] text-[16px] leading-[1.6] text-[#C9C3B8]">
                            L'ambre signale l'action et l'énergie. Le noir chaud porte la robustesse
                            industrielle. Le vert valide et rassure.
                        </p>

                        {/* feature swatches */}
                        <div className="mb-4 grid grid-cols-1 gap-4 md:grid-cols-[1.2fr_1fr_1fr]">
                            <FeatureSwatch
                                bg={AMBER}
                                fg={INK}
                                kicker="PRIMAIRE · ACTION"
                                name="Ambre 500"
                                code="#F4A81C · oklch(.78 .15 73)"
                            />
                            <FeatureSwatch
                                bg={INK}
                                fg="#F6F2EA"
                                kicker="NOIR · STRUCTURE"
                                name="Encre 950"
                                code="#15130F"
                                bordered
                                kickerColor="#8A837A"
                                codeColor="#C9C3B8"
                            />
                            <FeatureSwatch
                                bg={GREEN}
                                fg="#08230f"
                                kicker="ACCENT · VALIDATION"
                                name="Vert 500"
                                code="#2FA84F"
                            />
                        </div>

                        <MiniLabel>ÉCHELLE AMBRE</MiniLabel>
                        <div className="grid grid-cols-2 gap-[10px] sm:grid-cols-3 lg:grid-cols-5">
                            {AMBER_SCALE.map(([step, hex]) => (
                                <Swatch key={step} step={step} hex={hex} h="64px" />
                            ))}
                        </div>

                        <MiniLabel>NEUTRES CHAUDS</MiniLabel>
                        <div className="grid grid-cols-2 gap-[10px] sm:grid-cols-3 lg:grid-cols-6">
                            {NEUTRALS.map(([step, hex]) => (
                                <Swatch key={step} step={step} hex={hex} h="56px" />
                            ))}
                        </div>

                        <MiniLabel>SÉMANTIQUE · حالات</MiniLabel>
                        <div className="grid grid-cols-1 gap-[10px] sm:grid-cols-2 lg:grid-cols-4">
                            {SEMANTIC.map(([name, hex, Icon, iconColor]) => (
                                <div
                                    key={name}
                                    className="flex items-center gap-3 rounded border border-[#312D27] p-4"
                                    style={{ background: INK_800 }}
                                >
                                    <span
                                        className="flex h-[34px] w-[34px] items-center justify-center rounded-md"
                                        style={{ background: hex }}
                                    >
                                        <Icon size={16} strokeWidth={2.5} color={iconColor} />
                                    </span>
                                    <div>
                                        <div className="text-[13px] font-semibold">{name}</div>
                                        <div className="font-mono text-[11px] text-[#6B655E]">
                                            {hex}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ░░ 03 · TYPOGRAPHIE ░░ */}
                <section id="typo" className="px-10 py-[88px]" style={{ background: PAPER }}>
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="03" label="Fondations · الخطوط" />
                        <h2 className="m-0 mb-11 font-heading text-[46px] font-semibold uppercase">
                            Typographie
                        </h2>

                        <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <TypeCard
                                sample="Aa"
                                sampleClass="font-heading font-bold"
                                name="Oswald"
                                desc="Display industriel condensé. Titres, chiffres, signalétique. 400–700."
                            />
                            <TypeCard
                                sample="Aa"
                                sampleClass="font-sans font-semibold"
                                name="IBM Plex Sans"
                                desc="Texte courant, formulaires, données. Lisible et neutre. 400–600."
                            />
                            <TypeCard
                                sample="أ ب"
                                sampleClass="font-arabic font-extrabold"
                                sampleDir="rtl"
                                name="Cairo · العربية"
                                desc="Interface arabe. Accompagne le français en bilingue. 400–900."
                            />
                        </div>

                        {/* scale */}
                        <div className="rounded border border-[#E4DECF] bg-white px-8 py-2">
                            <ScaleRow meta="Display / Oswald 700 / 56">
                                <span className="font-heading text-[40px] font-bold uppercase leading-none sm:text-[56px]">
                                    Réparer. Entretenir.
                                </span>
                            </ScaleRow>
                            <ScaleRow meta="H1 / Oswald 600 / 36">
                                <span className="font-heading text-[36px] font-semibold uppercase leading-none">
                                    Bons de travail
                                </span>
                            </ScaleRow>
                            <ScaleRow meta="H2 / Oswald 500 / 24">
                                <span className="font-heading text-[24px] font-medium uppercase leading-none">
                                    Détails du véhicule
                                </span>
                            </ScaleRow>
                            <ScaleRow meta="Corps / Plex 400 / 17">
                                <span className="max-w-[640px] text-[17px] leading-[1.5]">
                                    Le mécanicien consigne chaque opération réalisée sur le
                                    véhicule, des pièces remplacées au temps passé, pour une
                                    facturation claire.
                                </span>
                            </ScaleRow>
                            <ScaleRow meta="Arabe / Cairo 700 / 30" last>
                                <span className="font-arabic text-[30px] font-bold" dir="rtl">
                                    إصلاح وصيانة السيارات
                                </span>
                            </ScaleRow>
                        </div>
                    </div>
                </section>

                {/* ░░ 04 · ICÔNES ░░ */}
                <section
                    id="icones"
                    className="px-10 py-[88px] text-[#F6F2EA]"
                    style={{ background: INK }}
                >
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="04" label="Système · الأيقونات" />
                        <h2 className="m-0 mb-3 font-heading text-[46px] font-semibold uppercase">
                            Iconographie
                        </h2>
                        <p className="m-0 mb-10 max-w-[600px] text-[16px] leading-[1.6] text-[#C9C3B8]">
                            Jeu lucide, trait régulier 1.5px. Reprend les métiers de l'atelier vus
                            en façade.
                        </p>
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                            {ICONS.map(([Icon, label, amber]) => (
                                <div
                                    key={label}
                                    className="flex flex-col items-center gap-[10px] rounded border border-[#312D27] px-2 py-[18px]"
                                    style={{ background: INK_800 }}
                                >
                                    <Icon
                                        size={30}
                                        strokeWidth={1.5}
                                        color={amber ? AMBER : '#C9C3B8'}
                                    />
                                    <span className="font-mono text-[9.5px] text-[#8A837A]">
                                        {label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ░░ 05 · COMPOSANTS ░░ */}
                <section id="composants" className="px-10 py-[88px]" style={{ background: PAPER }}>
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="05" label="Bibliothèque · المكوّنات" />
                        <h2 className="m-0 mb-11 font-heading text-[46px] font-semibold uppercase">
                            Composants
                        </h2>

                        {/* BUTTONS — real shadcn components */}
                        <MiniLabel>BOUTONS · أزرار</MiniLabel>
                        <div className="mb-4 rounded border border-[#E4DECF] bg-white p-8">
                            <div className="mb-[22px] flex flex-wrap items-center gap-[14px]">
                                <Button className="font-heading uppercase tracking-[0.06em]">
                                    <Plus /> Nouveau bon
                                </Button>
                                <Button
                                    variant="secondary"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Détails
                                </Button>
                                <Button
                                    variant="outline"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Annuler
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Ghost
                                </Button>
                                <Button className="bg-success font-heading uppercase tracking-[0.06em] text-success-foreground hover:bg-success/90">
                                    <Check /> Terminer
                                </Button>
                                <Button
                                    variant="destructive"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Supprimer
                                </Button>
                            </div>
                            <div className="flex flex-wrap items-center gap-[14px]">
                                <Button
                                    size="sm"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Petit · SM
                                </Button>
                                <Button className="font-heading uppercase tracking-[0.06em]">
                                    Moyen · MD
                                </Button>
                                <Button
                                    size="lg"
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Grand · LG
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    aria-label="wrench"
                                    className="text-primary"
                                >
                                    <Wrench />
                                </Button>
                                <Button variant="outline" size="icon" aria-label="more">
                                    <MoreHorizontal />
                                </Button>
                                <Button
                                    disabled
                                    className="font-heading uppercase tracking-[0.06em]"
                                >
                                    Désactivé
                                </Button>
                            </div>
                        </div>

                        {/* FORM FIELDS */}
                        <MiniLabel>CHAMPS DE FORMULAIRE · حقول</MiniLabel>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-5 rounded border border-[#E4DECF] bg-white p-7">
                                <Field label="Nom du client" hint="· اسم العميل">
                                    <input
                                        type="text"
                                        defaultValue="Youssef Benali"
                                        className="w-full rounded border-[1.5px] border-[#E4DECF] bg-[#FBF8F1] px-[14px] py-3 text-[15px] text-[#211E1A] outline-none focus:border-primary focus:bg-white"
                                    />
                                </Field>
                                <Field label="Immatriculation">
                                    <div className="relative">
                                        <Search
                                            size={16}
                                            className="absolute left-[14px] top-1/2 -translate-y-1/2 text-[#8A837A]"
                                        />
                                        <input
                                            type="text"
                                            placeholder="1234 - أ - 56"
                                            className="w-full rounded border-[1.5px] border-[#E4DECF] bg-[#FBF8F1] py-3 pl-10 pr-[14px] font-mono text-[15px] text-[#211E1A] outline-none focus:border-primary focus:bg-white"
                                        />
                                    </div>
                                </Field>
                                <Field label="Service demandé">
                                    <div className="relative">
                                        <ChevronDown
                                            size={15}
                                            className="pointer-events-none absolute right-[14px] top-1/2 -translate-y-1/2 text-[#8A837A]"
                                        />
                                        <select className="w-full appearance-none rounded border-[1.5px] border-[#E4DECF] bg-[#FBF8F1] px-[14px] py-3 text-[15px] text-[#211E1A] outline-none focus:border-primary focus:bg-white">
                                            <option>Mécanique générale</option>
                                            <option>Vidange &amp; entretien</option>
                                            <option>Lavage auto</option>
                                        </select>
                                    </div>
                                </Field>
                            </div>
                            <div className="flex flex-col gap-5 rounded border border-[#E4DECF] bg-white p-7">
                                <Field label="Note du mécanicien">
                                    <textarea
                                        rows={3}
                                        defaultValue="Plaquettes avant à remplacer, disque OK."
                                        className="w-full resize-none rounded border-[1.5px] border-[#E4DECF] bg-[#FBF8F1] px-[14px] py-3 text-[15px] leading-[1.5] text-[#211E1A] outline-none focus:border-primary focus:bg-white"
                                    />
                                </Field>
                                <div>
                                    <label className="mb-[7px] block text-[12.5px] font-semibold tracking-[0.03em] text-[#E5484D]">
                                        Kilométrage <span className="font-normal">· erreur</span>
                                    </label>
                                    <input
                                        type="text"
                                        defaultValue="abcde"
                                        className="w-full rounded border-[1.5px] border-[#E5484D] bg-[#FDF2F2] px-[14px] py-3 text-[15px] text-[#211E1A] outline-none"
                                    />
                                    <div className="mt-[7px] flex items-center gap-[6px] text-xs text-[#E5484D]">
                                        <TriangleAlert size={13} /> Valeur numérique requise.
                                    </div>
                                </div>
                                <div className="mt-[2px] flex items-center gap-5">
                                    <label className="flex cursor-pointer items-center gap-[9px] text-sm">
                                        <span
                                            className="flex h-5 w-5 items-center justify-center rounded-[5px]"
                                            style={{ background: GREEN }}
                                        >
                                            <Check size={12} strokeWidth={3} color="#fff" />
                                        </span>
                                        Pièces validées
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-[9px] text-sm text-[#6B655E]">
                                        <span className="h-5 w-5 rounded-[5px] border-[1.5px] border-[#C9C3B8] bg-white" />
                                        SMS client
                                    </label>
                                    <div
                                        className="relative ml-auto h-[26px] w-[46px] rounded-full"
                                        style={{ background: AMBER }}
                                    >
                                        <span className="absolute right-[3px] top-[3px] h-5 w-5 rounded-full bg-white" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* BADGES */}
                        <MiniLabel>BADGES &amp; STATUTS · شارات</MiniLabel>
                        <div className="flex flex-wrap items-center gap-3 rounded border border-[#E4DECF] bg-white p-7">
                            <StatusPill bg="#FDF6E6" color="#A66A07" dot="#F4A81C" pulse>
                                En attente · في الانتظار
                            </StatusPill>
                            <StatusPill bg="#EAF1FA" color="#2C5E96" dot="#3B82C4">
                                En cours · قيد التنفيذ
                            </StatusPill>
                            <StatusPill bg="#E8F6ED" color="#1E7A3C" icon={CheckCircle2}>
                                Terminé · مكتمل
                            </StatusPill>
                            <StatusPill bg="#FDECEC" color="#C5363B" icon={XCircle}>
                                Annulé · ملغى
                            </StatusPill>
                            <span
                                className="inline-flex items-center gap-[7px] rounded px-3 py-[6px] text-xs font-bold uppercase tracking-[0.04em]"
                                style={{ background: INK, color: AMBER }}
                            >
                                <Flame size={13} fill={AMBER} /> Urgent
                            </span>
                            <span
                                className="inline-flex items-center gap-[7px] rounded px-3 py-[7px] font-mono text-[13px] font-semibold tracking-[0.05em]"
                                style={{ background: AMBER, color: INK }}
                            >
                                1234 · أ · 56
                            </span>
                            <StatusPill bg="#F1ECE1" color="#6B655E" icon={Clock}>
                                + 2 j de retard
                            </StatusPill>
                        </div>

                        {/* SERVICE CARDS */}
                        <MiniLabel>CARTES DE SERVICE · خدمات</MiniLabel>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {SERVICES.map(([Icon, title, ar, desc, price, dark]) => (
                                <div
                                    key={title}
                                    className="overflow-hidden rounded border"
                                    style={{
                                        background: dark ? INK : '#fff',
                                        borderColor: dark ? INK : '#E4DECF',
                                    }}
                                >
                                    <div
                                        className="h-[5px]"
                                        style={{ background: dark ? GREEN : AMBER }}
                                    />
                                    <div className="p-6">
                                        <div
                                            className="mb-[18px] flex h-[52px] w-[52px] items-center justify-center rounded"
                                            style={{ background: dark ? GREEN : INK }}
                                        >
                                            <Icon
                                                size={26}
                                                strokeWidth={1.5}
                                                color={dark ? '#fff' : AMBER}
                                            />
                                        </div>
                                        <h3
                                            className="m-0 font-heading text-[21px] font-semibold uppercase"
                                            style={{ color: dark ? '#F6F2EA' : undefined }}
                                        >
                                            {title}
                                        </h3>
                                        <div
                                            className="mt-[2px] font-arabic text-[15px] font-semibold"
                                            dir="rtl"
                                            style={{ color: dark ? '#8A837A' : '#6B655E' }}
                                        >
                                            {ar}
                                        </div>
                                        <p
                                            className="my-3 mb-[18px] text-[13.5px] leading-[1.5]"
                                            style={{ color: dark ? '#C9C3B8' : '#6B655E' }}
                                        >
                                            {desc}
                                        </p>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <span className="font-mono text-[11px] text-[#8A837A]">
                                                    {dark ? 'SUR DEVIS' : 'DÈS'}
                                                </span>
                                                <div
                                                    className="font-heading text-[24px] font-bold"
                                                    style={{ color: dark ? '#F6F2EA' : undefined }}
                                                >
                                                    {price}{' '}
                                                    <span className="text-[14px] text-[#6B655E]">
                                                        DH
                                                    </span>
                                                </div>
                                            </div>
                                            <span
                                                className="flex h-[38px] w-[38px] items-center justify-center rounded"
                                                style={{
                                                    background: dark ? GREEN : AMBER,
                                                    color: dark ? '#fff' : INK,
                                                }}
                                            >
                                                <ArrowRight size={18} strokeWidth={2.5} />
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ░░ 06 · NAV + RDV ░░ */}
                <section
                    id="rdv"
                    className="px-10 py-[88px] text-[#F6F2EA]"
                    style={{ background: INK }}
                >
                    <div className="mx-auto max-w-[1240px]">
                        <SectionLabel n="06" label="En contexte · واجهة النظام" />
                        <h2 className="m-0 mb-11 font-heading text-[46px] font-semibold uppercase">
                            Navigation &amp; rendez-vous
                        </h2>

                        {/* app shell mock */}
                        <div
                            className="overflow-hidden rounded text-[#211E1A] shadow-[0_24px_60px_rgba(0,0,0,.4)]"
                            style={{ background: '#FBF8F1' }}
                        >
                            {/* top bar */}
                            <div
                                className="flex h-[60px] items-center gap-5 px-[22px]"
                                style={{ background: INK }}
                            >
                                <div className="flex items-center gap-[11px]">
                                    <div
                                        className="flex h-[30px] w-[30px] items-center justify-center"
                                        style={{ background: AMBER, color: INK, clipPath: hexagon }}
                                    >
                                        <Wrench size={15} strokeWidth={2.5} />
                                    </div>
                                    <span className="font-heading text-[14px] font-semibold uppercase tracking-[0.12em] text-[#F6F2EA]">
                                        Direct Auto Care
                                    </span>
                                </div>
                                <div className="ml-[18px] hidden items-center gap-[6px] font-heading text-[13px] uppercase tracking-[0.05em] lg:flex">
                                    <span
                                        className="rounded px-[14px] py-[7px]"
                                        style={{ background: INK_700, color: AMBER }}
                                    >
                                        Tableau de bord
                                    </span>
                                    <span className="px-[14px] py-[7px] text-[#C9C3B8]">
                                        Bons de travail
                                    </span>
                                    <span className="px-[14px] py-[7px] text-[#C9C3B8]">
                                        Véhicules
                                    </span>
                                    <span className="px-[14px] py-[7px] text-[#C9C3B8]">
                                        Clients
                                    </span>
                                </div>
                                <div className="ml-auto flex items-center gap-[14px]">
                                    <div
                                        className="hidden w-[200px] items-center gap-2 rounded px-3 py-2 sm:flex"
                                        style={{ background: INK_800 }}
                                    >
                                        <Search size={15} className="text-[#6B655E]" />
                                        <span className="text-[13px] text-[#6B655E]">
                                            Rechercher…
                                        </span>
                                    </div>
                                    <div className="relative">
                                        <Bell size={20} className="text-[#C9C3B8]" />
                                        <span className="absolute -right-[3px] -top-[3px] h-2 w-2 rounded-full bg-[#E5484D]" />
                                    </div>
                                    <div
                                        className="flex h-[30px] w-[30px] items-center justify-center rounded-full font-heading text-[13px] font-semibold text-white"
                                        style={{ background: GREEN }}
                                    >
                                        KB
                                    </div>
                                </div>
                            </div>
                            {/* body */}
                            <div className="grid min-h-[380px] grid-cols-1 md:grid-cols-[200px_1fr]">
                                {/* sidebar */}
                                <div className="hidden flex-col gap-1 border-r border-[#EFEADD] bg-white px-[14px] py-[18px] md:flex">
                                    <SideHeader>ATELIER</SideHeader>
                                    <SideItem icon={LayoutGrid} active>
                                        Tableau de bord
                                    </SideItem>
                                    <SideItem icon={ClipboardList} badge="7">
                                        Bons de travail
                                    </SideItem>
                                    <SideItem icon={Car}>Véhicules</SideItem>
                                    <SideItem icon={CalendarDays}>Planning</SideItem>
                                    <SideItem icon={Users}>Clients</SideItem>
                                    <SideHeader className="pt-[14px]">GESTION</SideHeader>
                                    <SideItem icon={Receipt}>Factures</SideItem>
                                    <SideItem icon={Settings}>Réglages</SideItem>
                                </div>
                                {/* content: appointments */}
                                <div className="p-6">
                                    <div className="mb-[18px] flex items-center justify-between">
                                        <div>
                                            <h3 className="m-0 font-heading text-[24px] font-semibold uppercase">
                                                Rendez-vous du jour
                                            </h3>
                                            <div className="font-mono text-xs text-[#8A837A]">
                                                Samedi 28 juin 2026 · 5 fiches
                                            </div>
                                        </div>
                                        <button
                                            className="inline-flex items-center gap-2 rounded px-[18px] py-[11px] font-heading text-[13px] font-semibold uppercase tracking-[0.06em]"
                                            style={{ background: AMBER, color: INK }}
                                        >
                                            <Plus size={15} strokeWidth={2.5} /> Planifier
                                        </button>
                                    </div>
                                    <div className="flex flex-col gap-[10px]">
                                        {APPOINTMENTS.map((a) => (
                                            <div
                                                key={a.time}
                                                className="flex items-center gap-4 rounded border border-[#EFEADD] bg-white px-4 py-[14px]"
                                                style={{ borderLeft: `3px solid ${a.edge}` }}
                                            >
                                                <div className="min-w-[54px] text-center">
                                                    <div className="font-heading text-[19px] font-bold">
                                                        {a.time}
                                                    </div>
                                                    <div className="font-mono text-[10px] text-[#8A837A]">
                                                        {a.duration}
                                                    </div>
                                                </div>
                                                <div className="h-[34px] w-px bg-[#EFEADD]" />
                                                <div
                                                    className="flex h-10 w-10 items-center justify-center rounded"
                                                    style={{ background: INK }}
                                                >
                                                    <a.icon
                                                        size={20}
                                                        color={AMBER}
                                                        strokeWidth={1.5}
                                                    />
                                                </div>
                                                <div className="flex-1">
                                                    <div className="text-[15px] font-semibold">
                                                        {a.title}
                                                    </div>
                                                    <div className="text-[12.5px] text-[#8A837A]">
                                                        {a.customer} ·{' '}
                                                        <span className="font-mono">{a.plate}</span>
                                                    </div>
                                                </div>
                                                <div className="hidden sm:block">{a.badge}</div>
                                                <div
                                                    className="flex h-[30px] w-[30px] items-center justify-center rounded-full font-heading text-xs font-semibold"
                                                    style={{
                                                        background: a.avatarBg,
                                                        color: a.avatarFg ?? '#fff',
                                                    }}
                                                >
                                                    {a.initials || <User size={14} />}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p className="m-0 mt-10 text-center font-mono text-xs uppercase tracking-[0.14em] text-[#6B655E]">
                            Direct Auto Care · Design System · Réparer. Entretenir. En confiance.
                        </p>
                    </div>
                </section>
            </div>
        </>
    );
}

// ── Local presentational helpers ────────────────────────────────────────────

function LogoChip({
    bg,
    border,
    dir,
    sub,
    subColor,
    dirColor,
    note,
    noteColor,
}: {
    bg: string;
    border?: boolean;
    dir: string;
    sub: string;
    subColor: string;
    dirColor: string;
    note: string;
    noteColor: string;
}) {
    return (
        <div
            className="flex flex-col items-center gap-2 rounded px-[18px] py-[30px]"
            style={{ background: bg, border: border ? '1px solid #E4DECF' : undefined }}
        >
            <div
                className="font-heading text-[26px] font-bold uppercase tracking-[0.04em]"
                style={{ color: dirColor }}
            >
                {dir}
            </div>
            <div
                className="pl-[0.4em] font-heading text-[9px] font-medium uppercase tracking-[0.4em]"
                style={{ color: subColor }}
            >
                {sub}
            </div>
            <div
                className="mt-[6px] font-mono text-[10px] tracking-[0.1em]"
                style={{ color: noteColor }}
            >
                {note}
            </div>
        </div>
    );
}

function FeatureSwatch({
    bg,
    fg,
    kicker,
    name,
    code,
    bordered,
    kickerColor,
    codeColor,
}: {
    bg: string;
    fg: string;
    kicker: string;
    name: string;
    code: string;
    bordered?: boolean;
    kickerColor?: string;
    codeColor?: string;
}) {
    return (
        <div
            className="flex min-h-[200px] flex-col justify-between rounded p-7"
            style={{
                background: bg,
                color: fg,
                border: bordered ? '1px solid #312D27' : undefined,
            }}
        >
            <div className="font-mono text-[11px] tracking-[0.14em]" style={{ color: kickerColor }}>
                {kicker}
            </div>
            <div>
                <div className="font-heading text-[34px] font-bold uppercase">{name}</div>
                <div className="mt-[6px] font-mono text-[13px]" style={{ color: codeColor }}>
                    {code}
                </div>
            </div>
        </div>
    );
}

function Swatch({ step, hex, h }: { step: string; hex: string; h: string }) {
    return (
        <div className="overflow-hidden rounded border border-[#312D27]">
            <div style={{ height: h, background: hex }} />
            <div className="px-3 py-[10px] font-mono text-[11px]">
                <div className="text-[#C9C3B8]">{step}</div>
                <div className="text-[#6B655E]">{hex}</div>
            </div>
        </div>
    );
}

function TypeCard({
    sample,
    sampleClass,
    sampleDir,
    name,
    desc,
}: {
    sample: string;
    sampleClass: string;
    sampleDir?: 'rtl';
    name: string;
    desc: string;
}) {
    return (
        <div className="rounded border border-[#E4DECF] bg-white p-7">
            <div className={`text-[64px] leading-none ${sampleClass}`} dir={sampleDir}>
                {sample}
            </div>
            <div className="mt-[14px] font-heading text-[18px] font-semibold uppercase tracking-[0.06em]">
                {name}
            </div>
            <div className="mt-[6px] text-[13.5px] leading-[1.5] text-[#6B655E]">{desc}</div>
        </div>
    );
}

function ScaleRow({
    children,
    meta,
    last,
}: {
    children: React.ReactNode;
    meta: string;
    last?: boolean;
}) {
    return (
        <div
            className="flex items-baseline justify-between gap-5 py-[22px]"
            style={{ borderBottom: last ? undefined : '1px solid #EFEADD' }}
        >
            <div>{children}</div>
            <div className="hidden whitespace-nowrap font-mono text-[11px] text-[#8A837A] sm:block">
                {meta}
            </div>
        </div>
    );
}

function Field({
    label,
    hint,
    children,
}: {
    label: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <label className="mb-[7px] block text-[12.5px] font-semibold tracking-[0.03em] text-[#312D27]">
                {label} {hint && <span className="font-normal text-[#8A837A]">{hint}</span>}
            </label>
            {children}
        </div>
    );
}

function StatusPill({
    children,
    bg,
    color,
    dot,
    pulse,
    icon: Icon,
}: {
    children: React.ReactNode;
    bg: string;
    color: string;
    dot?: string;
    pulse?: boolean;
    icon?: LucideIcon;
}) {
    return (
        <span
            className="inline-flex items-center gap-[7px] rounded-full px-[13px] py-[6px] text-[12.5px] font-semibold"
            style={{ background: bg, color }}
        >
            {dot && (
                <span
                    className={`h-[7px] w-[7px] rounded-full ${pulse ? 'animate-pulse' : ''}`}
                    style={{ background: dot }}
                />
            )}
            {Icon && <Icon size={14} strokeWidth={2.5} />}
            {children}
        </span>
    );
}

function SideHeader({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div
            className={`px-[10px] py-[6px] font-mono text-[10px] tracking-[0.16em] text-[#B6AFA2] ${className}`}
        >
            {children}
        </div>
    );
}

function SideItem({
    icon: Icon,
    children,
    active,
    badge,
}: {
    icon: LucideIcon;
    children: React.ReactNode;
    active?: boolean;
    badge?: string;
}) {
    return (
        <div
            className="flex items-center gap-[11px] rounded px-3 py-[10px] text-[13.5px]"
            style={
                active
                    ? { background: '#FDF6E6', color: '#A66A07', fontWeight: 600 }
                    : { color: '#6B655E' }
            }
        >
            <Icon size={17} strokeWidth={active ? 2.2 : 1.6} />
            {children}
            {badge && (
                <span
                    className="ml-auto rounded-full px-[7px] py-px font-mono text-[11px]"
                    style={{ background: INK, color: AMBER }}
                >
                    {badge}
                </span>
            )}
        </div>
    );
}

const APPOINTMENTS: AppointmentRow[] = [
    {
        time: '08:30',
        duration: '45 MIN',
        icon: Droplet,
        title: 'Vidange + filtres — Dacia Logan',
        customer: 'Youssef Benali',
        plate: '1234·أ·56',
        edge: GREEN,
        badge: (
            <span className="inline-flex items-center gap-[7px] rounded-full bg-[#E8F6ED] px-3 py-[6px] text-xs font-semibold text-[#1E7A3C]">
                <CheckCircle2 size={14} strokeWidth={2.5} /> Terminé
            </span>
        ),
        initials: 'KB',
        avatarBg: GREEN,
    },
    {
        time: '09:30',
        duration: '2 H',
        icon: Wrench,
        title: 'Freinage avant — VW Golf',
        customer: 'Salma Idrissi',
        plate: '4480·ب·12',
        edge: '#3B82C4',
        badge: (
            <span className="inline-flex items-center gap-[7px] rounded-full bg-[#EAF1FA] px-3 py-[6px] text-xs font-semibold text-[#2C5E96]">
                <span className="h-[7px] w-[7px] rounded-full bg-[#3B82C4]" /> En cours
            </span>
        ),
        initials: 'RH',
        avatarBg: INK_700,
    },
    {
        time: '11:00',
        duration: '30 MIN',
        icon: SprayCan,
        title: 'Lavage complet — Peugeot 208',
        customer: 'Karim Ouazzani',
        plate: '9921·د·30',
        edge: AMBER,
        badge: (
            <span className="inline-flex items-center gap-[7px] rounded-full bg-[#FDF6E6] px-3 py-[6px] text-xs font-semibold text-[#A66A07]">
                <span className="h-[7px] w-[7px] animate-pulse rounded-full bg-[#F4A81C]" /> En
                attente
            </span>
        ),
        initials: '',
        avatarBg: '#E4DECF',
        avatarFg: '#8A837A',
    },
];
