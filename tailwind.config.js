/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.jsx",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                // Custom color palette untuk Office AI Agent
                'clean-light': '#F8F9FA',
                'card-white': '#FFFFFF',
                'accent': {
                    DEFAULT: '#0F172A',
                    50: '#F8FAFC',
                    100: '#F1F5F9',
                    900: '#0F172A',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'Courier New', 'monospace'],
            },
            spacing: {
                '248': '248px', // Fixed sidebar width
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },
    plugins: [],
}
