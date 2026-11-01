import InputError from '@/Components/InputError';
import type { User } from '@/types/models';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle, LayoutDashboard, LogIn } from 'lucide-react';
import type { ReactNode } from 'react';

// Landing page — "Narrative Workflow" redesign (design B): one car's day in the
// garage, stage by stage. The marketing palette is intentionally the DAC brand,
// independent of the per-client /branding.css theme. The product name is NOT —
// it comes from COMPANY_BRAND (appSettings.brand_name), so each deployment shows
// its own (e.g. "Garagio" on the demo, "Direct Auto Care" in production).

interface WelcomeProps {
    auth: {
        user: User | null;
    };
}

const CSS = `
/* Hallmark · macrostructure: Narrative Workflow · tone: industrial-editorial · anchor hue: amber 75°
 * nav: N6 masthead · footer: Ft5 statement · enrichment: none (inline product snippets per stage)
 * theme: DAC brand dark (paper dark · grotesk-condensed display · warm accent) */
.dc-landing{
  --dc-ink:#15130F;--dc-ink-2:#211E1A;--dc-ink-3:#27241F;--dc-line:#3A352E;
  --dc-paper:#F6F2EA;--dc-soft:#C9C3B8;--dc-mute:#9A9286;--dc-grey:#6B655E;
  --dc-amber:#F4A81C;--dc-amber-hover:#D98E0A;--dc-green:#2FA84F;--dc-green-lite:#6FD08A;--dc-error:#FF8A8D;
  --dc-display:'Oswald',sans-serif;--dc-body:'IBM Plex Sans',system-ui,sans-serif;
  --dc-mono:'IBM Plex Mono',monospace;--dc-ar:'Cairo',sans-serif;
  --dc-ease-out:cubic-bezier(.16,1,.3,1);
  min-height:100vh;overflow-x:clip;background:var(--dc-ink);color:var(--dc-soft);font-family:var(--dc-body);
}
.dc-landing ::selection{background:var(--dc-amber);color:var(--dc-ink);}
.dc-landing :focus-visible{outline:2px solid var(--dc-amber);outline-offset:2px;}
.dc-wrap{max-width:1240px;margin:0 auto;padding-left:24px;padding-right:24px;}
.dc-link{color:var(--dc-amber);text-decoration:none;white-space:nowrap;transition:color 150ms var(--dc-ease-out);}
.dc-link:hover{color:var(--dc-paper);}
.dc-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;border:0;border-radius:4px;
  background:var(--dc-amber);color:var(--dc-ink);font-family:var(--dc-body);font-weight:600;font-size:15px;
  padding:13px 16px;white-space:nowrap;text-decoration:none;cursor:pointer;transition:background 150ms var(--dc-ease-out);}
.dc-btn:hover{background:var(--dc-amber-hover);}
.dc-btn:active{transform:translateY(1px);}
.dc-btn:disabled{opacity:.7;cursor:progress;}
.dc-field{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:500;}
.dc-input{font:inherit;font-size:15px;font-weight:400;padding:12px 14px;border:1.5px solid var(--dc-line);border-radius:4px;
  background:var(--dc-ink);color:var(--dc-paper);outline:none;transition:border-color 150ms var(--dc-ease-out);}
.dc-input:focus{border-color:var(--dc-amber);}
.dc-input::placeholder{color:var(--dc-mute);}
.dc-landing .dc-error{color:var(--dc-error);}
.dc-display{font-family:var(--dc-display);font-weight:600;font-style:normal;color:var(--dc-paper);overflow-wrap:anywhere;min-width:0;margin:0;}
.dc-stage{display:grid;grid-template-columns:minmax(0,7rem) 8px minmax(0,1fr);column-gap:24px;border-top:2px solid var(--dc-line);padding:40px 0;}
.dc-stage-spine{border-radius:2px;background:repeating-linear-gradient(45deg,var(--dc-amber) 0 6px,var(--dc-ink-3) 6px 12px);}
.dc-stage-body{display:flex;flex-wrap:wrap;gap:32px;min-width:0;}
.dc-snippet{flex:1 1 320px;min-width:0;align-self:center;background:var(--dc-ink-2);border:1px solid var(--dc-line);border-radius:6px;padding:16px;font-size:14px;}
.dc-row{display:flex;justify-content:space-between;gap:16px;}
.dc-modules{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));column-gap:48px;}
@media (max-width:640px){
  .dc-stage{grid-template-columns:minmax(0,1fr);row-gap:12px;padding:32px 0;}
  .dc-stage-spine{height:8px;}
  .dc-masthead-strap{justify-content:center !important;text-align:center;}
  /* French and Arabic each get a whole line instead of the Arabic breaking mid-phrase. */
  .dc-strap-sep{display:none;}
  .dc-strap-ar{display:block;margin-top:4px;}
}
.dc-strap-ar{white-space:nowrap;}
@media (prefers-reduced-motion:reduce){
  .dc-landing *{transition-duration:0ms !important;}
}
`;

