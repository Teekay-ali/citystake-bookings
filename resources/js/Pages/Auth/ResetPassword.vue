<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="mb-8">
            <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Set new password</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Choose a strong password for your account.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    class="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="new-password"
                    :class="[
                        'w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                        form.errors.password
                            ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                            : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                    ]"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    :class="[
                        'w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                        form.errors.password_confirmation
                            ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                            : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                    ]"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.password_confirmation }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3.5 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-white dark:text-gray-900 font-medium rounded-xl transition-all"
            >
                {{ form.processing ? 'Resetting…' : 'Reset password' }}
            </button>
        </form>
    </GuestLayout>
</template>
