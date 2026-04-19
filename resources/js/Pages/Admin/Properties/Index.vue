<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import {
    Plus, ChevronDown,
    Building2,
    Edit,
    Trash2,
    Users,
    Eye,
    Home,
    Bed,
    MapPin,
    Search,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
});

const showDeleteModal = ref(false);
const showDeleteUnitTypeModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);
const searchQuery = ref('');
const filterStatus = ref('all');

// Computed stats
const totalProperties = computed(() => props.buildings.length);
const totalUnitTypes = computed(() => props.buildings.reduce((sum, b) => sum + (b.unit_types_count || 0), 0));
const totalUnits = computed(() => props.buildings.reduce((sum, b) => sum + (b.units_count || 0), 0));
const totalLocations = computed(() => new Set(props.buildings.map(b => b.city)).size);

// Filtered buildings
const filteredBuildings = computed(() => {
    let filtered = props.buildings;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(building =>
            building.name.toLowerCase().includes(query) ||
            building.city.toLowerCase().includes(query) ||
            building.address.toLowerCase().includes(query)
        );
    }

    // Filter by status
    if (filterStatus.value !== 'all') {
        filtered = filtered.filter(building =>
            filterStatus.value === 'active' ? building.is_active : !building.is_active
        );
    }

    return filtered;
});

