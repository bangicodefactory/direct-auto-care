import InputError from '@/Components/InputError';
import type { User } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarCheck,
    Car,
    CheckCircle,
    ClipboardList,
    LayoutDashboard,
    Lock,
    LogIn,
    Mail,
    Package,
    Receipt,
    TrendingUp,
    UserPlus,
    Wrench,
} from 'lucide-react';

// Landing page — converted from the "Direct Auto Care - Landing" Claude Design
// (project "Garage system design"). The marketing palette is intentionally the
// DAC brand, independent of the per-client /branding.css theme.

interface WelcomeProps {
    auth: {
        user: User | null;
    };
}

const oswald = "'Oswald', sans-serif";
const mono = "'IBM Plex Mono', monospace";
const cairo = "'Cairo', sans-serif";

const CSS = `
.dc-landing{--dac-primary:#F4A81C;--dac-accent:#2FA84F;--dac-radius:6px;font-family:'IBM Plex Sans',system-ui,sans-serif;}
.dc-landing ::selection{background:var(--dac-primary);color:#15130F;}
.dc-stripe{background-image:repeating-linear-gradient(45deg,#27241F 0 10px,#211E1A 10px 20px);}
.dc-link{color:#C9C3B8;text-decoration:none;transition:color .15s;}
.dc-link:hover{color:var(--dac-primary);}
.dc-btn-amber{transition:background .15s;}
.dc-btn-amber:hover{background:#D98E0A !important;}
.dc-btn-outline{transition:border-color .15s,color .15s;}
.dc-btn-outline:hover{border-color:var(--dac-primary) !important;color:var(--dac-primary) !important;}
.dc-btn-dark{transition:background .15s;}
.dc-btn-dark:hover{background:#000 !important;}
.dc-input{width:100%;font-family:'IBM Plex Sans',sans-serif;font-size:15px;padding:12px 14px 12px 42px;border:1.5px solid #E4DECF;border-radius:4px;background:#fff;color:#211E1A;outline:none;transition:border-color .15s;}
.dc-input:focus{border-color:var(--dac-primary);}
.dc-input::placeholder{color:#8A837A;}
.dc-hero-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:64px;align-items:center;}
.dc-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
@media (max-width:960px){
  .dc-hero-grid{grid-template-columns:1fr;gap:44px;}
  .dc-grid3{grid-template-columns:1fr;}
  .dc-h1{font-size:52px !important;}
  .dc-nav-links{display:none !important;}
}
@media (max-width:520px){
  .dc-nav{padding:0 16px !important;}
  .dc-nav-secondary{display:none !important;}
  .dc-section{padding-left:20px !important;padding-right:20px !important;}
}
`;

function Logo({ size = 36 }: { size?: number }) {
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: size,
                height: size + 2,
                background: 'var(--dac-accent)',
                clipPath: 'polygon(50% 0,100% 22%,100% 70%,50% 100%,0 70%,0 22%)',
            }}
        >
            <Wrench size={size / 2} color="#fff" strokeWidth={2.5} />
        </div>
    );
}

function scrollToLogin() {
    const el = document.getElementById('login');
    if (el) {
        window.scrollTo({
            top: el.getBoundingClientRect().top + window.scrollY - 90,
            behavior: 'smooth',
        });
    }
}

const heroCardShell: React.CSSProperties = {
    background: '#FBF8F1',
    borderRadius: 'var(--dac-radius)',
    boxShadow: '0 30px 70px rgba(0,0,0,.45)',
    overflow: 'hidden',
    color: '#211E1A',
};

const amberButton: React.CSSProperties = {
    width: '100%',
    marginTop: 22,
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 9,
    border: 'none',
    cursor: 'pointer',
    background: 'var(--dac-primary)',
    color: '#15130F',
    fontFamily: oswald,
    fontWeight: 600,
    fontSize: 15,
    letterSpacing: '.06em',
    textTransform: 'uppercase',
    padding: '14px 0',
    borderRadius: 4,
    textDecoration: 'none',
};

