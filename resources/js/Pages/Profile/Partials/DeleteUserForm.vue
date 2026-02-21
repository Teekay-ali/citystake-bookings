<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { AlertTriangle, Lock } from 'lucide-vue-next';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <div>
        <div class="mb-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
            </p>
        </div>

        <button
            @click="confirmUserDeletion"
            type="button"
            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-all flex items-center gap-2"
        >
            <AlertTriangle class="w-5 h-5" />
            Delete Account
        </button>

        <!-- Modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmingUserDeletion"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
                @click="closeModal"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/75 dark:bg-gray-950/90 backdrop-blur-sm"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="confirmingUserDeletion"
                        class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 p-6 md:p-8 max-w-lg w-full mx-4"
                        @click.stop
                    >
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                <AlertTriangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Delete Account
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.
                                </p>
                            </div>
                        </div>

                        <form @submit.prevent="deleteUser">
                            <div class="mb-6">
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <Lock class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        id="password"
                                        ref="passwordInput"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Enter your password"
                                        :class="[
                                            'w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-950 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.password
                                                ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    />
                                </div>
                                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="flex-1 px-6 py-3 border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-xl transition-all"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-xl transition-all disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Deleting...</span>
                                    <span v-else>Delete Account</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </div>
</template>
