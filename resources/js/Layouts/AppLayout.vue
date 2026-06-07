<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useDarkMode } from '@/Composables/useDarkMode';
import { usePage } from '@inertiajs/vue3';
import { useAppToast } from '@/Composables/useAppToast';
import { onClickOutside } from '@vueuse/core';
import { Sun, Moon, Menu, X } from 'lucide-vue-next';
import CookieConsent from '@/Components/CookieConsent.vue';
import EmailVerificationBanner from '@/Components/EmailVerificationBanner.vue';

const showMobileMenu = ref(false);
const showUserMenu = ref(false);
const userMenuRef = ref(null);
const isScrolled = ref(false);

const { isDark, toggle } = useDarkMode();

const page = usePage();
const toast = useAppToast();

const props = defineProps({
    hideFooter: {
        type: Boolean,
        default: false,
    },
});

function handleFlash(flash) {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
    if (flash?.info)    toast.info(flash.info)
    if (flash?.warning) toast.warning(flash.warning)
}

const onScroll = () => { isScrolled.value = window.scrollY > 10; };

onMounted(() => {
    handleFlash(page.props.flash);
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});

watch(() => page.props.flash, handleFlash, { deep: true });

onClickOutside(userMenuRef, () => showUserMenu.value = false);

// Cookie settings function
const openCookieSettings = () => {
    localStorage.removeItem('cookie_consent');
    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-gray-950">

        <EmailVerificationBanner />

        <!-- Navigation -->
        <nav class="fixed left-0 right-0 z-50 transition-all duration-300"
             :class="[
                 $page.props.auth?.user && !$page.props.auth.user.email_verified_at ? 'top-[40px]' : 'top-0',
                 isScrolled
                     ? 'bg-white/95 dark:bg-gray-950/95 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 shadow-sm'
                     : 'bg-white/80 dark:bg-gray-950/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-900'
             ]">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">

                    <!-- Logo -->
                    <Link :href="route('home')" class="flex items-center space-x-2">
                        <img src="/citystake-120.png" alt="CityStake Bookings" class="h-8 w-auto dark:invert" />
                        <span class="text-2xl font-light tracking-tight text-gray-900 dark:text-white">CityStake</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link
                            :href="route('properties.index')"
                            class="text-sm font-medium transition-colors"
                            :class="route().current('properties.*')
                                ? 'text-gray-900 dark:text-white font-semibold'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                        >
                            Properties
                        </Link>

                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggle"
                            class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                            aria-label="Toggle dark mode"
                        >
                            <Sun v-if="isDark" class="w-5 h-5" />
                            <Moon v-else class="w-5 h-5" />
                        </button>

                        <!-- Authenticated user -->
                        <template v-if="$page.props.auth.user">

                            <!-- Dashboard entry point for staff/admin -->
                            <Link
                                v-if="$page.props.auth.user.is_admin || $page.props.auth.user.is_staff"
                                :href="route('dashboard')"
                                class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-all"
                            >
                                Dashboard
                            </Link>

                            <!-- User dropdown -->
                            <div ref="userMenuRef" class="relative">
                                <button
                                    @click="showUserMenu = !showUserMenu"
                                    class="flex items-center space-x-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                                >
                                    <span class="w-8 h-8 rounded-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="showUserMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0 scale-95 -translate-y-1"
                                    enter-to-class="opacity-100 scale-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100 scale-100 translate-y-0"
                                    leave-to-class="opacity-0 scale-95 -translate-y-1"
                                >
                                    <div v-if="showUserMenu"
                                         class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg overflow-hidden">
                                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Signed in as</p>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $page.props.auth.user.name }}</p>
                                        </div>
                                        <Link
                                            :href="route('bookings.index')"
                                            @click="showUserMenu = false"
                                            class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                        >
                                            My Bookings
                                        </Link>
                                        <Link
                                            :href="route('profile.edit')"
                                            @click="showUserMenu = false"
                                            class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                        >
                                            Profile Settings
                                        </Link>
                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="w-full text-left flex items-center px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-t border-gray-100 dark:border-gray-800"
                                        >
                                            Sign out
                                        </Link>
                                    </div>
                                </Transition>
                            </div>
                        </template>

                        <!-- Guest -->
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                :href="route('register')"
                                class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-all"
                            >
                                Sign up
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile: Dark mode toggle + Hamburger -->
                    <div class="md:hidden flex items-center space-x-2">
                        <button
                            @click="toggle"
                            class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                            aria-label="Toggle dark mode"
                        >
                            <Sun v-if="isDark" class="w-5 h-5" />
                            <Moon v-else class="w-5 h-5" />
                        </button>
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                            :aria-label="showMobileMenu ? 'Close menu' : 'Open menu'"
                        >
                            <X v-if="showMobileMenu" class="w-5 h-5" />
                            <Menu v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu with animation -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showMobileMenu" class="md:hidden border-t border-gray-100 dark:border-gray-900 bg-white dark:bg-gray-950">
                    <div class="px-6 py-4 space-y-1">
                        <Link
                            :href="route('properties.index')"
                            @click="showMobileMenu = false"
                            class="block px-3 py-2.5 rounded-lg text-base font-medium transition-colors"
                            :class="route().current('properties.*')
                                ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900'"
                        >
                            Properties
                        </Link>

                        <template v-if="$page.props.auth.user">
                            <Link
                                v-if="$page.props.auth.user.is_admin || $page.props.auth.user.is_staff"
                                :href="route('dashboard')"
                                @click="showMobileMenu = false"
                                class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                Dashboard
                            </Link>
                            <Link
                                :href="route('bookings.index')"
                                @click="showMobileMenu = false"
                                class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                My Bookings
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                @click="showMobileMenu = false"
                                class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                Profile Settings
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="block w-full text-left px-3 py-2.5 rounded-lg text-base font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            >
                                Sign out
                            </Link>
                        </template>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                @click="showMobileMenu = false"
                                class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                :href="route('register')"
                                @click="showMobileMenu = false"
                                class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                            >
                                Sign up
                            </Link>
                        </template>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Page Content -->
        <main
            :class="$page.props.auth?.user && !$page.props.auth.user.email_verified_at
                ? 'pt-[120px]'
                : 'pt-16'
        ">
            <slot />
        </main>

        <!-- Footer -->
        <footer v-if="!hideFooter" class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto">
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
                                <Link href="/my-bookings" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
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