const fieldLabel: React.CSSProperties = {
    fontSize: 12,
    fontWeight: 600,
    letterSpacing: '.03em',
    color: '#312D27',
};

const inputIcon: React.CSSProperties = {
    position: 'absolute',
    left: 14,
    top: '50%',
    transform: 'translateY(-50%)',
};

function LoginCard() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    }

    return (
        <div id="login" style={heroCardShell}>
            <div style={{ height: 5, background: 'var(--dac-primary)' }} />
            <form onSubmit={submit} style={{ padding: '30px 30px 32px' }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                        fontFamily: oswald,
                        fontWeight: 600,
                        fontSize: 23,
                        textTransform: 'uppercase',
                        lineHeight: 1,
                    }}
                >
                    <LogIn size={22} color="var(--dac-primary)" strokeWidth={2.5} />
                    Connexion
                </div>
                <div style={{ fontSize: 13, color: '#8A837A', marginTop: 6 }}>
                    Accédez à votre atelier · ادخل إلى ورشتك
                </div>

                <div style={{ marginTop: 24 }}>
                    <label
                        htmlFor="landing-email"
                        style={{ ...fieldLabel, display: 'block', marginBottom: 7 }}
                    >
                        Adresse e-mail
                    </label>
                    <div style={{ position: 'relative' }}>
                        <Mail size={16} color="#8A837A" style={inputIcon} />
                        <input
                            id="landing-email"
                            type="email"
                            className="dc-input"
                            placeholder="vous@garage.ma"
                            value={data.email}
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.email} className="mt-1" />
                </div>
                <div style={{ marginTop: 16 }}>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            marginBottom: 7,
                        }}
                    >
                        <label htmlFor="landing-password" style={fieldLabel}>
                            Mot de passe
                        </label>
                        <Link
                            href={route('password.request')}
                            className="dc-link"
                            style={{ fontSize: 11.5, color: '#A66A07' }}
                        >
                            Oublié ?
                        </Link>
                    </div>
                    <div style={{ position: 'relative' }}>
                        <Lock size={16} color="#8A837A" style={inputIcon} />
                        <input
                            id="landing-password"
                            type="password"
                            className="dc-input"
                            value={data.password}
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.password} className="mt-1" />
                </div>
                <button
                    type="submit"
                    disabled={processing}
                    className="dc-btn-amber"
                    style={{ ...amberButton, opacity: processing ? 0.7 : 1 }}
                >
                    {processing ? 'Connexion…' : 'Se connecter'}
                    <ArrowRight size={16} strokeWidth={2.5} />
                </button>
                <div
                    style={{
                        textAlign: 'center',
                        fontSize: 11.5,
                        color: '#8A837A',
                        marginTop: 20,
                        lineHeight: 1.5,
                    }}
                >
                    Atelier protégé · données chiffrées
                    <br />
                    <span style={{ fontFamily: cairo, direction: 'rtl' }}>
                        بياناتك محمية ومشفّرة
                    </span>
                </div>
            </form>
        </div>
    );
}

function DashboardCard({ user }: { user: User }) {
    return (
        <div id="login" style={heroCardShell}>
            <div style={{ height: 5, background: 'var(--dac-primary)' }} />
            <div style={{ padding: '30px 30px 32px' }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                        fontFamily: oswald,
                        fontWeight: 600,
                        fontSize: 23,
                        textTransform: 'uppercase',
                        lineHeight: 1,
                    }}
                >
                    <CheckCircle size={22} color="var(--dac-accent)" strokeWidth={2.5} />
                    Bon retour
                </div>
                <div style={{ fontSize: 13, color: '#8A837A', marginTop: 6 }}>
                    Session ouverte — {user.name}
                </div>
                <Link href={route('dashboard')} className="dc-btn-amber" style={amberButton}>
                    Ouvrir le tableau de bord
                    <ArrowRight size={16} strokeWidth={2.5} />
                </Link>
            </div>
        </div>
    );
}

