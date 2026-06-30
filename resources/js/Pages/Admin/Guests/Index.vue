<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Users, Search, ChevronRight, UserCheck, UserX } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    guests:  Object,
    summary: Object,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
}) : '—'

function applyFilters() {
    router.get(route('manage.guests.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    status.value = ''
    applyFilters()
}
</script>

<template>
    <Head title="Guests" />

    <div class="p-6 lg:p-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Guests</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Registered guest accounts</p>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-3 gap-3">
            <div
                v-for="item in [
                    { label: 'Total Guests', value: summary.total },
                    { label: 'Active',       value: summary.active },
                    { label: 'Inactive',     value: summary.inactive },
                ]"
                :key="item.label"
                class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4"
            >
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ item.value }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3 flex-wrap">
            <div class="relative flex-1 max-w-xs">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search name, email, phone..."
                    class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                />
            </div>

            <select
                v-model="status"
                @change="applyFilters"
                class="px-3 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button
                v-if="search || status"
                @click="clearFilters"
                class="text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
            >Clear</button>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
            <div v-if="!guests.data.length" class="text-center py-16">
                <Users class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No guests found.</p>
            </div>

            <template v-else>
                <!-- Desktop -->
                <table class="hidden md:table w-full text-sm">
                    <thead class="border-b border-gray-100 dark:border-gray-800">
                    <tr class="text-left">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Guest</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Phone</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Bookings</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Total Spend</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Joined</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="guest in guests.data"
                        :key="guest.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ guest.name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ guest.email }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-xs">{{ guest.phone ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ guest.bookings_count }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ formatPrice(guest.bookings_sum_total_amount) }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ formatDate(guest.created_at) }}</td>
                        <td class="px-6 py-4">
                                <span :class="guest.is_active
                                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                                    : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'"
                                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border">
                                    {{ guest.is_active ? 'Active' : 'Inactive' }}
                                </span>
                        </td>
                        <td class="px-6 py-4">
                            <Link
                                :href="route('manage.guests.show', guest.id)"
                                class="p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Mobile -->
                <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                    <Link
                        v-for="guest in guests.data"
                        :key="guest.id"
                        :href="route('manage.guests.show', guest.id)"
                        class="flex items-start justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                    >
                        <div class="space-y-0.5 min-w-0 flex-1 pr-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ guest.name }}</p>
                            <p class="text-xs text-gray-500">{{ guest.email }}</p>
                            <p class="text-xs text-gray-400">{{ guest.bookings_count }} booking{{ guest.bookings_count !== 1 ? 's' : '' }} · {{ formatDate(guest.created_at) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(guest.bookings_sum_total_amount) }}</p>
                            <span :class="guest.is_active ? 'text-green-600' : 'text-red-500'" class="text-xs">
                                {{ guest.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </Link>
                </div>
            </template>

            <!-- Pagination -->
            <div v-if="guests.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <p class="text-sm text-gray-500">Showing {{ guests.from }}–{{ guests.to }} of {{ guests.total }}</p>
                <div class="flex gap-2">
                    <Link v-if="guests.prev_page_url" :href="guests.prev_page_url"
                          class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors">Previous</Link>
                    <Link v-if="guests.next_page_url" :href="guests.next_page_url"
                          class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors">Next</Link>
                </div>
            </div>
        </div>
    </div>
</template>
