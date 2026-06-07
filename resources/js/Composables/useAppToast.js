import { useToast } from 'vue-toastification'
import ToastContent from '@/Components/ToastContent.vue'

export function useAppToast() {
    const toast = useToast()

    const show = (type, title, description) => {
        toast[type]({ component: ToastContent, props: { title, description, type } })
    }

    return {
        success: (title, description) => show('success', title, description),
        error:   (title, description) => show('error',   title, description),
        warning: (title, description) => show('warning', title, description),
        info:    (title, description) => show('info',    title, description),
    }
}
