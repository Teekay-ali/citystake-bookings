<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    Plus, ChevronDown, Building2, Edit, Trash2,
    Users, Eye, Home, Bed, MapPin, Search
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings: Array,
})

const showDeleteModal         = ref(false)
const showDeleteUnitTypeModal = ref(false)
const itemToDelete            = ref(null)
const isDeleting              = ref(false)
const searchQuery             = ref('')
const filterStatus            = ref('all')

// ── Computed stats ────────────────────────────────────────────
const totalProperties = computed(() => props.buildings.length)
const totalUnitTypes  = computed(() => props.buildings.reduce((sum, b) => sum + (b.unit_types_count || 0), 0))
const totalUnits      = computed(() => props.buildings.reduce((sum, b) => sum + (b.units_count || 0), 0))
const totalLocations  = computed(() => new Set(props.buildings.map(b => b.city)).size)

// ── Filtered buildings ────────────────────────────────────────
const filteredBuildings = computed(() => {
    let filtered = props.buildings
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        filtered = filtered.filter(b =>
            b.name.toLowerCase().includes(q) ||
            b.city.toLowerCase().includes(q) ||
            b.address.toLowerCase().includes(q)
        )
    }
    if (filterStatus.value !== 'all') {
        filtered = filtered.filter(b =>
            filterStatus.value === 'active' ? b.is_active : !b.is_active
        )
    }
    return filtered
})

// ── Expand / collapse ─────────────────────────────────────────
const expandedBuildings = ref({})
const toggleBuilding = (id) => {
    expandedBuildings.value[id] = !expandedBuildings.value[id]
}
watch(() => props.buildings, (buildings) => {
    buildings.forEach(b => { expandedBuildings.value[b.id] = true })
}, { immediate: true })

// ── Delete handlers ───────────────────────────────────────────
const openDeleteModal = (building) => {
    itemToDelete.value = building
    showDeleteModal.value = true
}

const openDeleteUnitTypeModal = (building, unitType) => {
    itemToDelete.value = { building, unitType }
    showDeleteUnitTypeModal.value = true
}

const deleteBuilding = () => {
    isDeleting.value = true
    router.delete(route('manage.properties.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false
            showDeleteModal.value = false
            itemToDelete.value = null
        }
    })
}

const deleteUnitType = () => {
    isDeleting.value = true
    router.delete(route('manage.unit-types.destroy', [itemToDelete.value.building.id, itemToDelete.value.unitType.id]), {
        onFinish: () => {
            isDeleting.value = false
            showDeleteUnitTypeModal.value = false
            itemToDelete.value = null
        }
    })
}

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false
        showDeleteUnitTypeModal.value = false
        itemToDelete.value = null
    }
}

const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('en-NG').format(price)
}

