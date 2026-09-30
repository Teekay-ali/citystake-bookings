<script setup>
import { Link } from '@inertiajs/vue3'
import { LogIn, LogOut, Building2 } from 'lucide-vue-next'

defineProps({
    availability:      Object,
    todayCheckins:     Array,
    todayCheckouts:    Array,
    currentlyOccupied: Array,
})
</script>

<template>
    <div>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Units</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ availability.total }}</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Available</p>
                <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">
                    {{ availability.total - availability.occupied }}
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-5">
                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Occupied</p>
                <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ availability.occupied }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <!-- Today's check-ins -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <LogIn class="w-4 h-4 text-emerald-500" />
                        Today's Check-ins ({{ todayCheckins?.length ?? 0 }})
                    </h2>
                    <Link :href="route('manage.availability.index')"
                          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        View board →
                    </Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="booking in todayCheckins" :key="booking.id"
                         class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Unit {{ booking.unit?.unit_number }} · {{ booking.unit_type?.name }}
                            </p>
                        </div>
                        <span :class="booking.status === 'checked_in'
                            ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400'
                            : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'"
                              class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                            {{ booking.status === 'checked_in' ? 'Checked In' : 'Pending' }}
                        </span>
                    </div>
                    <div v-if="!todayCheckins?.length"
                         class="px-5 py-6 text-center text-sm text-gray-400">
                        No check-ins today
                    </div>
                </div>
            </div>

            <!-- Today's check-outs -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <LogOut class="w-4 h-4 text-amber-500" />
                        Today's Check-outs ({{ todayCheckouts?.length ?? 0 }})
                    </h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="booking in todayCheckouts" :key="booking.id"
                         class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Unit {{ booking.unit?.unit_number }}
                            </p>
                        </div>
                        <Link :href="route('manage.bookings.show', booking.booking_reference)"
                              class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                            View →
                        </Link>
                    </div>
                    <div v-if="!todayCheckouts?.length"
                         class="px-5 py-6 text-center text-sm text-gray-400">
                        No check-outs today
                    </div>
                </div>
            </div>

            <!-- Currently Occupied -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden sm:col-span-2">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-blue-500" />
                        Currently Occupied
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">
                            {{ currentlyOccupied?.length ?? 0 }} unit{{ (currentlyOccupied?.length ?? 0) !== 1 ? 's' : '' }}
                        </span>
                    </h2>
                    <Link :href="route('manage.availability.index')"
                          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        View board →
                    </Link>
                </div>

                <!-- Empty state -->
                <div v-if="!currentlyOccupied?.length" class="px-5 py-6 text-center text-sm text-gray-400">
                    No units currently occupied
                </div>

                <!-- Table -->
                <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                    <Link
                        v-for="booking in currentlyOccupied"
                        :key="booking.id"
                        :href="route('manage.bookings.show', booking.booking_reference)"
                        class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">

                        <!-- Unit badge -->
                        <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-400">{{ booking.unit_number }}</span>
                        </div>

                        <!-- Guest info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ booking.unit_type }}
                                <span v-if="currentlyOccupied.length > 1 && booking.building"> · {{ booking.building }}</span>
                            </p>
                        </div>

                        <!-- Checkout date -->
                        <div class="text-right shrink-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white">
                                Out {{ new Date(booking.check_out).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ booking.guest_phone }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