const FEATURES = [
    {
        icon: ClipboardList,
        title: 'Bons de travail',
        text: "Créez une fiche en quelques secondes : véhicule, opérations, pièces et main-d'œuvre, suivi en temps réel.",
    },
    {
        icon: CalendarCheck,
        title: 'Rendez-vous',
        text: 'Planning visuel de la journée, rappels automatiques au client, zéro créneau perdu.',
    },
    {
        icon: Car,
        title: 'Parc véhicules',
        text: 'Historique complet par véhicule et par client : interventions, kilométrage et pièces remplacées.',
    },
    {
        icon: Receipt,
        title: 'Devis & factures',
        text: 'Devis en un clic, conversion en facture, TVA et impression PDF aux couleurs de votre garage.',
    },
    {
        icon: Package,
        title: 'Stock & pièces',
        text: 'Suivi des pièces et consommables, alertes de seuil et coût réel par intervention.',
    },
];

const STEPS = [
    {
        num: '01',
        icon: UserPlus,
        iconBg: 'var(--dac-primary)',
        iconColor: '#15130F',
        title: 'Le client arrive',
        text: 'Enregistrez véhicule et demande en quelques touches, ou retrouvez le client par sa plaque.',
    },
    {
        num: '02',
        icon: Wrench,
        iconBg: 'var(--dac-primary)',
        iconColor: '#15130F',
        title: "L'atelier travaille",
        text: 'Le mécanicien suit le bon, ajoute pièces et temps. Le statut avance, le client est notifié.',
    },
    {
        num: '03',
        icon: CheckCircle,
        iconBg: 'var(--dac-accent)',
        iconColor: '#fff',
        title: 'Clé rendue, payé',
        text: 'Facture générée automatiquement, historique archivé, le véhicule rejoint le parc client.',
    },
];

function SectionHeading({
    num,
    tag,
    title,
    light,
}: {
    num: string;
    tag: string;
    title: string;
    light?: boolean;
}) {
    return (
        <>
            <div style={{ display: 'flex', alignItems: 'baseline', gap: 18, marginBottom: 8 }}>
                <span
                    style={{
                        fontFamily: mono,
                        fontSize: 13,
                        letterSpacing: '.2em',
                        color: 'var(--dac-primary)',
                        fontWeight: 600,
                    }}
                >
                    {num}
                </span>
                <span
                    style={{
                        fontFamily: mono,
                        fontSize: 12,
                        letterSpacing: '.2em',
                        color: '#8A837A',
                        textTransform: 'uppercase',
                    }}
                >
                    {tag}
                </span>
            </div>
            <h2
                style={{
                    fontFamily: oswald,
                    fontWeight: 600,
                    fontSize: 46,
                    margin: '0 0 8px',
                    textTransform: 'uppercase',
                    maxWidth: 640,
                    color: light ? '#F6F2EA' : undefined,
                }}
            >
                {title}
            </h2>
        </>
    );
}

