import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

type Theme = 'light' | 'dark';
type ThemePreference = 'light' | 'dark' | 'system';

export const useThemeStore = defineStore('theme', () => {
    // State
    const theme = ref<Theme>('light');
    const preference = ref<ThemePreference>('system');

    // Initialize
    function initTheme() {
        const storedPreference = localStorage.getItem('theme-preference') as ThemePreference | null;

        if (storedPreference) {
            preference.value = storedPreference;
        } else {
            preference.value = 'system';
        }

        applyTheme();

        // Listen for system changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (preference.value === 'system') {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    // Apply the current theme to the DOM
    function applyTheme() {
        let targetTheme: Theme;

        if (preference.value === 'system') {
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            targetTheme = systemDark ? 'dark' : 'light';
        } else {
            targetTheme = preference.value;
        }

        theme.value = targetTheme;

        const html = document.documentElement;
        if (targetTheme === 'dark') {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    }

    // Toggle function for the UI
    function toggleTheme() {
        // If currently system, or opposite of current, switch to the other
        // Simple logic: If currently dark (visually), switch to light preference.
        // If currently light (visually), switch to dark preference.

        const newPreference: ThemePreference = theme.value === 'dark' ? 'light' : 'dark';
        setPreference(newPreference);
    }

    function setPreference(newPreference: ThemePreference) {
        preference.value = newPreference;
        localStorage.setItem('theme-preference', newPreference);
        applyTheme();
    }

    // Internal helper to just set state (not preference)
    function setTheme(newTheme: Theme) {
        theme.value = newTheme;
        const html = document.documentElement;
        if (newTheme === 'dark') {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    }

    return {
        theme,
        preference,
        initTheme,
        toggleTheme,
        setPreference
    };
});
