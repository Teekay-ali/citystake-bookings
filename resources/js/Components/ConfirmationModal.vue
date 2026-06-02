<script setup>
import { ref, watch } from 'vue';
import { AlertTriangle, XCircle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed?',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    variant: {
        type: String,
        default: 'danger', // 'danger' | 'warning' | 'default'
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const isOpen = ref(props.show);

watch(() => props.show, (v) => { isOpen.value = v; });

const handleConfirm = () => emit('confirm');

const handleCancel = () => {
    if (props.processing) return;
    isOpen.value = false;
    emit('cancel');
    emit('close');
};

const handleClose = () => {
    if (!props.processing) {
        isOpen.value = false;
        emit('close');
    }
};

const confirmCls = {
    danger:  'bg-red-600 hover:bg-red-700 text-white',
    warning: 'bg-amber-500 hover:bg-amber-600 text-white',
    default: 'bg-gray-900 dark:bg-white hover:bg-gray-700 dark:hover:bg-gray-100 text-white dark:text-gray-900',
};

const iconCls = {
    danger:  'text-red-500',
    warning: 'text-amber-500',
    default: 'text-gray-500',
};

const iconBgCls = {
    danger:  'bg-red-50 dark:bg-red-900/20',
    warning: 'bg-amber-50 dark:bg-amber-900/20',
    default: 'bg-gray-100 dark:bg-gray-800',
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="handleClose" />

            <!-- Panel -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="isOpen"
                     class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4"
                     @click.stop>

                    <!-- Icon + Title -->
                    <div class="flex items-start gap-3">
                        <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5', iconBgCls[variant] ?? iconBgCls.default]">
                            <component
                                :is="variant === 'danger' ? XCircle : AlertTriangle"
                                :class="['w-4 h-4', iconCls[variant] ?? iconCls.default]"
                            />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug">{{ title }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ message }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-1">
                        <button
                            @click="handleCancel"
                            :disabled="processing"
                            class="flex-1 py-2.5 text-xs font-medium border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 transition-all"
                        >
                            {{ cancelText }}
                        </button>
                        <button
                            @click="handleConfirm"
                            :disabled="processing"
                            :class="['flex-1 py-2.5 text-xs font-semibold rounded-lg disabled:opacity-50 transition-all flex items-center justify-center gap-2', confirmCls[variant] ?? confirmCls.default]"
                        >
                            <svg v-if="processing" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ processing ? 'Processing…' : confirmText }}
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
