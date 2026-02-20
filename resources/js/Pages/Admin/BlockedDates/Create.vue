<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    ArrowLeft,
    Calendar,
    Ban,
    FileText,
    Home,
    Building2
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
});

const form = useForm({
    building_id: '',
    unit_id: '',
    blocked_from: '',
    blocked_to: '',
    reason: '',
    notes: '',
});

const selectedBuilding = computed(() => {
    return props.buildings.find(b => b.id == form.building_id);
});

const availableUnits = computed(() => {
    if (!selectedBuilding.value) return [];

    return selectedBuilding.value.units.map(unit => ({
        id: unit.id,
        label: `Unit ${unit.unit_number} - ${unit.unit_type.name} (${unit.floor} Floor)`,
        unitNumber: unit.unit_number,
        unitType: unit.unit_type.name,
        floor: unit.floor,
    }));
});

const calculateDuration = computed(() => {
    if (!form.blocked_from || !form.blocked_to) return 0;
    const start = new Date(form.blocked_from);
    const end = new Date(form.blocked_to);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays > 0 ? diffDays : 0;
});

// Reset unit when building changes
watch(() => form.building_id, () => {
    form.unit_id = '';
});

const commonReasons = [
    'Maintenance & Repairs',
    'Deep Cleaning',
    'Renovation',
    'Inspection',
    'Pest Control',
    'Owner Reserved',
    'Other',
];

const selectReason = (reason) => {
    form.reason = reason;
};

const submit = () => {
    form.post(route('admin.blocked-dates.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Block Dates - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link
                        :href="route('admin.blocked-dates.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to blocked dates
                    </Link>
                </div>

                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Block Dates
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Block a unit for maintenance, repairs, or other reasons
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Unit Selection -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Home class="w-5 h-5 mr-2" />
                            Select Unit
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Building -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Building <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.building_id"
                                    required
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.building_id
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                >
                                    <option value="">Select building</option>
                                    <option v-for="building in buildings" :key="building.id" :value="building.id">
                                        {{ building.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.building_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.building_id }}
                                </p>
                            </div>

                            <!-- Unit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Unit <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.unit_id"
                                    :disabled="!form.building_id"
                                    required
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.unit_id
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white',
                                        !form.building_id && 'opacity-50 cursor-not-allowed'
                                    ]"
                                >
                                    <option value="">Select unit</option>
                                    <option v-for="unit in availableUnits" :key="unit.id" :value="unit.id">
                                        {{ unit.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.unit_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.unit_id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Calendar class="w-5 h-5 mr-2" />
                            Block Period
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Block From <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.blocked_from"
                                    type="date"
                                    required
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.blocked_from
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                />
                                <p v-if="form.errors.blocked_from" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.blocked_from }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Block To <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.blocked_to"
                                    type="date"
                                    required
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.blocked_to
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                />
                                <p v-if="form.errors.blocked_to" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.blocked_to }}
                                </p>
                            </div>
                        </div>

                        <div v-if="calculateDuration > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <p class="text-sm text-red-900 dark:text-red-300 flex items-center">
                                <Ban class="w-4 h-4 mr-2" />
                                Unit will be blocked for {{ calculateDuration }} day{{ calculateDuration > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <FileText class="w-5 h-5 mr-2" />
                            Reason & Notes
                        </h2>

                        <div class="space-y-6">
                            <!-- Common Reasons -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Select Reason <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <button
                                        v-for="reason in commonReasons"
                                        :key="reason"
                                        type="button"
                                        @click="selectReason(reason)"
                                        :class="[
                                            'px-4 py-3 rounded-xl text-sm font-medium transition-all text-left',
                                            form.reason === reason
                                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                                : 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800'
                                        ]"
                                    >
                                        {{ reason }}
                                    </button>
                                </div>
                            </div>

                            <!-- Custom Reason -->
                            <div v-if="form.reason === 'Other'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Custom Reason <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.reason"
                                    type="text"
                                    required
                                    placeholder="Enter custom reason..."
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.reason
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                />
                            </div>
                            <p v-if="form.errors.reason" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.reason }}
                            </p>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Additional Notes
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    placeholder="Add any additional details about this block period..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all resize-none"
                                ></textarea>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Optional - Include details about the work to be done, contractor info, etc.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-6">
                        <Link
                            :href="route('admin.blocked-dates.index')"
                            class="px-6 py-3 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-full transition-all"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-full transition-all disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Blocking Dates...</span>
                            <span v-else>Block Dates</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
