import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
    ],
    theme: {
    	extend: {
    		fontFamily: {
    			heading: [
    				'Montserrat',
    				'sans-serif'
    			],
    			sans: [
    				'Hanken Grotesk',
                    ...defaultTheme.fontFamily.sans
                ],
    			mono: [
    				'JetBrains Mono',
                    ...defaultTheme.fontFamily.mono
                ]
    		},
    		fontSize: {
    			'display-lg': [
    				'48px',
    				{
    					lineHeight: '56px',
    					letterSpacing: '-0.02em',
    					fontWeight: '700'
    				}
    			],
    			'headline-lg': [
    				'32px',
    				{
    					lineHeight: '40px',
    					fontWeight: '600'
    				}
    			],
    			'headline-md': [
    				'24px',
    				{
    					lineHeight: '32px',
    					fontWeight: '600'
    				}
    			],
    			'body-lg': [
    				'18px',
    				{
    					lineHeight: '28px'
    				}
    			],
    			'body-md': [
    				'16px',
    				{
    					lineHeight: '24px'
    				}
    			],
    			'label-mono': [
    				'14px',
    				{
    					lineHeight: '20px',
    					letterSpacing: '0.05em',
    					fontWeight: '500'
    				}
    			],
    			'label-sm': [
    				'12px',
    				{
    					lineHeight: '16px',
    					fontWeight: '600'
    				}
    			]
    		},
    		colors: {
    			border: 'hsl(var(--border))',
    			input: 'hsl(var(--input))',
    			ring: 'hsl(var(--ring))',
    			background: 'hsl(var(--background))',
    			foreground: 'hsl(var(--foreground))',
    			primary: {
    				DEFAULT: 'hsl(var(--primary))',
    				foreground: 'hsl(var(--primary-foreground))'
    			},
    			secondary: {
    				DEFAULT: 'hsl(var(--secondary))',
    				foreground: 'hsl(var(--secondary-foreground))'
    			},
    			muted: {
    				DEFAULT: 'hsl(var(--muted))',
    				foreground: 'hsl(var(--muted-foreground))'
    			},
    			accent: {
    				DEFAULT: 'hsl(var(--accent))',
    				foreground: 'hsl(var(--accent-foreground))'
    			},
    			destructive: {
    				DEFAULT: 'hsl(var(--destructive))',
    				foreground: 'hsl(var(--destructive-foreground))'
    			},
    			card: {
    				DEFAULT: 'hsl(var(--card))',
    				foreground: 'hsl(var(--card-foreground))'
    			},
    			popover: {
    				DEFAULT: 'hsl(var(--popover))',
    				foreground: 'hsl(var(--popover-foreground))'
    			},
    			surface: {
    				DEFAULT: 'hsl(var(--surface))',
    				'container-low': 'hsl(var(--surface-container-low))',
    				container: 'hsl(var(--surface-container))',
    				'container-high': 'hsl(var(--surface-container-high))',
    				'container-highest': 'hsl(var(--surface-container-highest))'
    			},
    			'primary-container': {
    				DEFAULT: 'hsl(var(--primary-container))',
    				on: 'hsl(var(--on-primary-container))'
    			},
    			'secondary-container': 'hsl(var(--secondary-container))',
    			tertiary: {
    				DEFAULT: 'hsl(var(--tertiary))',
    				container: 'hsl(var(--tertiary-container))'
    			},
    			sidebar: {
    				DEFAULT: 'hsl(var(--sidebar-background))',
    				foreground: 'hsl(var(--sidebar-foreground))',
    				primary: 'hsl(var(--sidebar-primary))',
    				'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
    				accent: 'hsl(var(--sidebar-accent))',
    				'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
    				border: 'hsl(var(--sidebar-border))',
    				ring: 'hsl(var(--sidebar-ring))'
    			}
    		},
    		borderRadius: {
    			sm: '0.125rem',
    			DEFAULT: '0.25rem',
    			md: '0.375rem',
    			lg: '0.5rem',
    			xl: '0.75rem',
    			full: '9999px'
    		},
    		boxShadow: {
    			tech: '0px 4px 12px rgba(26, 54, 115, 0.08)'
    		},
    		keyframes: {
    			'accordion-down': {
    				from: {
    					height: '0'
    				},
    				to: {
    					height: 'var(--radix-accordion-content-height)'
    				}
    			},
    			'accordion-up': {
    				from: {
    					height: 'var(--radix-accordion-content-height)'
    				},
    				to: {
    					height: '0'
    				}
    			}
    		},
    		animation: {
    			'accordion-down': 'accordion-down 0.2s ease-out',
    			'accordion-up': 'accordion-up 0.2s ease-out'
    		}
    	}
    },
    plugins: [],
};
