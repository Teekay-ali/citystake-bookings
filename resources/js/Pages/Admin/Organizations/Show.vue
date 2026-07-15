<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Briefcase, Phone, Mail, MapPin } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    organization: Object,
    totalSpend:   Number,
    bookingCount: Number,
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
    <Head :title="organization.name" />

    <div class="p-4 lg:p-6">
        <!-- Header (sticky) -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-6 flex items-center gap-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <Link :href="route('manage.organizations.index')" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate flex items-center gap-2">
                    <Briefcase class="w-4 h-4 text-indigo-500" /> {{ organization.name }}
                    <span v-if="!organization.is_active" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500">Inactive</span>
                </h1>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ bookingCount }} booking{{ bookingCount !== 1 ? 's' : '' }} · {{ fmt(totalSpend) }} total</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:items-start">
            <!-- Contact -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-3">Contact</p>
                    <div class="space-y-2.5 text-sm">
                        <p v-if="organization.contact_name" class="text-gray-900 dark:text-white">{{ organization.contact_name }}</p>
                        <a v-if="organization.contact_phone" :href="`tel:${organization.contact_phone}`" class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <Phone class="w-3.5 h-3.5 text-gray-400" /> {{ organization.contact_phone }}
                        </a>
                        <a v-if="organization.contact_email" :href="`mailto:${organization.contact_email}`" class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white break-all">
                            <Mail class="w-3.5 h-3.5 text-gray-400 shrink-0" /> {{ organization.contact_email }}
                        </a>
                        <p v-if="organization.address" class="flex items-start gap-2 text-gray-600 dark:text-gray-400">
                            <MapPin class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5" /> {{ organization.address }}
                        </p>
                        <p v-if="!organization.contact_name && !organization.contact_phone && !organization.contact_email" class="text-xs text-gray-400">No contact details.</p>
                    </div>
                    <p v-if="organization.notes" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ organization.notes }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Bookings</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ bookingCount }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total spend</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ fmt(totalSpend) }}</p>
                    </div>
                </div>
            </div>

            <!-- Bookings -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                        <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Bookings</h2>
                    </div>
                    <div v-if="organization.bookings.length === 0" class="px-5 py-12 text-center text-sm text-gray-400">No bookings yet.</div>
                    <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="b in organization.bookings" :key="b.id" :href="route('manage.bookings.show', b.booking_reference)"
                              class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ b.guest_name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ b.booking_reference }} · {{ b.building?.name }} · {{ fmtDate(b.check_in) }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ fmt(b.total_amount) }}</span>
                            <span :class="['text-[10px] font-medium px-1.5 py-0.5 rounded-md whitespace-nowrap', statusBadge[b.status] ?? statusBadge.completed]">{{ b.status }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
