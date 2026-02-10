import { ref, watch, onMounted } from 'vue';

export function useDarkMode() {
    const isDark = ref(false);

    const initDarkMode = () => {
        // Check localStorage first, then system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            isDark.value = true;
            document.documentElement.classList.add('dark');
        } else {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
        }
    };

    const toggle = () => {
        isDark.value = !isDark.value;
    };

    watch(isDark, (value) => {
        if (value) {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        }
    });

    onMounted(() => {
        initDarkMode();
    });

    return {
        isDark,
        toggle
    };
}
