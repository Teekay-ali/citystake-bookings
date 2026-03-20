<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Phone, MapPin, Clock } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('contact.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Contact Us" />

        <div class="bg-white dark:bg-gray-950 min-h-screen">
            <!-- Hero Section -->
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 text-white py-20">
                <div class="max-w-7xl mx-auto px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <h1 class="text-5xl font-light tracking-tight mb-6">
                            Get in Touch
                        </h1>
                        <p class="text-xl text-gray-300 leading-relaxed">
                            Have questions about our properties or need assistance with your booking?
                            We're here to help.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact Content -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    <!-- Contact Information -->
                    <div class="lg:col-span-1 space-y-8">
                        <div>
                            <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-8">
                                Contact Information
                            </h2>

                            <div class="space-y-6">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <Mail class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</p>
                                        <a href="mailto:help@csbookings.ninetentech.net" class="text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                            help@csbookings.ninetentech.net
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <Phone class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Phone</p>
                                        <a href="tel:+2348012345678" class="text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                            +234 801 234 5678
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <MapPin class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Office</p>
                                        <p class="text-gray-900 dark:text-white">
                                            Abuja, Federal Capital Territory<br>
                                            Nigeria
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <Clock class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Hours</p>
                                        <p class="text-gray-900 dark:text-white">
                                            Monday - Friday: 8am - 6pm<br>
                                            Saturday: 9am - 4pm<br>
                                            Sunday: Closed
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="pt-8 border-t border-gray-200 dark:border-gray-800">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Quick Links
                            </h3>
                            <div class="space-y-3">
                                <a href="/" class="block text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Browse Properties
                                </a>
                                <a href="/about" class="block text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    About Us
                                </a>
                                <a href="/my-bookings" class="block text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    My Bookings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-8">
                            <h2 class="text-2xl font-light tracking-tight text-gray-900 dark:text-white mb-8">
                                Send us a Message
                            </h2>

                            <form @submit.prevent="submit" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Your Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            required
                                            placeholder="John Doe"
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.name
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="john@example.com"
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.email
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Phone Number
                                        </label>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            placeholder="+234 800 000 0000"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Subject <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.subject"
                                            type="text"
                                            required
                                            placeholder="How can we help?"
                                            :class="[
                                                'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                form.errors.subject
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                            ]"
                                        />
                                        <p v-if="form.errors.subject" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.subject }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Message <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.message"
                                        rows="6"
                                        required
                                        placeholder="Tell us more about your inquiry..."
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all resize-none',
                                            form.errors.message
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    ></textarea>
                                    <p v-if="form.errors.message" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.message }}
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full px-8 py-4 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-full transition-all disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Sending...</span>
                                    <span v-else>Send Message</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
