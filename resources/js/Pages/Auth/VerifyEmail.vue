<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Mail } from 'lucide-vue-next';

const props = defineProps({ status: String });

const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verify Email" />

        <div class="mb-8">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-6">
                <Mail class="w-7 h-7 text-gray-500 dark:text-gray-400" />
            </div>
            <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Check your email</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                We sent a verification link to your email address. Click the link to activate your account.
            </p>
        </div>

        <div v-if="verificationLinkSent" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <p class="text-sm text-green-700 dark:text-green-400">A new verification link has been sent to your email address.</p>
        </div>

        <form @submit.prevent="submit">
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-white dark:text-gray-900 font-medium rounded-xl transition-all mb-4"
            >
                {{ form.processing ? 'Sending…' : 'Resend verification email' }}
            </button>
        </form>

        <div class="text-center">
            <Link :href="route('logout')" method="post" as="button" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                Sign out and use a different account
            </Link>
        </div>
    </GuestLayout>
</template>
