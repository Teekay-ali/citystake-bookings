<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="mb-8">
            <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Forgot password?</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                Enter your email and we'll send you a link to reset your password.
            </p>
        </div>

        <div v-if="status" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
            <p class="text-sm text-green-700 dark:text-green-400">{{ status }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    :class="[
                        'w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 transition-all',
                        form.errors.email
                            ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                            : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                    ]"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-white dark:text-gray-900 font-medium rounded-xl transition-all"
            >
                {{ form.processing ? 'Sending…' : 'Send reset link' }}
            </button>
        </form>
    </GuestLayout>
</template>
