<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { Mail, Calendar, Newspaper, CheckCircle } from 'lucide-vue-next';

const user = usePage().props.auth.user;

const form = useForm({
    email_marketing: user.email_marketing,
    email_reminders: user.email_reminders,
    email_newsletters: user.email_newsletters,
});

const updatePreferences = () => {
    form.patch(route('profile.email-preferences.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <form @submit.prevent="updatePreferences" class="space-y-6">
        <!-- Info Notice -->
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Note:</strong> You will always receive important emails like booking confirmations, cancellations, and payment receipts. These preferences only apply to optional communications.
            </p>
        </div>

        <div class="space-y-4">
            <!-- Marketing Emails -->
            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Mail class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                Promotional Emails
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Receive promotional offers, special deals, and discounts
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.email_marketing"
                                type="checkbox"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Reminders -->
            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Calendar class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                Booking Reminders
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Helpful reminders about upcoming check-ins (recommended)
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.email_reminders"
                                type="checkbox"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Newsletters -->
            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Newspaper class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                Newsletters
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Monthly updates about new properties and features
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                v-model="form.email_newsletters"
                                type="checkbox"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle class="w-5 h-5" />
                <span>Preferences saved successfully</span>
            </div>
        </Transition>

        <!-- Submit Button -->
        <div class="flex items-center gap-4 pt-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="px-8 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-xl transition-all disabled:cursor-not-allowed"
            >
                <span v-if="form.processing">Saving...</span>
                <span v-else>Save Preferences</span>
            </button>
        </div>
    </form>
</template>
