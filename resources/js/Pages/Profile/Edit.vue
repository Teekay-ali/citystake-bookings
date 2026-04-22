<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdateEmailPreferencesForm from './Partials/UpdateEmailPreferencesForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { User, Lock, AlertTriangle, Mail } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Profile Settings" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-5xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                        Profile Settings
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Manage your account information and preferences
                    </p>
                </div>

                <!-- Cards Container -->
                <div class="space-y-8">
                    <!-- Profile Information -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="p-6 md:p-8 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                    <User class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 class="text-2xl font-medium text-gray-900 dark:text-white">
                                    Profile Information
                                </h2>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Update your account's profile information and email address
                            </p>
                        </div>
                        <div class="p-6 md:p-8">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>
                    </div>

                    <!-- Update Password -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="p-6 md:p-8 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                                    <Lock class="w-5 h-5 text-green-600 dark:text-green-400" />
                                </div>
                                <h2 class="text-2xl font-medium text-gray-900 dark:text-white">
                                    Update Password
                                </h2>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Ensure your account is using a strong password to stay secure
                            </p>
                        </div>
                        <div class="p-6 md:p-8">
                            <UpdatePasswordForm />
                        </div>
                    </div>

                    <!-- Email Preferences -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="p-6 md:p-8 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center">
                                    <Mail class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                <h2 class="text-2xl font-medium text-gray-900 dark:text-white">
                                    Email Preferences
                                </h2>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Manage your email notification preferences
                            </p>
                        </div>
                        <div class="p-6 md:p-8">
                            <UpdateEmailPreferencesForm />
                        </div>
                    </div>

                    <!-- Delete Account -->
                    <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-900/50 rounded-2xl overflow-hidden">
                        <div class="p-6 md:p-8 border-b border-red-200 dark:border-red-900/50">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                                </div>
                                <h2 class="text-2xl font-medium text-gray-900 dark:text-white">
                                    Delete Account
                                </h2>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Permanently delete your account and all associated data
                            </p>
                        </div>
                        <div class="p-6 md:p-8">
                            <DeleteUserForm />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
