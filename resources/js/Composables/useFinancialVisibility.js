import { ref, watch } from 'vue';

const STORAGE_KEY = 'cs_financials_visible';

const financialsVisible = ref(
    localStorage.getItem(STORAGE_KEY) !== 'false'
);

watch(financialsVisible, (val) => {
    localStorage.setItem(STORAGE_KEY, val);
});

export function useFinancialVisibility() {
    const toggle = () => {
        financialsVisible.value = !financialsVisible.value;
    };

    return { financialsVisible, toggle };
}
