<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { X, MailWarning } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user;
const dismissed = ref(false);
const form = useForm({});

const resend = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <div
        v-if="user && !user.email_verified_at && !dismissed"
        class="fixed top-0 left-0 right-0 z-[60] bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-700"
    >
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-2.5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 text-sm text-amber-800 dark:text-amber-200">
                <MailWarning class="w-4 h-4 shrink-0" />
                <span>
                    Please verify your email address to secure your account.
                    <button
                        @click="resend"
                        :disabled="form.processing"
                        class="underline font-medium hover:no-underline ml-1 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Sending…' : 'Resend verification email' }}
                    </button>
                    <span
                        v-if="form.wasSuccessful"
                        class="ml-2 font-medium text-green-700 dark:text-green-400"
                    >
                        ✓ Sent!
                    </span>
                </span>
            </div>
            <button
                @click="dismissed = true"
                class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-100 shrink-0"
                aria-label="Dismiss"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
