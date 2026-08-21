import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'fade-in': 'fadeIn 0.4s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                slideUp: { '0%': { transform: 'translateY(16px)', opacity: 0 }, '100%': { transform: 'translateY(0)', opacity: 1 } },
            },
        },
    },

    plugins: [
        require('daisyui'),
    ],

    daisyui: {
        themes: [
            {
                mric: {
                    "primary": "#1E40AF",
                    "primary-content": "#ffffff",
                    "secondary": "#7C3AED",
                    "secondary-content": "#ffffff",
                    "accent": "#F59E0B",
                    "accent-content": "#ffffff",
                    "neutral": "#1E293B",
                    "neutral-content": "#ffffff",
                    "base-100": "#FFFFFF",
                    "base-200": "#F1F5F9",
                    "base-300": "#E2E8F0",
                    "base-content": "#1E293B",
                    "info": "#0EA5E9",
                    "info-content": "#ffffff",
                    "success": "#10B981",
                    "success-content": "#ffffff",
                    "warning": "#F59E0B",
                    "warning-content": "#ffffff",
                    "error": "#EF4444",
                    "error-content": "#ffffff",
                },
            },
        ],
        base: true,
        styled: true,
        utils: true,
        logs: false,
    },
};

