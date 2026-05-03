<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, ArrowRight, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Sign in to your account" />

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
                        Welcome back
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Sign in to manage your bookings
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <p class="text-sm text-green-700 dark:text-green-400">
                        {{ status }}
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
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
                                autofocus
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
                                autocomplete="current-password"
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

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-gray-900 dark:focus:ring-white transition-colors"
                            />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                Remember me
                            </span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-gray-900 to-gray-800 dark:from-white dark:to-gray-100 hover:from-gray-800 hover:to-gray-700 dark:hover:from-gray-100 dark:hover:to-white text-white dark:text-gray-900 font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg group"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else class="flex items-center">
                            Sign in
                            <ArrowRight class="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </form>

                <!-- Social Login -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white dark:bg-gray-950 text-gray-500 dark:text-gray-400">
                                Or continue with
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a
                            :href="route('social.redirect', 'google')"
                            class="flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Google
                        </a>

                        <a
                            :href="route('social.redirect', 'facebook')"
                            class="flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                        >
                            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="#1877F2">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </a>
                    </div>
                </div>

                <!-- Sign Up Link -->
                <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account?
                    <Link
                        :href="route('register')"
                        class="font-medium text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        Create an account
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

        <!-- Right Side - Hero Image -->
        <div class="hidden lg:block relative flex-1 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 dark:from-gray-950 dark:to-gray-900">

                <!-- Ken Burns background image -->
                <div class="absolute inset-0 bg-[url('/images/hero-apartment.jpg')] bg-cover bg-center opacity-20 animate-kenburns"></div>

                <!-- Floating blobs -->
                <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-white/5 rounded-full blur-3xl animate-float-slow"></div>
                <div class="absolute bottom-1/3 right-1/4 w-96 h-96 bg-white/3 rounded-full blur-3xl animate-float-slower"></div>

                <!-- Grid pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')]"></div>
                </div>

                <!-- Content -->
                <div class="relative h-full flex flex-col justify-center px-16 text-white">
                    <div class="max-w-lg">
                        <h2 class="text-4xl font-light tracking-tight mb-6 animate-fade-up" style="animation-delay: 0.1s">
                            Premium Living<br />
                            <span class="text-gray-300">in Abuja's Finest</span>
                        </h2>
                        <p class="text-lg text-gray-300 font-light mb-8 animate-fade-up" style="animation-delay: 0.25s">
                            Access exclusive properties in Asokoro, Maitama, and Wuse. Book instantly with best price guarantee.
                        </p>

                        <!-- Features -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 animate-fade-up" style="animation-delay: 0.4s">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">Instant booking confirmation</span>
                            </div>
                            <div class="flex items-center gap-3 animate-fade-up" style="animation-delay: 0.55s">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">24/7 customer support</span>
                            </div>
                            <div class="flex items-center gap-3 animate-fade-up" style="animation-delay: 0.7s">
                                <div class="w-10 h-10 rounded-lg bg-white/10 backdrop-blur-sm flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-gray-200">Secure payment processing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes kenburns {
    0%   { transform: scale(1)    translateX(0)    translateY(0); }
    50%  { transform: scale(1.08) translateX(-1%)  translateY(-1%); }
    100% { transform: scale(1)    translateX(0)    translateY(0); }
}

@keyframes float-slow {
    0%, 100% { transform: translateY(0)    translateX(0); }
    50%       { transform: translateY(-30px) translateX(15px); }
}

@keyframes float-slower {
    0%, 100% { transform: translateY(0)    translateX(0); }
    50%       { transform: translateY(20px) translateX(-20px); }
}

@keyframes fade-up {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

.animate-kenburns {
    animation: kenburns 20s ease-in-out infinite;
}

.animate-float-slow {
    animation: float-slow 12s ease-in-out infinite;
}

.animate-float-slower {
    animation: float-slower 18s ease-in-out infinite;
}

.animate-fade-up {
    animation: fade-up 0.6s ease-out both;
}
</style>