function scrollToLogin() {
    const el = document.getElementById('login');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

const cardShell: React.CSSProperties = {
    background: 'var(--dc-ink-2)',
    border: '1px solid var(--dc-line)',
    borderRadius: 6,
    padding: 24,
};

const cardTitle: React.CSSProperties = {
    display: 'flex',
    alignItems: 'center',
    gap: 10,
    fontSize: 22,
    lineHeight: 1,
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
        <form
            id="login"
            onSubmit={submit}
            style={{ ...cardShell, display: 'flex', flexDirection: 'column', gap: 14 }}
        >
            <h2 className="dc-display" style={cardTitle}>
                <LogIn size={20} color="var(--dc-amber)" strokeWidth={2.5} aria-hidden="true" />
                Ouvrir l&apos;atelier
            </h2>
            <label className="dc-field" htmlFor="landing-email">
                Adresse e-mail
                <input
                    id="landing-email"
                    type="email"
                    className="dc-input"
                    placeholder="vous@garage.ma"
                    value={data.email}
                    autoComplete="username"
                    onChange={(e) => setData('email', e.target.value)}
                />
                <InputError message={errors.email} className="dc-error" />
            </label>
            <label className="dc-field" htmlFor="landing-password">
                Mot de passe
                <input
                    id="landing-password"
                    type="password"
                    className="dc-input"
                    value={data.password}
                    autoComplete="current-password"
                    onChange={(e) => setData('password', e.target.value)}
                />
                <InputError message={errors.password} className="dc-error" />
            </label>
            <div
                style={{
                    display: 'flex',
                    flexWrap: 'wrap',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 12,
                    fontSize: 13,
                }}
            >
                <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        style={{ accentColor: 'var(--dc-amber)' }}
                    />
                    Se souvenir de moi
                </label>
                <Link href={route('password.request')} className="dc-link">
                    Mot de passe oublié ?
                </Link>
            </div>
            <button type="submit" disabled={processing} className="dc-btn">
                {processing ? 'Connexion…' : 'Se connecter'}
                <ArrowRight size={16} strokeWidth={2.5} aria-hidden="true" />
            </button>
        </form>
    );
}

function DashboardCard({ user }: { user: User }) {
    return (
        <div id="login" style={{ ...cardShell, display: 'flex', flexDirection: 'column', gap: 16 }}>
            <h2 className="dc-display" style={cardTitle}>
                <CheckCircle
                    size={20}
                    color="var(--dc-green-lite)"
                    strokeWidth={2.5}
                    aria-hidden="true"
                />
                Bon retour
            </h2>
            <p style={{ margin: 0, fontSize: 14 }}>Session ouverte — {user.name}</p>
            <Link href={route('dashboard')} className="dc-btn">
                Ouvrir le tableau de bord
                <ArrowRight size={16} strokeWidth={2.5} aria-hidden="true" />
            </Link>
        </div>
    );
}

const STATUS_STEPS = [
    { label: 'Reçu', done: true },
    { label: 'Diag.', done: true },
    { label: 'En cours', done: true },
    { label: 'Prêt', current: true },
    { label: 'Livré' },
];

function StatusTrack() {
    return (
        <div
            className="dc-snippet"
            style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(5, minmax(0, 1fr))',
                gap: 6,
                fontSize: 12,
                alignContent: 'center',
            }}
        >
            {STATUS_STEPS.map(({ label, done, current }) => (
                <span
                    key={label}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 6,
                        minWidth: 0,
                        color: current ? 'var(--dc-paper)' : done ? undefined : 'var(--dc-mute)',
                        fontWeight: current ? 600 : undefined,
                    }}
                >
                    <span
                        aria-hidden="true"
                        style={{
                            height: 4,
                            borderRadius: 2,
                            background: current
                                ? 'var(--dc-amber)'
                                : done
                                  ? 'var(--dc-green)'
                                  : 'var(--dc-line)',
                        }}
                    />
                    {label}
                </span>
            ))}
        </div>
    );
}

