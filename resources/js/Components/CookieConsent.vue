<script setup>
import { ref, onMounted } from 'vue';
import { Cookie, X, Check, Settings } from 'lucide-vue-next';

const showBanner = ref(false);
const showSettings = ref(false);

// Cookie preferences
const preferences = ref({
    necessary: true, // Always true, can't be disabled
    analytics: false,
    marketing: false,
});

onMounted(() => {
    // Check if user has already made a choice
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        // Show banner after a short delay for better UX
        setTimeout(() => {
            showBanner.value = true;
        }, 1000);
    } else {
        // Load saved preferences
        const saved = JSON.parse(consent);
        preferences.value = { ...preferences.value, ...saved };
    }
});

const acceptAll = () => {
    preferences.value = {
        necessary: true,
        analytics: true,
        marketing: true,
    };
    savePreferences();
};

const rejectAll = () => {
    preferences.value = {
        necessary: true,
        analytics: false,
        marketing: false,
    };
    savePreferences();
};

const saveCustomPreferences = () => {
    savePreferences();
};

const savePreferences = () => {
    localStorage.setItem('cookie_consent', JSON.stringify(preferences.value));
    localStorage.setItem('cookie_consent_date', new Date().toISOString());
    showBanner.value = false;
    showSettings.value = false;

    // Here you would typically initialize analytics/marketing scripts based on preferences
    if (preferences.value.analytics) {
        // Initialize analytics (e.g., Google Analytics)
        console.log('Analytics enabled');
    }
    if (preferences.value.marketing) {
        // Initialize marketing pixels (e.g., Facebook Pixel)
        console.log('Marketing enabled');
    }
};

const toggleSettings = () => {
    showSettings.value = !showSettings.value;
};
</script>

<template>
    <!-- Cookie Banner -->
    <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner"
            class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
        >
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <!-- Simple Banner (when settings not shown) -->
                    <div v-if="!showSettings" class="p-6 md:p-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Icon & Text -->
                            <div class="flex-1">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 flex items-center justify-center">
                                            <Cookie class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                            We value your privacy
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                            We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic.
                                            By clicking "Accept All", you consent to our use of cookies.
                                            <a href="/privacy" class="text-blue-600 dark:text-blue-400 hover:underline ml-1">
                                                Read our Privacy Policy
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <button
                                    @click="toggleSettings"
                                    class="px-6 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white font-medium rounded-xl transition-all flex items-center justify-center"
                                >
                                    <Settings class="w-4 h-4 mr-2" />
                                    Customize
                                </button>
                                <button
                                    @click="rejectAll"
                                    class="px-6 py-3 border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-xl transition-all"
                                >
                                    Reject All
                                </button>
                                <button
                                    @click="acceptAll"
                                    class="px-6 py-3 bg-gradient-to-r from-gray-900 to-gray-800 dark:from-white dark:to-gray-100 hover:from-gray-800 hover:to-gray-700 dark:hover:from-gray-100 dark:hover:to-white text-white dark:text-gray-900 font-medium rounded-xl transition-all flex items-center justify-center shadow-lg"
                                >
                                    <Check class="w-4 h-4 mr-2" />
                                    Accept All
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Panel -->
                    <div v-else class="p-6 md:p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Cookie Preferences
                            </h3>
                            <button
                                @click="toggleSettings"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                            >
                                <X class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                            </button>
                        </div>

                        <div class="space-y-6 mb-8">
                            <!-- Necessary Cookies (Always on) -->
                            <div class="flex items-start justify-between pb-6 border-b border-gray-200 dark:border-gray-800">
                                <div class="flex-1 pr-6">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                        Necessary Cookies
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        These cookies are essential for the website to function properly. They enable basic features like page navigation and access to secure areas.
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="px-3 py-1.5 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full border border-green-200 dark:border-green-800">
                                        Always Active
                                    </div>
                                </div>
                            </div>

                            <!-- Analytics Cookies -->
                            <div class="flex items-start justify-between pb-6 border-b border-gray-200 dark:border-gray-800">
                                <div class="flex-1 pr-6">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                        Analytics Cookies
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        These cookies help us understand how visitors interact with our website, providing insights to improve user experience.
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <button
                                        @click="preferences.analytics = !preferences.analytics"
                                        :class="[
                                            'relative inline-flex h-8 w-14 items-center rounded-full transition-colors',
                                            preferences.analytics
                                                ? 'bg-green-500'
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-6 w-6 transform rounded-full bg-white transition-transform',
                                                preferences.analytics ? 'translate-x-7' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Marketing Cookies -->
                            <div class="flex items-start justify-between">
                                <div class="flex-1 pr-6">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">
                                        Marketing Cookies
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        These cookies track your online activity to help advertisers deliver more relevant advertising or limit how many times you see an ad.
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <button
                                        @click="preferences.marketing = !preferences.marketing"
                                        :class="[
                                            'relative inline-flex h-8 w-14 items-center rounded-full transition-colors',
                                            preferences.marketing
                                                ? 'bg-green-500'
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-6 w-6 transform rounded-full bg-white transition-transform',
                                                preferences.marketing ? 'translate-x-7' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Actions -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                @click="rejectAll"
                                class="flex-1 px-6 py-3 border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-xl transition-all"
                            >
                                Reject All
                            </button>
                            <button
                                @click="acceptAll"
                                class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white font-medium rounded-xl transition-all"
                            >
                                Accept All
                            </button>
                            <button
                                @click="saveCustomPreferences"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-gray-900 to-gray-800 dark:from-white dark:to-gray-100 hover:from-gray-800 hover:to-gray-700 dark:hover:from-gray-100 dark:hover:to-white text-white dark:text-gray-900 font-medium rounded-xl transition-all shadow-lg"
                            >
                                Save Preferences
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
