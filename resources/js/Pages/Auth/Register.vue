<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, User, ArrowRight, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Create your account" />

    <div class="min-h-screen bg-white dark:bg-gray-950 flex">
        <!-- Left Side - Form -->
        <div class="flex-1 flex flex-col justify-center px-6 py-12 lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-md">
                <!-- Logo -->
                <Link :href="route('home')" class="inline-block mb-8">
                    <span class="text-3xl font-light tracking-tight text-gray-900 dark:text-white">
                        CityStake
                    </span>
                </Link>

                <!-- Header -->
                <div class="mb-10">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                        Create account
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Start booking premium apartments today
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full name
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <User class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autocomplete="name"
                                autofocus
                                placeholder="John Doe"
                                :class="[
                                    'w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 transition-all',
                                    form.errors.name
                                        ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                ]"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Mail class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="you@example.com"
                                :class="[
                                    'w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 transition-all',
                                    form.errors.email
                                        ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                ]"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                :class="[
                                    'w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-900 border rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 transition-all',
                                    form.errors.password
                                        ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                ]"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-400" />
                            </div>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <EyeOff v-if="showPasswordConfirmation" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-gray-900 to-gray-800 dark:from-white dark:to-gray-100 hover:from-gray-800 hover:to-gray-700 dark:hover:from-gray-100 dark:hover:to-white text-white dark:text-gray-900 font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg group"
                    >
                        <span v-if="form.processing">Creating account...</span>
                        <span v-else class="flex items-center">
                            Create account
                            <ArrowRight class="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </form>

                <!-- Terms -->
                <p class="mt-6 text-xs text-center text-gray-500 dark:text-gray-400">
                    By creating an account, you agree to our
                    <Link href="/terms" class="text-gray-900 dark:text-white hover:underline">Terms & Conditions</Link>
                    and
                    <Link href="/privacy" class="text-gray-900 dark:text-white hover:underline">Privacy Policy</Link>
                </p>

                <!-- Sign In Link -->
                <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                    Already have an account?
                    <Link
                        :href="route('login')"
                        class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        Sign in
                    </Link>
                </p>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        ← Back to home
                    </Link>
                </div>
            </div>
        </div>

        <!-- Right Side - Hero Image (same as login) -->
        <div class="hidden lg:block relative flex-1">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 dark:from-gray-950 dark:to-gray-900">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')]"></div>
                </div>
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&h=1600&fit=crop')] bg-cover bg-center opacity-20"></div>
                <div class="relative h-full flex flex-col justify-center px-16 text-white">
                    <div class="max-w-lg">
                        <h2 class="text-4xl font-light tracking-tight mb-6">
                            Join CityStake<br />
                            <span class="text-gray-300">Today</span>
                        </h2>
                        <p class="text-lg text-gray-300 font-light mb-8">
                            Get exclusive access to premium properties and enjoy seamless booking experience.
                        </p>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">Instant booking confirmation</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">Manage bookings easily</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">Exclusive member benefits</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
