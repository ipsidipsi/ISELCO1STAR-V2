/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                // Primary colors from design
                'navy': {
                    DEFAULT: '#2D3748',
                    50: '#F7FAFC',
                    100: '#EDF2F7',
                    200: '#E2E8F0',
                    300: '#CBD5E0',
                    400: '#A0AEC0',
                    500: '#718096',
                    600: '#4A5568',
                    700: '#2D3748',
                    800: '#1A202C',
                    900: '#171923',
                },
                'teal': {
                    DEFAULT: '#14B8A6',
                    50: '#F0FDFA',
                    100: '#CCFBF1',
                    200: '#99F6E4',
                    300: '#5EEAD4',
                    400: '#2DD4BF',
                    500: '#14B8A6',
                    600: '#0D9488',
                    700: '#0F766E',
                    800: '#115E59',
                    900: '#134E4A',
                },
                'sidebar-bg': '#2D3748',
                'sidebar-active': '#3B82F6',
            },
        },
    },
    plugins: [],
}