// One car's day. Sample data (plate, amounts) is illustrative only.
const STAGES: {
    time: string;
    label: string;
    title: string;
    text: string;
    final?: boolean;
    snippet: ReactNode;
}[] = [
    {
        time: '08:12',
        label: '1.0 · Accueil',
        title: 'La Logan arrive. On tape la plaque.',
        text: 'Client et véhicule sont retrouvés en quelques chiffres. Nouveau client ? La fiche se crée en quelques champs.',
        snippet: (
            <div
                className="dc-snippet"
                style={{ display: 'flex', flexDirection: 'column', gap: 12 }}
            >
                <div
                    style={{
                        border: '1.5px solid var(--dc-amber)',
                        borderRadius: 4,
                        padding: '10px 12px',
                        fontFamily: 'var(--dc-mono)',
                        color: 'var(--dc-paper)',
                    }}
                >
                    48213
                </div>
                <div className="dc-row">
                    <span style={{ color: 'var(--dc-paper)', fontWeight: 500 }}>
                        {/* <bdi> keeps the plate's Arabic letter from reordering it. */}
                        48213-<bdi>أ</bdi>-6 · Dacia Logan
                    </span>
                    <span style={{ color: 'var(--dc-mute)' }}>Karim Bennani</span>
                </div>
            </div>
        ),
    },
    {
        time: '08:40',
        label: '2.0 · Devis',
        title: 'Le devis part avant le café.',
        text: 'Opérations et pièces tirées du catalogue, TVA calculée. Le client accepte, le devis devient bon de travail.',
        snippet: (
            <div
                className="dc-snippet"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 8,
                    fontVariantNumeric: 'tabular-nums',
                }}
            >
                <div className="dc-row">
                    <span>Vidange + filtre</span>
                    <span>445,00</span>
                </div>
                <div className="dc-row">
                    <span>Plaquettes AV</span>
                    <span>420,00</span>
                </div>
                <div className="dc-row">
                    <span>Main-d&apos;œuvre 1,5 h</span>
                    <span>300,00</span>
                </div>
                <div
                    className="dc-row"
                    style={{
                        alignItems: 'center',
                        borderTop: '1px solid var(--dc-line)',
                        paddingTop: 10,
                        marginTop: 4,
                    }}
                >
                    <span style={{ color: 'var(--dc-paper)', fontWeight: 600 }}>
                        1 398,00 DH TTC
                    </span>
                    <span
                        style={{
                            fontSize: 12,
                            fontWeight: 600,
                            color: 'var(--dc-green-lite)',
                            border: '1px solid var(--dc-green)',
                            borderRadius: 999,
                            padding: '2px 10px',
                        }}
                    >
                        Accepté
                    </span>
                </div>
            </div>
        ),
    },
    {
        time: '09:15',
        label: '3.0 · Atelier',
        title: 'Le mécanicien suit le bon.',
        text: 'Pièces et temps s’ajoutent au fil du travail. Chaque pièce posée sort du stock — et le stock ne passe jamais sous zéro.',
        snippet: (
            <div
                className="dc-snippet"
                style={{ display: 'flex', flexDirection: 'column', gap: 12 }}
            >
                <div className="dc-row">
                    <span style={{ color: 'var(--dc-paper)' }}>Plaquettes AV</span>
                    <span style={{ fontFamily: 'var(--dc-mono)' }}>stock 6 → 5</span>
                </div>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 10,
                        background: 'var(--dc-ink)',
                        border: '1px solid var(--dc-amber)',
                        borderRadius: 4,
                        padding: '10px 12px',
                    }}
                >
                    <span
                        aria-hidden="true"
                        style={{
                            width: 8,
                            height: 8,
                            borderRadius: '50%',
                            background: 'var(--dc-amber)',
                            flex: 'none',
                        }}
                    />
                    Seuil bas : filtre à huile (2 restants)
                </div>
            </div>
        ),
    },
    {
        time: '16:50',
        label: '4.0 · Prêt',
        title: 'La voiture est prête.',
        text: 'Le bon passe à « Prêt ». Toute l’équipe le voit sur le tableau de bord, l’accueil sait quoi dire au client.',
        snippet: <StatusTrack />,
    },
    {
        time: '17:40',
        label: '5.0 · Facture',
        title: 'Clé rendue, facture remise.',
        text: 'La facture naît du bon : numérotée, avec ICE, en PDF. L’intervention rejoint l’historique du véhicule.',
        final: true,
        snippet: (
            <div
                className="dc-snippet"
                style={{
                    background: 'var(--dc-paper)',
                    borderColor: 'var(--dc-paper)',
                    color: 'var(--dc-ink-2)',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 6,
                }}
            >
                <span
                    style={{ fontFamily: 'var(--dc-mono)', fontSize: 13, color: 'var(--dc-grey)' }}
                >
                    FAC-2026-0212 · 01/11/2026
                </span>
                <span
                    style={{
                        fontFamily: 'var(--dc-display)',
                        fontWeight: 600,
                        fontSize: 32,
                        color: 'var(--dc-ink)',
                    }}
                >
                    1 398,00 DH
                </span>
                <span style={{ fontSize: 13, color: 'var(--dc-grey)' }}>
                    TTC · TVA 20 % incluse
                </span>
            </div>
        ),
    },
];