export default function Welcome({ auth }: WelcomeProps) {
    return (
        <>
            <Head title="Votre atelier, sous contrôle" />
            <style>{CSS}</style>
            <div
                className="dc-landing"
                style={{
                    color: '#211E1A',
                    background: '#15130F',
                    width: '100%',
                    overflow: 'hidden',
                }}
            >
                {/* NAV */}
                <div
                    className="dc-nav"
                    style={{
                        position: 'sticky',
                        top: 0,
                        zIndex: 40,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 24,
                        padding: '0 40px',
                        height: 68,
                        background: 'rgba(21,19,15,.92)',
                        backdropFilter: 'blur(10px)',
                        borderBottom: '1px solid #312D27',
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: 13 }}>
                        <Logo />
                        <div
                            style={{
                                fontFamily: oswald,
                                fontWeight: 600,
                                letterSpacing: '.13em',
                                color: '#F6F2EA',
                                fontSize: 16,
                                textTransform: 'uppercase',
                            }}
                        >
                            Direct<span style={{ color: 'var(--dac-primary)' }}>·</span>Auto Care
                        </div>
                    </div>
                    <div
                        className="dc-nav-links"
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 30,
                            fontFamily: mono,
                            fontSize: 11,
                            letterSpacing: '.12em',
                            textTransform: 'uppercase',
                        }}
                    >
                        <a href="#features" className="dc-link">
                            Fonctions
                        </a>
                        <a href="#flow" className="dc-link">
                            Comment ça marche
                        </a>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="dc-btn-amber"
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 8,
                                    background: 'var(--dac-primary)',
                                    color: '#15130F',
                                    fontFamily: oswald,
                                    fontWeight: 600,
                                    fontSize: 13,
                                    letterSpacing: '.06em',
                                    textTransform: 'uppercase',
                                    padding: '11px 20px',
                                    borderRadius: 4,
                                    textDecoration: 'none',
                                }}
                            >
                                <LayoutDashboard size={15} strokeWidth={2.5} />
                                Tableau de bord
                            </Link>
                        ) : (
                            <>
                                <button
                                    onClick={scrollToLogin}
                                    className="dc-btn-outline dc-nav-secondary"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 8,
                                        cursor: 'pointer',
                                        background: 'transparent',
                                        color: '#F6F2EA',
                                        border: '1.5px solid #312D27',
                                        fontFamily: oswald,
                                        fontWeight: 600,
                                        fontSize: 13,
                                        letterSpacing: '.06em',
                                        textTransform: 'uppercase',
                                        padding: '9.5px 18px',
                                        borderRadius: 4,
                                    }}
                                >
                                    <LogIn size={15} strokeWidth={2.5} />
                                    Connexion
                                </button>
                                <button
                                    onClick={scrollToLogin}
                                    className="dc-btn-amber"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 8,
                                        border: 'none',
                                        cursor: 'pointer',
                                        background: 'var(--dac-primary)',
                                        color: '#15130F',
                                        fontFamily: oswald,
                                        fontWeight: 600,
                                        fontSize: 13,
                                        letterSpacing: '.06em',
                                        textTransform: 'uppercase',
                                        padding: '11px 20px',
                                        borderRadius: 4,
                                    }}
                                >
                                    Démarrer
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {/* HERO */}
                <section
                    className="dc-section"
                    style={{
                        position: 'relative',
                        background: '#15130F',
                        color: '#F6F2EA',
                        padding: '84px 40px 96px',
                    }}
                >
                    <div
                        className="dc-stripe"
                        style={{ position: 'absolute', inset: 0, opacity: 0.45 }}
                    />
                    <div
                        style={{
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            right: 0,
                            height: 5,
                            background: 'var(--dac-primary)',
                        }}
                    />
                    <div
                        className="dc-hero-grid"
                        style={{ position: 'relative', maxWidth: 1240, margin: '0 auto' }}
                    >
                        <div>
                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 13,
                                    marginBottom: 30,
                                    fontFamily: mono,
                                    fontSize: 12,
                                    letterSpacing: '.2em',
                                    textTransform: 'uppercase',
                                    color: 'var(--dac-primary)',
                                }}
                            >
                                <span
                                    style={{
                                        width: 34,
                                        height: 1,
                                        background: 'var(--dac-primary)',
                                    }}
                                />
                                Logiciel de gestion d'atelier
                            </div>
                            <h1
                                className="dc-h1"
                                style={{
                                    fontFamily: oswald,
                                    fontWeight: 700,
                                    fontSize: 74,
                                    lineHeight: 1.08,
                                    letterSpacing: '.01em',
                                    margin: 0,
                                    textTransform: 'uppercase',
                                }}
                            >
                                Votre atelier,
                                <br />
                                <span style={{ color: 'var(--dac-primary)' }}>sous contrôle.</span>
                            </h1>
                            <p
                                style={{
                                    maxWidth: 500,
                                    margin: '26px 0 0',
                                    fontSize: 18,
                                    lineHeight: 1.6,
                                    color: '#C9C3B8',
                                }}
                            >
                                Bons de travail, rendez-vous, véhicules et factures — réunis dans un
                                seul outil pensé pour les garages. Du devis à la clé rendue.
                            </p>
                            <div
                                style={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    gap: 28,
                                    marginTop: 40,
                                }}
                            >
                                {[
                                    ['+30%', 'de fiches traitées / jour'],
                                    ['0', 'rendez-vous oublié'],
                                    ['2 min', 'pour créer un devis'],
                                ].map(([stat, label], i) => (
                                    <div key={stat} style={{ display: 'flex', gap: 28 }}>
                                        {i > 0 && (
                                            <div style={{ width: 1, background: '#312D27' }} />
                                        )}
                                        <div>
                                            <div
                                                style={{
                                                    fontFamily: oswald,
                                                    fontWeight: 700,
                                                    fontSize: 34,
                                                    color: 'var(--dac-primary)',
                                                }}
                                            >
                                                {stat}
                                            </div>
                                            <div
                                                style={{
                                                    fontSize: 12.5,
                                                    color: '#8A837A',
                                                    letterSpacing: '.02em',
                                                }}
                                            >
                                                {label}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        {auth.user ? <DashboardCard user={auth.user} /> : <LoginCard />}
                    </div>
                </section>

                {/* FEATURES */}
                <section
                    id="features"
                    className="dc-section"
                    style={{ padding: '88px 40px', background: '#F6F2EA' }}
                >
                    <div style={{ maxWidth: 1240, margin: '0 auto' }}>
                        <SectionHeading
                            num="01"
                            tag="Tout l'atelier · كل الورشة"
                            title="Un seul outil, du devis à la facture"
                        />
                        <p
                            style={{
                                maxWidth: 560,
                                margin: '0 0 44px',
                                fontSize: 16,
                                lineHeight: 1.6,
                                color: '#6B655E',
                            }}
                        >
                            Chaque fonction conçue pour le quotidien d'un garage — rapide à l'écran,
                            claire pour le client.
                        </p>
                        <div className="dc-grid3">
                            {FEATURES.map(({ icon: Icon, title, text }) => (
                                <div
                                    key={title}
                                    style={{
                                        background: '#fff',
                                        border: '1px solid #E4DECF',
                                        borderRadius: 'var(--dac-radius)',
                                        padding: 28,
                                    }}
                                >
                                    <div
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            width: 54,
                                            height: 54,
                                            borderRadius: 'var(--dac-radius)',
                                            background: '#15130F',
                                            marginBottom: 20,
                                        }}
                                    >
                                        <Icon size={27} color="var(--dac-primary)" />
                                    </div>
                                    <h3
                                        style={{
                                            fontFamily: oswald,
                                            fontWeight: 600,
                                            fontSize: 22,
                                            margin: 0,
                                            textTransform: 'uppercase',
                                        }}
                                    >
                                        {title}
                                    </h3>
                                    <p
                                        style={{
                                            fontSize: 14,
                                            color: '#6B655E',
                                            lineHeight: 1.55,
                                            margin: '10px 0 0',
                                        }}
                                    >
                                        {text}
                                    </p>
                                </div>
                            ))}
                            <div
                                style={{
                                    background: '#15130F',
                                    border: '1px solid #15130F',
                                    borderRadius: 'var(--dac-radius)',
                                    padding: 28,
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        width: 54,
                                        height: 54,
                                        borderRadius: 'var(--dac-radius)',
                                        background: 'var(--dac-accent)',
                                        marginBottom: 20,
                                    }}
                                >
                                    <TrendingUp size={27} color="#fff" />
                                </div>
                                <h3
                                    style={{
                                        fontFamily: oswald,
                                        fontWeight: 600,
                                        fontSize: 22,
                                        margin: 0,
                                        textTransform: 'uppercase',
                                        color: '#F6F2EA',
                                    }}
                                >
                                    Tableau de bord
                                </h3>
                                <p
                                    style={{
                                        fontSize: 14,
                                        color: '#C9C3B8',
                                        lineHeight: 1.55,
                                        margin: '10px 0 0',
                                    }}
                                >
                                    Chiffre d'affaires, fiches du jour et charge de l'atelier d'un
                                    coup d'œil, où que vous soyez.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* FLOW */}
                <section
                    id="flow"
                    className="dc-section"
                    style={{ padding: '88px 40px', background: '#15130F', color: '#F6F2EA' }}
                >
                    <div style={{ maxWidth: 1240, margin: '0 auto' }}>
                        <SectionHeading
                            num="02"
                            tag="En 3 étapes · 3 خطوات"
                            title="De la clé au paiement"
                            light
                        />
                        <div className="dc-grid3" style={{ marginTop: 36 }}>
                            {STEPS.map(({ num, icon: Icon, iconBg, iconColor, title, text }) => (
                                <div
                                    key={num}
                                    style={{
                                        background: '#211E1A',
                                        border: '1px solid #312D27',
                                        borderRadius: 'var(--dac-radius)',
                                        padding: 30,
                                    }}
                                >
                                    <div
                                        style={{
                                            fontFamily: oswald,
                                            fontWeight: 700,
                                            fontSize: 60,
                                            color: '#312D27',
                                            lineHeight: 0.8,
                                        }}
                                    >
                                        {num}
                                    </div>
                                    <div
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            width: 46,
                                            height: 46,
                                            borderRadius: 'var(--dac-radius)',
                                            background: iconBg,
                                            color: iconColor,
                                            margin: '18px 0',
                                        }}
                                    >
                                        <Icon size={22} strokeWidth={2.5} />
                                    </div>
                                    <h3
                                        style={{
                                            fontFamily: oswald,
                                            fontWeight: 600,
                                            fontSize: 21,
                                            margin: 0,
                                            textTransform: 'uppercase',
                                        }}
                                    >
                                        {title}
                                    </h3>
                                    <p
                                        style={{
                                            fontSize: 14,
                                            color: '#C9C3B8',
                                            lineHeight: 1.55,
                                            margin: '10px 0 0',
                                        }}
                                    >
                                        {text}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section
                    className="dc-section"
                    style={{
                        position: 'relative',
                        background: 'var(--dac-primary)',
                        color: '#15130F',
                        padding: '72px 40px',
                        overflow: 'hidden',
                    }}
                >
                    <div
                        style={{
                            position: 'relative',
                            maxWidth: 1240,
                            margin: '0 auto',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            gap: 32,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div>
                            <h2
                                style={{
                                    fontFamily: oswald,
                                    fontWeight: 700,
                                    fontSize: 48,
                                    margin: 0,
                                    textTransform: 'uppercase',
                                    lineHeight: 0.98,
                                }}
                            >
                                Prêt à équiper
                                <br />
                                votre atelier ?
                            </h2>
                            <p
                                style={{
                                    margin: '14px 0 0',
                                    fontSize: 16,
                                    color: '#5a3f08',
                                    maxWidth: 440,
                                }}
                            >
                                Mise en route en moins d'une heure, avec vos couleurs et votre
                                catalogue de services.
                            </p>
                        </div>
                        <button
                            onClick={scrollToLogin}
                            className="dc-btn-dark"
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: 10,
                                border: 'none',
                                cursor: 'pointer',
                                background: '#15130F',
                                color: '#F6F2EA',
                                fontFamily: oswald,
                                fontWeight: 600,
                                fontSize: 17,
                                letterSpacing: '.06em',
                                textTransform: 'uppercase',
                                padding: '18px 34px',
                                borderRadius: 4,
                            }}
                        >
                            Accéder à l'atelier
                            <ArrowRight size={18} strokeWidth={2.5} />
                        </button>
                    </div>
                </section>

                {/* FOOTER */}
                <footer
                    className="dc-section"
                    style={{ background: '#15130F', color: '#C9C3B8', padding: '54px 40px 40px' }}
                >
                    <div
                        style={{
                            maxWidth: 1240,
                            margin: '0 auto',
                            display: 'flex',
                            alignItems: 'flex-start',
                            justifyContent: 'space-between',
                            gap: 40,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div style={{ maxWidth: 300 }}>
                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 12,
                                    marginBottom: 16,
                                }}
                            >
                                <Logo size={32} />
                                <div
                                    style={{
                                        fontFamily: oswald,
                                        fontWeight: 600,
                                        letterSpacing: '.13em',
                                        color: '#F6F2EA',
                                        fontSize: 15,
                                        textTransform: 'uppercase',
                                    }}
                                >
                                    Direct·Auto Care
                                </div>
                            </div>
                            <p
                                style={{
                                    fontSize: 13.5,
                                    lineHeight: 1.6,
                                    color: '#8A837A',
                                    margin: 0,
                                }}
                            >
                                Le logiciel de gestion d'atelier pensé pour les garages. Réparer.
                                Entretenir. En confiance.
                            </p>
                        </div>
                        <div style={{ display: 'flex', gap: 60, flexWrap: 'wrap' }}>
                            <div>
                                <div
                                    style={{
                                        fontFamily: mono,
                                        fontSize: 11,
                                        letterSpacing: '.16em',
                                        color: '#6B655E',
                                        textTransform: 'uppercase',
                                        marginBottom: 14,
                                    }}
                                >
                                    Produit
                                </div>
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: 9,
                                        fontSize: 13.5,
                                    }}
                                >
                                    <a href="#features" className="dc-link">
                                        Fonctions
                                    </a>
                                    <a href="#flow" className="dc-link">
                                        Comment ça marche
                                    </a>
                                </div>
                            </div>
                            <div>
                                <div
                                    style={{
                                        fontFamily: mono,
                                        fontSize: 11,
                                        letterSpacing: '.16em',
                                        color: '#6B655E',
                                        textTransform: 'uppercase',
                                        marginBottom: 14,
                                    }}
                                >
                                    Atelier
                                </div>
                                <div
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: 9,
                                        fontSize: 13.5,
                                    }}
                                >
                                    <a href="#login" className="dc-link">
                                        Connexion
                                    </a>
                                </div>
                            </div>
                            <div style={{ direction: 'rtl', textAlign: 'right' }}>
                                <div
                                    style={{
                                        fontFamily: cairo,
                                        fontSize: 13,
                                        letterSpacing: '.04em',
                                        color: '#6B655E',
                                        marginBottom: 14,
                                        fontWeight: 700,
                                    }}
                                >
                                    العربية
                                </div>
                                <div
                                    style={{
                                        fontFamily: cairo,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: 9,
                                        fontSize: 14,
                                        fontWeight: 600,
                                    }}
                                >
                                    <a href="#login" className="dc-link">
                                        إدارة الورشة
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        style={{
                            maxWidth: 1240,
                            margin: '36px auto 0',
                            paddingTop: 22,
                            borderTop: '1px solid #312D27',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            gap: 16,
                            flexWrap: 'wrap',
                            fontFamily: mono,
                            fontSize: 11,
                            letterSpacing: '.1em',
                            color: '#6B655E',
                        }}
                    >
                        <span>© {new Date().getFullYear()} DIRECT AUTO CARE</span>
                        <span>FABRIQUÉ POUR LES ATELIERS · صُنع للورشات</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
