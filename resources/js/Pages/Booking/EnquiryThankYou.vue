<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { CheckCircle, Phone } from 'lucide-vue-next'
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Request Sent" />

        <div class="bg-white dark:bg-gray-950 min-h-screen flex items-center justify-center py-16 px-6">
            <div class="max-w-md w-full text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mx-auto mb-6">
                    <CheckCircle class="w-8 h-8 text-emerald-500" />
                </div>

                <h1 class="text-3xl font-light tracking-tight text-gray-900 dark:text-white mb-3">
                    Request received
                </h1>
                <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-8">
                    Thank you. Our team will reach out shortly to confirm availability
                    and arrange payment for your stay. Keep an eye on your phone and email.
                </p>

                <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-10">
                    <Phone class="w-4 h-4" />
                    Need it sooner? Call us and quote your name.
                </div>

                <Link
                    :href="route('properties.index')"
                    class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-all">
                    Browse more properties
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
