<?php

return [
    'name'    => env('COMPANY_NAME', 'Direct Auto Care'),
    'address' => env('COMPANY_ADDRESS', 'Casablanca, Maroc'),
    'phone'   => env('COMPANY_PHONE', ''),
    'email'   => env('COMPANY_EMAIL', ''),
    'ice'     => env('COMPANY_ICE', ''),
    'if'      => env('COMPANY_IF', ''),
    'rc'      => env('COMPANY_RC', ''),
    'logo'    => env('COMPANY_LOGO', ''),

    /*
    |--------------------------------------------------------------------------
    | Per-client theme (white-label)
    |--------------------------------------------------------------------------
    | Set at provisioning time in each client's .env. Hex values; converted to
    | HSL and injected at runtime via GET /branding.css (App\Support\Branding),
    | so the single compiled front-end bundle themes itself per deployment.
    | Defaults are the Direct Auto Care design system (v1.0).
    |
    | NOTE: only these brand levers are overridden. The neutral SURFACE tokens
    | (--card, --popover, --border, --sidebar-background) keep their app.css
    | defaults, which are tuned for a light, warm-neutral background. A client
    | with a light palette near the DAC neutrals themes cleanly; a dark or
    | strongly-tinted THEME_BG would need those surface tokens themed too.
    */
    'theme' => [
        'primary'    => env('THEME_PRIMARY', '#F4A81C'),     // action — amber
        'bg'         => env('THEME_BG', '#F6F2EA'),          // paper background
        'text'       => env('THEME_TEXT', '#211E1A'),        // ink
        'text_muted' => env('THEME_TEXT_MUTED', '#6B655E'),  // warm grey
        'success'    => env('THEME_SUCCESS', '#2FA84F'),     // validation — green
        'warning'    => env('THEME_WARNING', '#F4A81C'),     // amber
        'error'      => env('THEME_ERROR', '#E5484D'),       // red
    ],
];
