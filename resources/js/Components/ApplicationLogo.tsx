/**
 * Direct Auto Care brand mark — amber hexagon emblem with a wrench isotype.
 * Mirrors the design-system logo. Two-colour by design (it ignores
 * `fill-current`): the hexagon uses --primary and the wrench --primary-foreground
 * so the mark tracks the theme. Pass sizing via `className` (e.g. `h-20 w-20`).
 */
export default function ApplicationLogo(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg
            viewBox="0 0 100 100"
            xmlns="http://www.w3.org/2000/svg"
            role="img"
            aria-label="Direct Auto Care"
            {...props}
        >
            <polygon points="50,0 100,25 100,75 50,100 0,75 0,25" fill="hsl(var(--primary))" />
            <g
                transform="translate(27 27) scale(1.92)"
                fill="none"
                stroke="hsl(var(--primary-foreground))"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </g>
        </svg>
    );
}
