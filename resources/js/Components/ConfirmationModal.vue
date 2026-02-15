<script setup>
import { ref, watch } from 'vue';
import { XCircle, AlertTriangle } from 'lucide-vue-next';

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
        default: 'danger', // 'danger' or 'warning'
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const isOpen = ref(props.show);

watch(() => props.show, (newValue) => {
    isOpen.value = newValue;
});

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
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
</script>

<template>
    <!-- Backdrop -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50"
            @click="handleClose"
        ></div>
    </Transition>

    <!-- Modal -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-md w-full p-8 border border-gray-200 dark:border-gray-800"
                @click.stop
            >
                <!-- Icon -->
                <div class="flex justify-center mb-6">
                    <div
                        :class="[
                            'w-16 h-16 rounded-full flex items-center justify-center',
                            variant === 'danger'
                                ? 'bg-red-50 dark:bg-red-900/20'
                                : 'bg-yellow-50 dark:bg-yellow-900/20'
                        ]"
                    >
                        <component
                            :is="variant === 'danger' ? XCircle : AlertTriangle"
                            :class="[
                                'w-8 h-8',
                                variant === 'danger'
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-yellow-600 dark:text-yellow-400'
                            ]"
                        />
                    </div>
                </div>

                <!-- Content -->
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-medium text-gray-900 dark:text-white mb-3">
                        {{ title }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        {{ message }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row gap-3">
                    <button
                        @click="handleCancel"
                        :disabled="processing"
                        class="flex-1 px-6 py-3 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-full transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        @click="handleConfirm"
                        :disabled="processing"
                        :class="[
                            'flex-1 px-6 py-3 font-medium rounded-full transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center',
                            variant === 'danger'
                                ? 'bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 text-white'
                                : 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white'
                        ]"
                    >
                        <span v-if="processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                        <span v-else>{{ confirmText }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
