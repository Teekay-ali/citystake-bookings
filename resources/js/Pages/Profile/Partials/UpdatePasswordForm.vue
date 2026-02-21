<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Lock, CheckCircle, Eye, EyeOff } from 'lucide-vue-next';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-6">
        <!-- Current Password -->
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Current Password
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <Lock class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    :class="[
                        'w-full pl-12 pr-12 py-3 bg-gray-50 dark:bg-gray-950 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                        form.errors.current_password
                            ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                            : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                    ]"
                />
                <button
                    type="button"
                    @click="showCurrentPassword = !showCurrentPassword"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <EyeOff v-if="showCurrentPassword" class="h-5 w-5" />
                    <Eye v-else class="h-5 w-5" />
                </button>
            </div>
            <p v-if="form.errors.current_password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ form.errors.current_password }}
            </p>
        </div>

        <!-- New Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                New Password
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <Lock class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    :class="[
                        'w-full pl-12 pr-12 py-3 bg-gray-50 dark:bg-gray-950 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
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
                Confirm New Password
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <Lock class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="w-full pl-12 pr-12 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
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
            <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ form.errors.password_confirmation }}
            </p>
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
                <span>Password updated successfully</span>
            </div>
        </Transition>

        <!-- Submit Button -->
        <div class="flex items-center gap-4 pt-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="px-8 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-xl transition-all disabled:cursor-not-allowed"
            >
                <span v-if="form.processing">Updating...</span>
                <span v-else>Update Password</span>
            </button>
        </div>
    </form>
</template>
