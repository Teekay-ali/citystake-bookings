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

            <!-- ─── Hero ───────────────────────────────────────────── -->
            <!-- Solid dark — distinct from the gradient hero on Home -->
            <div class="relative bg-gray-950 py-20 overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-400/60 to-transparent"></div>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-[500px] h-[200px] bg-amber-400/5 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
                    <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-4">We'd Love to Hear From You</p>
                    <h1 class="text-5xl md:text-6xl font-extralight tracking-tight text-white mb-5">
                        Get in Touch
                    </h1>
                    <p class="text-base text-gray-400 font-light max-w-xl leading-relaxed">
                        Have questions about our properties or need assistance with your booking?
                        We're here to help.
                    </p>
                </div>
            </div>

            <!-- ─── Contact Content ────────────────────────────────── -->
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                    <!-- ─── Left: Contact Info ──────────────────────── -->
                    <div class="lg:col-span-1 space-y-10">
                        <div>
                            <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-4">Reach Us</p>
                            <h2 class="text-2xl font-extralight tracking-tight text-gray-900 dark:text-white mb-8">
                                Contact Information
                            </h2>

                            <!-- Contact items — monochromatic icons, amber on hover -->
                            <div class="space-y-6">
                                <div class="flex items-start gap-4 group">
                                    <div class="w-10 h-10 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center flex-shrink-0 group-hover:bg-amber-400 transition-colors duration-300">
                                        <Mail class="w-4 h-4 text-white dark:text-gray-900" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1">Email</p>
                                        <a
                                            href="mailto:help@csbookings.ninetentech.net"
                                            class="text-sm text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                        >
                                            help@csbookings.ninetentech.net
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 group">
                                    <div class="w-10 h-10 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center flex-shrink-0 group-hover:bg-amber-400 transition-colors duration-300">
                                        <Phone class="w-4 h-4 text-white dark:text-gray-900" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1">Phone</p>
                                        <a
                                            href="tel:+2348012345678"
                                            class="text-sm text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                        >
                                            +234 801 234 5678
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 group">
                                    <div class="w-10 h-10 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center flex-shrink-0 group-hover:bg-amber-400 transition-colors duration-300">
                                        <MapPin class="w-4 h-4 text-white dark:text-gray-900" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1">Office</p>
                                        <p class="text-sm text-gray-900 dark:text-white leading-relaxed">
                                            Abuja, Federal Capital Territory<br />
                                            Nigeria
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-4 group">
                                    <div class="w-10 h-10 rounded-xl bg-gray-950 dark:bg-white flex items-center justify-center flex-shrink-0 group-hover:bg-amber-400 transition-colors duration-300">
                                        <Clock class="w-4 h-4 text-white dark:text-gray-900" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1">Hours</p>
                                        <p class="text-sm text-gray-900 dark:text-white leading-relaxed">
                                            Monday – Friday: 8am – 6pm<br />
                                            Saturday: 9am – 4pm<br />
                                            Sunday: Closed
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="pt-8 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-4">Quick Links</p>
                            <div class="space-y-3">
                                <a href="/" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group">
                                    <span class="w-1 h-1 rounded-full bg-amber-400 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                    Browse Properties
                                </a>
                                <a href="/about" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group">
                                    <span class="w-1 h-1 rounded-full bg-amber-400 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                    About Us
                                </a>
                                <a href="/my-bookings" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors group">
                                    <span class="w-1 h-1 rounded-full bg-amber-400 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                    My Bookings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Right: Contact Form ─────────────────────── -->
                    <div class="lg:col-span-2">
                        <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                            <!-- Gold accent top border — matches search card on Home -->
                            <div class="h-0.5 bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500"></div>

                            <div class="p-8">
                                <p class="text-xs font-medium text-amber-500 tracking-widest uppercase mb-3">Direct Message</p>
                                <h2 class="text-2xl font-extralight tracking-tight text-gray-900 dark:text-white mb-8">
                                    Send us a Message
                                </h2>

                                <form @submit.prevent="submit" class="space-y-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1.5">
                                                Your Name <span class="text-amber-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                required
                                                placeholder="John Doe"
                                                :class="[
                                                    'w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                    form.errors.name
                                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-400/40'
                                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-amber-400/40 focus:border-amber-400'
                                                ]"
                                            />
                                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                                                {{ form.errors.name }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1.5">
                                                Email Address <span class="text-amber-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.email"
                                                type="email"
                                                required
                                                placeholder="john@example.com"
                                                :class="[
                                                    'w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                    form.errors.email
                                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-400/40'
                                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-amber-400/40 focus:border-amber-400'
                                                ]"
                                            />
                                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                                                {{ form.errors.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1.5">
                                                Phone Number
                                            </label>
                                            <input
                                                v-model="form.phone"
                                                type="tel"
                                                placeholder="+234 800 000 0000"
                                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400 transition-all"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1.5">
                                                Subject <span class="text-amber-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.subject"
                                                type="text"
                                                required
                                                placeholder="How can we help?"
                                                :class="[
                                                    'w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                                    form.errors.subject
                                                        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-400/40'
                                                        : 'border border-gray-200 dark:border-gray-800 focus:ring-amber-400/40 focus:border-amber-400'
                                                ]"
                                            />
                                            <p v-if="form.errors.subject" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                                                {{ form.errors.subject }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 dark:text-gray-500 tracking-widest uppercase mb-1.5">
                                            Message <span class="text-amber-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.message"
                                            rows="6"
                                            required
                                            placeholder="Tell us more about your inquiry..."
                                            :class="[
                                                'w-full px-4 py-3 bg-gray-50 dark:bg-gray-950 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all resize-none',
                                                form.errors.message
                                                    ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-400/40'
                                                    : 'border border-gray-200 dark:border-gray-800 focus:ring-amber-400/40 focus:border-amber-400'
                                            ]"
                                        ></textarea>
                                        <p v-if="form.errors.message" class="mt-1.5 text-xs text-red-500 dark:text-red-400">
                                            {{ form.errors.message }}
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full px-8 py-3.5 bg-gray-950 dark:bg-amber-400 hover:bg-gray-800 dark:hover:bg-amber-300 disabled:bg-gray-200 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white dark:text-gray-900 text-sm font-medium rounded-xl transition-all"
                                    >
                                        <span v-if="form.processing">Sending…</span>
                                        <span v-else>Send Message</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
