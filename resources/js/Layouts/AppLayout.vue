<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useDarkMode } from '@/Composables/useDarkMode';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import CookieConsent from '@/Components/CookieConsent.vue';

const showMobileMenu = ref(false);
const { isDark, toggle } = useDarkMode();

const page = usePage();
const toast = useToast();

// Show flash messages on mount
onMounted(() => {
    const flash = page.props.flash;

    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
    if (flash?.info) {
        toast.info(flash.info);
    }
    if (flash?.warning) {
        toast.warning(flash.warning);
    }
});

// Also watch for changes (for SPA navigation)
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.info) {
            toast.info(flash.info);
        }
        if (flash?.warning) {
            toast.warning(flash.warning);
        }
    },
    { deep: true }
);

// Cookie settings function
const openCookieSettings = () => {
    localStorage.removeItem('cookie_consent');
    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-gray-950">
        <!-- Navigation - Minimal & Clean -->
        <nav class="fixed top-0 left-0 right-0 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md z-50 border-b border-gray-100 dark:border-gray-900">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <Link :href="route('home')" class="flex items-center space-x-2">
                        <span class="text-2xl font-light tracking-tight text-gray-900 dark:text-white">CityStake</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link
                            :href="route('properties.index')"
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                        >
                            Properties
                        </Link>

                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggle"
                            class="p-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            aria-label="Toggle dark mode"
                        >
                            <!-- Sun Icon (shown in dark mode) -->
                            <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon Icon (shown in light mode) -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <div v-if="$page.props.auth.user" class="flex items-center space-x-6">
                            <Link
                                v-if="$page.props.auth.user.is_admin"
                                :href="route('admin.dashboard')"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Admin
                            </Link>
                            <Link
                                :href="route('bookings.index')"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                My Bookings
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                {{ $page.props.auth.user.name }}
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Sign out
                            </Link>
                        </div>

                        <div v-else class="flex items-center space-x-6">
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all"
                            >
                                Sign up
                            </Link>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="showMobileMenu = !showMobileMenu"
                        class="md:hidden p-2 text-gray-700 dark:text-gray-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="showMobileMenu" class="md:hidden border-t border-gray-100 dark:border-gray-900 bg-white dark:bg-gray-950">
                <div class="px-6 py-4 space-y-4">
                    <Link
                        :href="route('properties.index')"
                        class="block text-base font-medium text-gray-700 dark:text-gray-300"
                    >
                        Properties
                    </Link>

                    <!-- Dark Mode Toggle Mobile -->
                    <button
                        @click="toggle"
                        class="flex items-center space-x-2 text-base font-medium text-gray-700 dark:text-gray-300"
                    >
                        <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span>{{ isDark ? 'Light mode' : 'Dark mode' }}</span>
                    </button>

                    <template v-if="$page.props.auth.user">
                        <Link
                            v-if="$page.props.auth.user.is_admin"
                            :href="route('admin.dashboard')"
                            class="block text-base font-medium text-gray-700 dark:text-gray-300"
                        >
                            Admin
                        </Link>
                        <Link
                            :href="route('bookings.index')"
                            class="block text-base font-medium text-gray-700 dark:text-gray-300"
                        >
                            My Bookings
                        </Link>

                        <Link
                            :href="route('profile.edit')"
                            class="block text-base font-medium text-gray-700 dark:text-gray-300"
                        >
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block text-base font-medium text-gray-500 dark:text-gray-400"
                        >
                            Sign out
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="block text-base font-medium text-gray-700 dark:text-gray-300"
                        >
                            Sign in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="block text-base font-medium text-gray-700 dark:text-gray-300"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="pt-20">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $page.props.appName }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Premium apartment rentals in Abuja's finest locations.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Company</h4>
                        <ul class="space-y-2">
                            <li>
                                <Link href="/about" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    About Us
                                </Link>
                            </li>
                            <li>
                                <Link href="/contact" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Contact
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Support</h4>
                        <ul class="space-y-2">
                            <li>
                                <Link href="/bookings" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    My Bookings
                                </Link>
                            </li>
                            <li>
                                <Link href="/contact" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Help Center
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Legal</h4>
                        <ul class="space-y-2">
                            <li>
                                <Link href="/terms" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Terms & Conditions
                                </Link>
                            </li>
                            <li>
                                <Link href="/privacy" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <button
                                    @click="openCookieSettings"
                                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors text-left"
                                >
                                    Cookie Settings
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        © {{ new Date().getFullYear() }} {{ $page.props.appName }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Cookie Consent Banner -->
        <CookieConsent />
    </div>
</template>
