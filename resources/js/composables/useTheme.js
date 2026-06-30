import { ref } from 'vue';

const isDark = ref(true);

export default function useTheme() {
    const applyTheme = (dark, vuetifyTheme = null) => {
        if (dark) {
            document.documentElement.classList.remove('light-mode');
        } else {
            document.documentElement.classList.add('light-mode');
        }
        if (vuetifyTheme) {
            vuetifyTheme.global.name.value = dark ? 'dark' : 'light';
        }
    };

    const initTheme = (vuetifyTheme = null) => {
        const saved = localStorage.getItem('timesys-theme');
        isDark.value = saved !== 'light';
        applyTheme(isDark.value, vuetifyTheme);
    };

    const toggleTheme = (vuetifyTheme = null) => {
        isDark.value = !isDark.value;
        localStorage.setItem('timesys-theme', isDark.value ? 'dark' : 'light');
        applyTheme(isDark.value, vuetifyTheme);
    };

    return { isDark, initTheme, toggleTheme };
}
