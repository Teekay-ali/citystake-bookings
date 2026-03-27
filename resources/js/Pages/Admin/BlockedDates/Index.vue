<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import {
    Plus,
    Calendar,
    Trash2,
    Ban,
    CheckCircle,
    Clock
} from 'lucide-vue-next';

const props = defineProps({
    blockedDates: Object,
    buildings: Array,
    filters: Object,
});

const building = ref(props.filters.building || '');
const status = ref(props.filters.status || '');

const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

watch([building, status], () => {
    router.get(route('admin.blocked-dates.index'), {
        building: building.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
});

const openDeleteModal = (blockedDate) => {
    itemToDelete.value = blockedDate;
    showDeleteModal.value = true;
};

const deleteBlockedDate = () => {
    isDeleting.value = true;
    router.delete(route('admin.blocked-dates.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusBadge = (blockedDate) => {
    const today = new Date().toISOString().split('T')[0];

    if (blockedDate.blocked_to < today) {
        return {
            icon: CheckCircle,
            text: 'Past',
            class: 'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border border-gray-200 dark:border-gray-800'
        };
    } else if (blockedDate.blocked_from <= today && blockedDate.blocked_to >= today) {
        return {
            icon: Ban,
            text: 'Active Block',
            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
        };
    } else {
        return {
            icon: Clock,
            text: 'Upcoming',
            class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800'
        };
    }
};

const getDuration = (from, to) => {
    const start = new Date(from);
    const end = new Date(to);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays;
};

const clearFilters = () => {
    building.value = '';
    status.value = '';
};
</script>

<template>
    <AppLayout>
        <Head title="Blocked Dates - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            Blocked Dates
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Manage maintenance periods and blocked dates
                        </p>
                    </div>

                    <Link
                        :href="route('admin.blocked-dates.create')"
                        class="px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all flex items-center"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Block Dates
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Property
                            </label>
                            <select
                                v-model="building"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option value="">All Properties</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">
                                    {{ b.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select
                                v-model="status"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                            >
                                <option value="">All Status</option>
                                <option value="upcoming">Upcoming</option>
                                <option value="active">Active</option>
                                <option value="past">Past</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                            >
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Blocked Dates List -->
                <div class="space-y-4">
                    <div
                        v-for="block in blockedDates.data"
                        :key="block.id"
                        class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <!-- Header -->
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ block.reason }}
                                    </h3>
                                    <span :class="getStatusBadge(block).class" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                                        <component :is="getStatusBadge(block).icon" class="w-3 h-3 mr-1" />
                                        {{ getStatusBadge(block).text }}
                                    </span>
                                </div>

                                <!-- Unit Info -->
                                <div class="mb-3">
                                    <p class="text-sm text-gray-900 dark:text-white font-medium">
                                        Unit {{ block.unit.unit_number }} • {{ block.unit.unit_type.name }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ block.unit.unit_type.building.name }}
                                    </p>
                                </div>

                                <!-- Dates -->
                                <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    <div class="flex items-center">
                                        <Calendar class="w-4 h-4 mr-1.5" />
                                        <span>{{ formatDate(block.blocked_from) }} - {{ formatDate(block.blocked_to) }}</span>
                                    </div>
                                    <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                    <span>{{ getDuration(block.blocked_from, block.blocked_to) }} day{{ getDuration(block.blocked_from, block.blocked_to) > 1 ? 's' : '' }}</span>
                                </div>

                                <!-- Notes -->
                                <div v-if="block.notes" class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-3 rounded-xl">
                                    {{ block.notes }}
                                </div>

                                <!-- Created By -->
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-3">
                                    Created by {{ block.creator?.name ?? 'System' }} on {{ formatDate(block.created_at) }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <button
                                @click="openDeleteModal(block)"
                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                title="Remove block"
                            >
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="blockedDates.data.length === 0" class="text-center py-20">
                    <Ban class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                        No blocked dates
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Block dates for maintenance or when units are unavailable
                    </p>
                    <Link
                        :href="route('admin.blocked-dates.create')"
                        class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Block Your First Dates
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="blockedDates.links.length > 3" class="flex justify-center items-center space-x-2 mt-8">
                    <component
                        v-for="(link, index) in blockedDates.links"
                        :key="index"
                        :is="link.url ? 'button' : 'span'"
                        @click="link.url && router.visit(link.url)"
                        v-html="link.label"
                        :class="[
                            'min-w-[40px] h-10 flex items-center justify-center rounded-full text-sm font-medium transition-all',
                            link.active
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                : link.url
                                ? 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800'
                                : 'text-gray-400 cursor-not-allowed'
                        ]"
                        :disabled="!link.url"
                    />
                </div>

                <!-- Delete Confirmation Modal -->
                <ConfirmationModal
                    :show="showDeleteModal"
                    :processing="isDeleting"
                    title="Remove Blocked Dates?"
                    message="Are you sure you want to remove this block? The unit will become available for bookings during this period."
                    confirm-text="Yes, Remove Block"
                    cancel-text="Cancel"
                    variant="danger"
                    @confirm="deleteBlockedDate"
                    @close="showDeleteModal = false"
                />
            </div>
        </div>
    </AppLayout>
</template>
