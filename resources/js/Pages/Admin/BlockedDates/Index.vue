<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { Plus, Calendar, Trash2, Ban, CheckCircle, Clock } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    blockedDates: Object,
    buildings:    Array,
    filters:      Object,
})

const building = ref(props.filters.building || '')
const status   = ref(props.filters.status || '')

const showDeleteModal = ref(false)
const itemToDelete    = ref(null)
const isDeleting      = ref(false)

watch([building, status], () => {
    router.get(route('manage.blocked-dates.index'), {
        building: building.value,
        status:   status.value,
    }, { preserveState: true, replace: true })
})

const openDeleteModal = (blockedDate) => {
    itemToDelete.value   = blockedDate
    showDeleteModal.value = true
}

const deleteBlockedDate = () => {
    isDeleting.value = true
    router.delete(route('manage.blocked-dates.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value      = false
            showDeleteModal.value  = false
            itemToDelete.value     = null
        }
    })
}

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric'
})

const getStatusBadge = (blockedDate) => {
    const today = new Date().toISOString().split('T')[0]
    if (blockedDate.blocked_to < today) {
        return { icon: CheckCircle, text: 'Past',         cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' }
    } else if (blockedDate.blocked_from <= today && blockedDate.blocked_to >= today) {
        return { icon: Ban,         text: 'Active Block', cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' }
    } else {
        return { icon: Clock,       text: 'Upcoming',     cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' }
    }
}

const getDuration = (from, to) => {
    const diff = Math.abs(new Date(to) - new Date(from))
    return Math.ceil(diff / (1000 * 60 * 60 * 24)) + 1
}

const selectClass = "w-full pl-3 pr-8 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Blocked Dates" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Blocked Dates</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage maintenance periods and blocked dates</p>
            </div>
            <Link
                :href="route('manage.blocked-dates.create')"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                Block Dates
            </Link>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-6">
            <select v-model="building" :class="selectClass" style="width: auto">
                <option value="">All properties</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option value="upcoming">Upcoming</option>
                <option value="active">Active</option>
                <option value="past">Past</option>
            </select>

            <button
                v-if="building || status"
                @click="building = ''; status = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Blocked dates list ── -->
        <div class="space-y-2">
            <div
                v-for="block in blockedDates.data"
                :key="block.id"
                class="border border-gray-200 dark:border-gray-800 rounded-xl p-5 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">

                        <!-- Reason + status -->
                        <div class="flex items-center gap-2.5 mb-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ block.reason }}
                            </h3>
                            <span :class="[getStatusBadge(block).cls, 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0']">
                                <component :is="getStatusBadge(block).icon" class="w-3 h-3" />
                                {{ getStatusBadge(block).text }}
                            </span>
                        </div>

                        <!-- Unit info -->
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-0.5">
                            Unit {{ block.unit.unit_number }} · {{ block.unit.unit_type.name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">
                            {{ block.unit.unit_type.building.name }}
                        </p>

                        <!-- Dates + duration -->
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mb-3">
                            <span class="flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5" />
                                {{ formatDate(block.blocked_from) }} → {{ formatDate(block.blocked_to) }}
                            </span>
                            <span>·</span>
                            <span>{{ getDuration(block.blocked_from, block.blocked_to) }} day{{ getDuration(block.blocked_from, block.blocked_to) > 1 ? 's' : '' }}</span>
                        </div>

                        <!-- Notes -->
                        <div v-if="block.notes"
                             class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg mb-3">
                            {{ block.notes }}
                        </div>

                        <!-- Created by -->
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Created by {{ block.creator?.name ?? 'System' }} · {{ formatDate(block.created_at) }}
                        </p>
                    </div>

                    <!-- Delete action -->
                    <button
                        @click="openDeleteModal(block)"
                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all flex-shrink-0"
                        title="Remove block">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Empty state ── -->
        <div v-if="blockedDates.data.length === 0" class="text-center py-20">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Ban class="w-6 h-6 text-gray-400" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No blocked dates</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Block dates for maintenance or when units are unavailable.
            </p>
            <Link
                :href="route('manage.blocked-dates.create')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                <Plus class="w-3.5 h-3.5" />
                Block Your First Dates
            </Link>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="blockedDates.links.length > 3" class="flex justify-center items-center gap-1.5 mt-6">
            <component
                v-for="(link, index) in blockedDates.links"
                :key="index"
                :is="link.url ? 'button' : 'span'"
                @click="link.url && router.visit(link.url)"
                v-html="link.label"
                :disabled="!link.url"
                :class="[
                    'min-w-[36px] h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all px-3',
                    link.active
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                        : link.url
                        ? 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800'
                        : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                ]" />
        </div>

    </div>

    <!-- ── Modal ── -->
    <ConfirmationModal
        :show="showDeleteModal"
        :processing="isDeleting"
        title="Remove Blocked Dates?"
        message="Are you sure you want to remove this block? The unit will become available for bookings during this period."
        confirm-text="Yes, Remove Block"
        cancel-text="Cancel"
        variant="danger"
        @confirm="deleteBlockedDate"
        @close="showDeleteModal = false" />
</template>
