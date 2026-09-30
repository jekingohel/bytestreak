import { ref } from 'vue';

const KEY = 'bytestreak-theme';
const theme = ref(typeof document === 'undefined' ? 'dark' : document.documentElement.dataset.theme || 'dark');

export function useTheme() {
    const set = (value) => {
        theme.value = value;
        document.documentElement.dataset.theme = value;
        try {
            localStorage.setItem(KEY, value);
        } catch {
            // Private mode: the choice just lasts for this page view.
        }
    };

    return {
        theme,
        toggle: () => set(theme.value === 'dark' ? 'light' : 'dark'),
    };
}
