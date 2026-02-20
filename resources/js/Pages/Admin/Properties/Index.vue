<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import {
    Plus,
    Building2,
    Edit,
    Trash2,
    Users,
    Eye,
    Home,
    Bed,
    MapPin,
    ToggleLeft,
    ToggleRight
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
});

const showDeleteModal = ref(false);
const showDeleteUnitTypeModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (building) => {
    itemToDelete.value = building;
    showDeleteModal.value = true;
};

const openDeleteUnitTypeModal = (building, unitType) => {
    itemToDelete.value = { building, unitType };
    showDeleteUnitTypeModal.value = true;
};

const deleteBuilding = () => {
    isDeleting.value = true;
    router.delete(route('admin.properties.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const deleteUnitType = () => {
    isDeleting.value = true;
    router.delete(route('admin.unit-types.destroy', [itemToDelete.value.building.id, itemToDelete.value.unitType.id]), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteUnitTypeModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
        showDeleteUnitTypeModal.value = false;
        itemToDelete.value = null;
    }
};

</script>

<template>
    <AppLayout>
        <Head title="Property Management - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            Property Management
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Manage your properties and unit types
                        </p>
                    </div>

                    <Link
                        :href="route('admin.properties.create')"
                        class="px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all flex items-center"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Add Property
                    </Link>
                </div>

                <!-- Properties List -->
                <div class="space-y-6">
                    <div
                        v-for="building in buildings"
                        :key="building.id"
                        class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden"
                    >
                        <!-- Building Header -->
                        <div class="bg-gray-50 dark:bg-gray-900 p-6 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h2 class="text-2xl font-medium text-gray-900 dark:text-white">
                                            {{ building.name }}
                                        </h2>
                                        <span
                                            v-if="building.is_active"
                                            class="px-3 py-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full border border-green-200 dark:border-green-800"
                                        >
                                            Active
                                        </span>
                                        <span
                                            v-else
                                            class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full"
                                        >
                                            Inactive
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center mb-3">
                                        <MapPin class="w-4 h-4 mr-1" />
                                        {{ building.address }}, {{ building.city }}
                                    </p>
                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <Home class="w-4 h-4 mr-1" />
                                            {{ building.unit_types_count }} unit types
                                        </span>
                                        <span class="flex items-center">
                                            <Bed class="w-4 h-4 mr-1" />
                                            {{ building.units_count }} units
                                        </span>
                                    </div>
                                </div>

                                <!-- Building Actions -->
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('admin.properties.edit', building.id)"
                                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                                        title="Edit property"
                                    >
                                        <Edit class="w-5 h-5" />
                                    </Link>
                                    <button
                                        @click="openDeleteModal(building)"
                                        class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                        title="Delete property"
                                    >
                                        <Trash2 class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Unit Types -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Unit Types
                                </h3>
                                <Link
                                    :href="route('admin.unit-types.create', building.id)"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-all flex items-center"
                                >
                                    <Plus class="w-4 h-4 mr-1" />
                                    Add Unit Type
                                </Link>
                            </div>

                            <div v-if="building.unit_types && building.unit_types.length > 0" class="space-y-3">
                                <div
                                    v-for="unitType in building.unit_types"
                                    :key="unitType.id"
                                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                {{ unitType.name }}
                                            </h4>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ unitType.bedroom_type }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="flex items-center">
                                                <Users class="w-3.5 h-3.5 mr-1.5" />
                                                {{ unitType.max_guests }} guests max
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                ₦{{ Number(unitType.base_price_per_night).toLocaleString() }}/night
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span class="flex items-center">
                                                <Home class="w-3.5 h-3.5 mr-1.5" />
                                                {{ unitType.units_count || 0 }} unit{{ (unitType.units_count || 0) !== 1 ? 's' : '' }}
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span
                                                v-if="unitType.is_active"
                                                class="inline-flex items-center text-green-600 dark:text-green-400 font-medium"
                                            >
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
                                                Active
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center text-gray-500 dark:text-gray-400"
                                            >
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1.5"></span>
                                                Inactive
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="route('admin.unit-types.edit', [building.id, unitType.id])"
                                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                                            title="Edit unit type"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </Link>
                                        <button
                                            @click="openDeleteUnitTypeModal(building, unitType)"
                                            class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                            title="Delete unit type"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">
                                    No unit types yet. Add one to get started.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="buildings.length === 0" class="text-center py-20">
                    <Building2 class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                        No properties yet
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Get started by adding your first property
                    </p>
                    <Link
                        :href="route('admin.properties.create')"
                        class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Add Your First Property
                    </Link>
                </div>

                <!-- Delete Building Modal -->
                <ConfirmationModal
                    :show="showDeleteModal"
                    :processing="isDeleting"
                    title="Delete Property?"
                    message="Are you sure you want to delete this property? This action cannot be undone. You can only delete properties with no existing bookings."
                    confirm-text="Yes, Delete Property"
                    cancel-text="Cancel"
                    variant="danger"
                    @confirm="deleteBuilding"
                    @close="closeDeleteModal"
                />

                <!-- Delete Unit Type Modal -->
                <ConfirmationModal
                    :show="showDeleteUnitTypeModal"
                    :processing="isDeleting"
                    title="Delete Unit Type?"
                    message="Are you sure you want to delete this unit type? This action cannot be undone. You can only delete unit types with no existing bookings."
                    confirm-text="Yes, Delete Unit Type"
                    cancel-text="Cancel"
                    variant="danger"
                    @confirm="deleteUnitType"
                    @close="closeDeleteModal"
                />
            </div>
        </div>
    </AppLayout>
</template>
