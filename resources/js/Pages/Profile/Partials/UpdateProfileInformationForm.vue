<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { Mail, User, Phone, CheckCircle } from 'lucide-vue-next'

defineProps({
    mustVerifyEmail: Boolean,
    status:          String,
})

const user = usePage().props.auth.user

const form = useForm({
    name:  user.name,
    email: user.email,
    phone: user.phone ?? '',
})

const inputClass = (field) => [
    'w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-950 border rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
    form.errors[field]
        ? 'border-red-300 dark:border-red-700 focus:ring-red-500'
        : 'border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
]
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-6">

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <User class="h-5 w-5 text-gray-400" />
                </div>
                <input id="name" v-model="form.name" type="text" required autofocus
                       autocomplete="name" :class="inputClass('name')" />
            </div>
            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <Mail class="h-5 w-5 text-gray-400" />
                </div>
                <input id="email" v-model="form.email" type="email" required
                       autocomplete="username" :class="inputClass('email')" />
            </div>
            <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <Phone class="h-5 w-5 text-gray-400" />
                </div>
                <input id="phone" v-model="form.phone" type="tel"
                       autocomplete="tel" placeholder="+234 800 000 0000"
                       :class="inputClass('phone')" />
            </div>
            <p v-if="form.errors.phone" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
        </div>

        <!-- Email Verification Notice -->
        <div v-if="mustVerifyEmail && user.email_verified_at === null">
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-3">Your email address is unverified.</p>
                <Link :href="route('verification.send')" method="post" as="button"
                      class="text-sm text-yellow-900 dark:text-yellow-100 underline hover:no-underline">
                    Click here to re-send the verification email.
                </Link>
            </div>
            <div v-show="status === 'verification-link-sent'"
                 class="mt-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <p class="text-sm text-green-800 dark:text-green-200">A new verification link has been sent to your email address.</p>
            </div>
        </div>

        <!-- Success -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle class="w-5 h-5" />
                <span>Profile saved successfully.</span>
            </div>
        </Transition>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit" :disabled="form.processing"
                    class="px-8 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:opacity-50 text-white dark:text-gray-900 font-medium rounded-xl transition-all disabled:cursor-not-allowed">
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </form>
</template>