const expandedBuildings = ref({});
const toggleBuilding = (id) => {
    expandedBuildings.value[id] = !expandedBuildings.value[id];
};
// Default all to expanded on load
watch(() => props.buildings, (buildings) => {
    buildings.forEach(b => { expandedBuildings.value[b.id] = true; });
}, { immediate: true });

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
    router.delete(route('manage.properties.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const deleteUnitType = () => {
    isDeleting.value = true;
    router.delete(route('manage.unit-types.destroy', [itemToDelete.value.building.id, itemToDelete.value.unitType.id]), {
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

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('en-NG').format(price);
};
</script>

<template>
    <ManageLayout>
        <Head title="Property Management - Admin" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-8">
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
                        :href="route('manage.properties.create')"
                        class="px-6 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all flex items-center shadow-lg"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Add Property
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div v-if="buildings.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <Building2 class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ totalProperties }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Properties
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-xl border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <Home class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ totalUnitTypes }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Unit Types
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-xl border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <Bed class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ totalUnits }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Units
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-4 rounded-xl border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                <MapPin class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                        <div class="text-xl font-semibold text-gray-900 dark:text-white mb-1">
                            {{ totalLocations }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Locations
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div v-if="buildings.length > 0" class="mb-6">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1 relative">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search properties by name, city, or address..."
                                class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                            />
                        </div>
                        <div class="relative">
                            <Filter class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <select
                                v-model="filterStatus"
                                class="pl-12 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white appearance-none cursor-pointer transition-all w-full sm:min-w-[180px]"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active Only</option>
                                <option value="inactive">Inactive Only</option>
                            </select>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div v-if="searchQuery || filterStatus !== 'all'" class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ filteredBuildings.length }} of {{ buildings.length }} properties
                    </div>
                </div>

                <!-- Properties List -->
                <div class="space-y-6">
                    <div
                        v-for="building in filteredBuildings"
                        :key="building.id"
                        class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                    >
                        <!-- Building Header -->
                        <div class="bg-gray-50 dark:bg-gray-900 p-6 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex gap-6">
                                <!-- Property Image -->
                                <div class="flex-shrink-0">
                                    <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800 ring-2 ring-gray-100 dark:ring-gray-800">
                                        <img
                                            v-if="building.images && building.images.length > 0"
                                            :src="building.images[0].url"
                                            :alt="building.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <Building2 class="w-6 h-6 text-gray-400" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Building Info -->
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ building.name }}
                                                </h2>
                                                <span
                                                    v-if="building.is_active"
                                                    class="px-3 py-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full border border-green-200 dark:border-green-800 flex items-center gap-1.5"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                    Active
                                                </span>
                                                <span
                                                    v-else
                                                    class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full flex items-center gap-1.5"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                    Inactive
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center mb-4">
                                                <MapPin class="w-4 h-4 mr-1.5" />
                                                {{ building.address }}, {{ building.city }}
                                            </p>
                                            <div class="flex items-center gap-6 text-sm">
                                                <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-950 rounded-lg border border-gray-200 dark:border-gray-800">
                                                    <Home class="w-4 h-4 text-gray-400" />
                                                    <span class="text-gray-900 dark:text-white font-medium">{{ building.unit_types_count || 0 }}</span>
                                                    <span class="text-gray-600 dark:text-gray-400">unit type{{ (building.unit_types_count || 0) !== 1 ? 's' : '' }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-950 rounded-lg border border-gray-200 dark:border-gray-800">
                                                    <Bed class="w-4 h-4 text-gray-400" />
                                                    <span class="text-gray-900 dark:text-white font-medium">{{ building.units_count || 0 }}</span>
                                                    <span class="text-gray-600 dark:text-gray-400">unit{{ (building.units_count || 0) !== 1 ? 's' : '' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Building Actions -->
                                        <!-- Building Actions -->
                                        <div class="flex items-center gap-2">
                                            <Link
                                                :href="route('manage.properties.edit', building.id)"
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

                                            <!-- Chevron toggle -->
                                            <button
                                                @click="toggleBuilding(building.id)"
                                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                                                title="Toggle unit types"
                                            >
                                                <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedBuildings[building.id] }" />
                                            </button>
                                        </div>                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unit Types -->
                        <div v-if="expandedBuildings[building.id]" class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <Bed class="w-5 h-5 text-gray-400" />
                                    Unit Types
                                </h3>
                                <Link
                                    :href="route('manage.unit-types.create', building.id)"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white text-sm font-medium rounded-lg transition-all flex items-center"
                                >
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Add Unit Type
                                </Link>
                            </div>

                            <div v-if="building.unit_types && building.unit_types.length > 0" class="space-y-3">
                                <div
                                    v-for="unitType in building.unit_types"
                                    :key="unitType.id"
                                    class="group flex items-center gap-4 p-4 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                                >
                                    <!-- Unit Type Image -->
                                    <div class="w-10 h-10 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-900 flex-shrink-0 ring-2 ring-gray-100 dark:ring-gray-900">
                                        <img
                                            v-if="unitType.images && unitType.images.length > 0"
                                            :src="unitType.images[0].url"
                                            :alt="unitType.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <Bed class="w-5 h-5 text-gray-400" />
                                        </div>
                                    </div>

                                    <!-- Unit Type Info -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ unitType.name }}
                                            </h4>
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 text-xs font-medium rounded">
                                                {{ unitType.bedroom_type }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="flex items-center gap-1.5">
                                                <Users class="w-3.5 h-3.5" />
                                                Up to {{ unitType.max_guests }} guests
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                ₦{{ formatPrice(unitType.base_price_per_night) }}/night
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span class="flex items-center gap-1.5">
                                                <Home class="w-3.5 h-3.5" />
                                                {{ unitType.units_count || 0 }} unit{{ (unitType.units_count || 0) !== 1 ? 's' : '' }}
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                            <span
                                                v-if="unitType.is_active"
                                                class="inline-flex items-center gap-1.5 text-green-600 dark:text-green-400 font-medium"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                Active
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                Inactive
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link
                                            v-if="unitType.slug"
                                            :href="route('properties.show', [building.slug, unitType.slug])"
                                            target="_blank"
                                            class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                                            title="View on website"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                        <Link
                                            :href="route('manage.unit-types.edit', [building.id, unitType.id])"
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

                            <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                                <Bed class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">
                                    No unit types yet. Add one to get started.
                                </p>
                                <Link
                                    :href="route('manage.unit-types.create', building.id)"
                                    class="inline-flex items-center text-sm text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                >
                                    <Plus class="w-4 h-4 mr-1" />
                                    Add Unit Type
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="filteredBuildings.length === 0 && buildings.length > 0" class="text-center py-20">
                    <Search class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">
                        No properties found
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Try adjusting your search or filter criteria
                    </p>
                    <button
                        @click="searchQuery = ''; filterStatus = 'all'"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-900 hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-900 dark:text-white font-medium rounded-full transition-all"
                    >
                        Clear filters
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="buildings.length === 0" class="text-center py-20">
                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6">
                        <Building2 class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-2xl font-medium text-gray-900 dark:text-white mb-2">
                        No properties yet
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        Get started by adding your first property to begin managing your rental business
                    </p>
                    <Link
                        :href="route('manage.properties.create')"
                        class="inline-flex items-center px-8 py-4 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-full transition-all shadow-lg"
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
    </ManageLayout>
</template>
