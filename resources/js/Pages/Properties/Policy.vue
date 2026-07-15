<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ShieldCheck, ArrowLeft, FileText } from 'lucide-vue-next'

const props = defineProps({
    building: Object,
    policy:   Object,
})

const updated = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : ''
</script>

<template>
    <AppLayout :hide-footer="false">
        <Head :title="`Property Policy - ${building.name}`" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 lg:py-14">
            <Link :href="route('properties.building', building.slug)"
                  class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors mb-6">
                <ArrowLeft class="w-4 h-4" /> Back to {{ building.name }}
            </Link>

            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-gray-900 dark:bg-white flex items-center justify-center shrink-0">
                    <ShieldCheck class="w-5 h-5 text-white dark:text-gray-900" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Property Policy</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ building.name }}<template v-if="building.city"> · {{ building.city }}</template></p>
                </div>
            </div>

            <div v-if="policy"
                 class="mt-6 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-6 sm:p-8">
                <div class="rt-content" v-html="policy.body"></div>
                <p class="mt-8 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-400 dark:text-gray-500">
                    Version {{ policy.version }} · Last updated {{ updated(policy.updated_at) }}
                </p>
            </div>

            <div v-else class="mt-6 text-center py-16 border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl">
                <FileText class="w-8 h-8 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No policy has been published for this property yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