const inputClass = "px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Properties" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Properties</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage your properties and unit types</p>
            </div>
            <Link
                :href="route('manage.properties.create')"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Property
            </Link>
        </div>

        <!-- ── Stats ── -->
        <div v-if="buildings.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-blue-500 uppercase tracking-wider mb-2">Properties</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ totalProperties }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-green-500 uppercase tracking-wider mb-2">Unit Types</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ totalUnitTypes }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-violet-500 uppercase tracking-wider mb-2">Total Units</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ totalUnits }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                <p class="text-xs font-medium text-amber-500 uppercase tracking-wider mb-2">Locations</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ totalLocations }}</p>
            </div>
        </div>

        <!-- ── Search + filter ── -->
        <div v-if="buildings.length > 0" class="flex flex-col sm:flex-row gap-2 mb-4">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by name, city, or address..."
                    class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
            </div>
            <select v-model="filterStatus" :class="inputClass" style="width: auto">
                <option value="all">All statuses</option>
                <option value="active">Active only</option>
                <option value="inactive">Inactive only</option>
            </select>
            <button
                v-if="searchQuery || filterStatus !== 'all'"
                @click="searchQuery = ''; filterStatus = 'all'"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear
            </button>

            <span v-if="searchQuery || filterStatus !== 'all'"
                  class="self-center text-xs text-gray-400 dark:text-gray-500 ml-1">
                {{ filteredBuildings.length }} of {{ buildings.length }} shown
            </span>
        </div>

        <!-- ── Properties list ── -->
        <div class="space-y-3">
            <div
                v-for="building in filteredBuildings"
                :key="building.id"
                class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">

                <!-- Building header -->
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3.5 flex items-center gap-4">

                    <!-- Image -->
                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800 flex-shrink-0">
                        <img
                            v-if="building.images?.length > 0"
                            :src="building.images[0].url"
                            :alt="building.name"
                            class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <Building2 class="w-5 h-5 text-gray-400" />
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ building.name }}
                            </h2>
                            <span :class="building.is_active
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700'"
                                  class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-lg flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="building.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                                {{ building.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                            <MapPin class="w-3 h-3 flex-shrink-0" />
                            {{ building.address }}, {{ building.city }}
                        </p>
                    </div>

                    <!-- Meta counts -->
                    <div class="hidden sm:flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
                        <span class="flex items-center gap-1">
                            <Home class="w-3.5 h-3.5" />
                            {{ building.unit_types_count || 0 }} type{{ (building.unit_types_count || 0) !== 1 ? 's' : '' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <Bed class="w-3.5 h-3.5" />
                            {{ building.units_count || 0 }} unit{{ (building.units_count || 0) !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <Link
                            :href="route('manage.properties.edit', building.id)"
                            class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                            title="Edit property">
                            <Edit class="w-4 h-4" />
                        </Link>
                        <button
                            @click="openDeleteModal(building)"
                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                            title="Delete property">
                            <Trash2 class="w-4 h-4" />
                        </button>
                        <button
                            @click="toggleBuilding(building.id)"
                            class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                            title="Toggle unit types">
                            <ChevronDown
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': expandedBuildings[building.id] }" />
                        </button>
                    </div>
                </div>

                <!-- Unit types -->
                <div v-if="expandedBuildings[building.id]" class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            Unit Types
                        </p>
                        <Link
                            :href="route('manage.unit-types.create', building.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                            <Plus class="w-3 h-3" />
                            Add Unit Type
                        </Link>
                    </div>

                    <div v-if="building.unit_types?.length > 0" class="space-y-2">
                        <div
                            v-for="unitType in building.unit_types"
                            :key="unitType.id"
                            class="flex items-center gap-3 p-3.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                            <!-- Image -->
                            <div class="w-9 h-9 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900 flex-shrink-0">
                                <img
                                    v-if="unitType.images?.length > 0"
                                    :src="unitType.images[0].url"
                                    :alt="unitType.name"
                                    class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Bed class="w-4 h-4 text-gray-400" />
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ unitType.name }}
                                    </h4>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded flex-shrink-0">
                                        {{ unitType.bedroom_type }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <Users class="w-3 h-3" />
                                        Up to {{ unitType.max_guests }}
                                    </span>
                                    <span>·</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        ₦{{ formatPrice(unitType.base_price_per_night) }}/night
                                    </span>
                                    <span>·</span>
                                    <span class="flex items-center gap-1">
                                        <Home class="w-3 h-3" />
                                        {{ unitType.units_count || 0 }} unit{{ (unitType.units_count || 0) !== 1 ? 's' : '' }}
                                    </span>
                                    <span>·</span>
                                    <span :class="unitType.is_active
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-gray-400 dark:text-gray-600'"
                                          class="flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="unitType.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                                        {{ unitType.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions — visible on hover -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <Link
                                    v-if="unitType.slug"
                                    :href="route('properties.show', [building.slug, unitType.slug])"
                                    target="_blank"
                                    class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                                    title="View on website">
                                    <Eye class="w-3.5 h-3.5" />
                                </Link>
                                <Link
                                    :href="route('manage.unit-types.edit', [building.id, unitType.id])"
                                    class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all"
                                    title="Edit unit type">
                                    <Edit class="w-3.5 h-3.5" />
                                </Link>
                                <button
                                    @click="openDeleteUnitTypeModal(building, unitType)"
                                    class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                    title="Delete unit type">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- No unit types -->
                    <div v-else class="text-center py-8 bg-gray-50 dark:bg-gray-900/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                        <Bed class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">No unit types yet.</p>
                        <Link
                            :href="route('manage.unit-types.create', building.id)"
                            class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            <Plus class="w-3 h-3" />
                            Add Unit Type
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── No search results ── -->
        <div v-if="filteredBuildings.length === 0 && buildings.length > 0" class="text-center py-16">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Search class="w-6 h-6 text-gray-400" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No properties found</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Try adjusting your search or filter criteria.</p>
            <button
                @click="searchQuery = ''; filterStatus = 'all'"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Empty state (no buildings at all) ── -->
        <div v-if="buildings.length === 0" class="text-center py-20">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Building2 class="w-6 h-6 text-gray-400" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No properties yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
                Get started by adding your first property to begin managing your rental business.
            </p>
            <Link
                :href="route('manage.properties.create')"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Your First Property
            </Link>
        </div>

    </div>

    <!-- ── Modals ── -->
    <ConfirmationModal
        :show="showDeleteModal"
        :processing="isDeleting"
        title="Delete Property?"
        message="Are you sure you want to delete this property? This action cannot be undone. You can only delete properties with no existing bookings."
        confirm-text="Yes, Delete Property"
        cancel-text="Cancel"
        variant="danger"
        @confirm="deleteBuilding"
        @close="closeDeleteModal" />

    <ConfirmationModal
        :show="showDeleteUnitTypeModal"
        :processing="isDeleting"
        title="Delete Unit Type?"
        message="Are you sure you want to delete this unit type? This action cannot be undone. You can only delete unit types with no existing bookings."
        confirm-text="Yes, Delete Unit Type"
        cancel-text="Cancel"
        variant="danger"
        @confirm="deleteUnitType"
        @close="closeDeleteModal" />
</template>