const MODULES = [
    ['Parc véhicules', 'Historique et kilométrage par plaque'],
    ['Rendez-vous', 'Planning, confirmation au client'],
    ['Stock & pièces', 'Seuils et alertes de réassort'],
    ['Rappels', 'Prochaine vidange, prochain contrôle'],
    ['Notes flotte', 'Suivi daté des véhicules de société'],
    ['Tableau de bord', 'Chiffre d’affaires et charge du jour'],
];

export default function Welcome({ auth }: WelcomeProps) {
    const brand = usePage().props.appSettings.brand_name;

    return (
        <>
            <Head title="De la clé reçue à la clé rendue" />
            <style>{CSS}</style>
            <div className="dc-landing">
                {/* NAV · N6 masthead */}
                <header className="dc-wrap" style={{ paddingTop: 16 }}>
                    <div
                        className="dc-masthead-strap"
                        style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 12,
                            fontFamily: 'var(--dc-mono)',
                            fontSize: 12,
                            letterSpacing: '.04em',
                            color: 'var(--dc-mute)',
                        }}
                    >
                        <span>
                            Logiciel de gestion d&apos;atelier
                            <span className="dc-strap-sep"> · </span>
                            <span
                                lang="ar"
                                dir="rtl"
                                className="dc-strap-ar"
                                style={{ fontFamily: 'var(--dc-ar)' }}
                            >
                                نظام تسيير الورشة
                            </span>
                        </span>
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="dc-link"
                                style={{
                                    fontFamily: 'var(--dc-body)',
                                    fontWeight: 600,
                                    fontSize: 14,
                                    letterSpacing: 0,
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 6,
                                }}
                            >
                                <LayoutDashboard size={14} strokeWidth={2.5} aria-hidden="true" />
                                Tableau de bord
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={scrollToLogin}
                                className="dc-link"
                                style={{
                                    background: 'none',
                                    border: 0,
                                    padding: 0,
                                    cursor: 'pointer',
                                    fontFamily: 'var(--dc-body)',
                                    fontWeight: 600,
                                    fontSize: 14,
                                }}
                            >
                                Connexion →
                            </button>
                        )}
                    </div>
                    <div
                        className="dc-display"
                        style={{
                            textAlign: 'center',
                            padding: '24px 0 20px',
                            fontWeight: 700,
                            fontSize: 'clamp(2rem, 6vw, 4rem)',
                            letterSpacing: '.04em',
                            textTransform: 'uppercase',
                            lineHeight: 1,
                        }}
                    >
                        {brand}
                    </div>
                    <div
                        aria-hidden="true"
                        style={{
                            height: 3,
                            borderTop: '3px solid var(--dc-amber)',
                            borderBottom: '1px solid var(--dc-amber)',
                        }}
                    />
                </header>

                {/* HERO */}
                <section
                    className="dc-wrap"
                    style={{
                        paddingTop: 64,
                        paddingBottom: 80,
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: 48,
                        alignItems: 'center',
                    }}
                >
                    <div style={{ flex: '1 1 520px', minWidth: 0 }}>
                        <h1
                            className="dc-display"
                            style={{ fontSize: 'clamp(2.75rem, 6vw, 5rem)', lineHeight: 1 }}
                        >
                            De la clé reçue à la{' '}
                            <span style={{ color: 'var(--dc-amber)' }}>clé rendue.</span>
                        </h1>
                        <p
                            style={{
                                margin: '24px 0 0',
                                maxWidth: '32rem',
                                fontSize: 18,
                                lineHeight: 1.6,
                            }}
                        >
                            Suivez une voiture pendant une journée d&apos;atelier — et ce que{' '}
                            {brand} fait à chaque étape.
                        </p>
                    </div>
                    <div style={{ flex: '0 1 380px', minWidth: 0 }}>
                        {auth.user ? <DashboardCard user={auth.user} /> : <LoginCard />}
                    </div>
                </section>

                {/* TIMELINE */}
                <section className="dc-wrap" aria-label="Une journée d'atelier">
                    {STAGES.map(({ time, label, title, text, final, snippet }) => (
                        <article
                            key={time}
                            className="dc-stage"
                            style={final ? { borderBottom: '2px solid var(--dc-line)' } : undefined}
                        >
                            <div
                                style={{
                                    fontFamily: 'var(--dc-mono)',
                                    fontSize: 24,
                                    color: final ? 'var(--dc-green-lite)' : 'var(--dc-amber)',
                                    fontVariantNumeric: 'tabular-nums',
                                }}
                            >
                                {time}
                            </div>
                            <div
                                aria-hidden="true"
                                className="dc-stage-spine"
                                style={final ? { background: 'var(--dc-green)' } : undefined}
                            />
                            <div className="dc-stage-body">
                                <div style={{ flex: '1 1 360px', minWidth: 0 }}>
                                    <div
                                        style={{
                                            fontFamily: 'var(--dc-mono)',
                                            fontSize: 13,
                                            color: 'var(--dc-mute)',
                                        }}
                                    >
                                        {label}
                                    </div>
                                    <h3
                                        className="dc-display"
                                        style={{
                                            margin: '8px 0 12px',
                                            fontSize: 'clamp(1.75rem, 3vw, 2.5rem)',
                                            lineHeight: 1.05,
                                        }}
                                    >
                                        {title}
                                    </h3>
                                    <p style={{ margin: 0, lineHeight: 1.6, maxWidth: '30rem' }}>
                                        {text}
                                    </p>
                                </div>
                                {snippet}
                            </div>
                        </article>
                    ))}
                </section>

                {/* INDEX · the rest of the garage */}
                <section className="dc-wrap" style={{ paddingTop: 80 }}>
                    <h2
                        className="dc-display"
                        style={{ marginBottom: 24, fontSize: 'clamp(1.75rem, 3vw, 2.25rem)' }}
                    >
                        Et le reste de l&apos;atelier
                    </h2>
                    <div className="dc-modules">
                        {MODULES.map(([name, line]) => (
                            <div
                                key={name}
                                className="dc-row"
                                style={{ padding: '16px 0', borderTop: '1px solid var(--dc-line)' }}
                            >
                                <span
                                    style={{
                                        color: 'var(--dc-paper)',
                                        fontWeight: 600,
                                        whiteSpace: 'nowrap',
                                    }}
                                >
                                    {name}
                                </span>
                                <span style={{ textAlign: 'right' }}>{line}</span>
                            </div>
                        ))}
                    </div>
                </section>

                {/* FOOTER · Ft5 statement */}
                <footer className="dc-wrap" style={{ paddingTop: 96, paddingBottom: 40 }}>
                    <p
                        className="dc-display"
                        style={{
                            fontWeight: 700,
                            fontSize: 'clamp(2.5rem, 7vw, 5.5rem)',
                            lineHeight: 1,
                        }}
                    >
                        Réparer. Entretenir.{' '}
                        <span style={{ color: 'var(--dc-amber)' }}>En confiance.</span>
                    </p>
                    <p
                        lang="ar"
                        dir="rtl"
                        style={{
                            margin: '16px 0 0',
                            fontFamily: 'var(--dc-ar)',
                            fontWeight: 700,
                            fontSize: 'clamp(1.5rem, 3vw, 2.25rem)',
                            textAlign: 'left',
                        }}
                    >
                        إصلاح. صيانة. بثقة.
                    </p>
                    <div
                        style={{
                            marginTop: 48,
                            paddingTop: 20,
                            borderTop: '1px solid var(--dc-line)',
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: '8px 24px',
                            fontSize: 13,
                            color: 'var(--dc-mute)',
                        }}
                    >
                        <span
                            style={{
                                fontFamily: 'var(--dc-display)',
                                fontWeight: 600,
                                textTransform: 'uppercase',
                                color: 'var(--dc-soft)',
                            }}
                        >
                            {brand}
                        </span>
                        <span>Logiciel de gestion d&apos;atelier</span>
                        <span style={{ marginLeft: 'auto' }}>© {new Date().getFullYear()}</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
