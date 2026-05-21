<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Lock } from 'lucide-vue-next';

const form = useForm({ password: '' });
const submit = () => form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
});
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-8">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-6">
                <Lock class="w-7 h-7 text-gray-500 dark:text-gray-400" />
            </div>
            <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Confirm your password</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                This is a secure area. Please confirm your password before continuing.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    autofocus
                    :class="[
                        'w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                        form.errors.password
                            ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                            : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                    ]"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-white dark:text-gray-900 font-medium rounded-xl transition-all"
            >
                {{ form.processing ? 'Confirming…' : 'Confirm password' }}
            </button>
        </form>
    </GuestLayout>
</template>
