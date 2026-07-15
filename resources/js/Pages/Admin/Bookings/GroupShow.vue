<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Layers, Briefcase, Building2, CalendarDays } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    group:  Object,
    total:  Number,
    nights: Number,
})

const fmt = (v) => '₦' + Number(v || 0).toLocaleString('en-NG')
const fmtDate = (d) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })

const statusBadge = {
    confirmed:  'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    checked_in: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
    completed:  'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    cancelled:  'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400',
}
</script>

<template>
    <Head :title="`Group ${group.reference}`" />

    <div class="p-4 lg:p-6">
        <!-- Header (sticky) -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-6 flex items-center gap-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <Link :href="route('manage.bookings.index')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate font-mono flex items-center gap-2">
                    <Layers class="w-4 h-4 text-gray-400" /> {{ group.reference }}
                </h1>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                    {{ group.bookings.length }} units · {{ nights }} night{{ nights !== 1 ? 's' : '' }} · {{ group.building?.name }}
                </p>
            </div>
        </div>

        <!-- Summary -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 mb-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1.5 text-sm">
                    <p class="text-gray-900 dark:text-white font-medium">{{ group.lead_name }}</p>
                    <p v-if="group.organization" class="inline-flex items-center gap-1 text-xs font-medium px-1.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400">
                        <Briefcase class="w-3 h-3" /> {{ group.organization.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-3 flex-wrap">
                        <span class="inline-flex items-center gap-1"><Building2 class="w-3 h-3" /> {{ group.building?.name }}</span>
                        <span class="inline-flex items-center gap-1"><CalendarDays class="w-3 h-3" /> {{ fmtDate(group.bookings[0]?.check_in) }} → {{ fmtDate(group.bookings[0]?.check_out) }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wide">Group total</p>
                    <p class="text-xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ fmt(total) }}</p>
                </div>
            </div>
        </div>

        <!-- Member bookings -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
            <Link v-for="b in group.bookings" :key="b.id" :href="route('manage.bookings.show', b.booking_reference)"
                  class="flex items-center gap-3 px-4 sm:px-5 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 shrink-0">
                    {{ b.unit?.unit_number }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ b.guest_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ b.unit_type?.name }} · {{ b.booking_reference }}</p>
                </div>
                <span class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ fmt(b.total_amount) }}</span>
                <span :class="['text-[10px] font-medium px-1.5 py-0.5 rounded-md whitespace-nowrap', statusBadge[b.status] ?? statusBadge.completed]">{{ b.status }}</span>
            </Link>
        </div>
    </div>
</template>
