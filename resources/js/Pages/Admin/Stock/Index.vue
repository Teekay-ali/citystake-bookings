<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Package, AlertTriangle, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    items:         Object,
    buildings:     Array,
    categories:    Array,
    lowStockCount: Number,
    filters:       Object,
})

const buildingId = ref(props.filters.building_id || '')
const category   = ref(props.filters.category || '')
const lowStock   = ref(props.filters.low_stock || '')

watch([buildingId, category, lowStock], () => {
    router.get(route('manage.stock.index'), {
        building_id: buildingId.value || undefined,
        category:    category.value || undefined,
        low_stock:   lowStock.value || undefined,
    }, { preserveState: true, replace: true })
})
</script>

<template>
    <ManageLayout>
        <Head title="Stock Keeping" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Stock Keeping</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Inventory management and usage tracking</p>
                </div>
                <Link :href="route('manage.stock.create')"
                      class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    Add Item
                </Link>
            </div>

            <!-- Low stock alert -->
            <div v-if="lowStockCount > 0"
                 class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl mb-6 cursor-pointer"
                 @click="lowStock = lowStock ? '' : '1'">
                <AlertTriangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                    {{ lowStockCount }} item{{ lowStockCount !== 1 ? 's' : '' }} below low stock threshold
                    <span class="font-normal">— click to filter</span>
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="buildingId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-if="categories.length" v-model="category"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Categories</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>
                <button v-if="buildingId || category || lowStock"
                        @click="buildingId = ''; category = ''; lowStock = ''"
                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-all">
                    Clear filters
                </button>
            </div>

            <!-- Items grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link v-for="item in items.data" :key="item.id"
                      :href="route('manage.stock.show', item.id)"
                      class="bg-white dark:bg-gray-900 border rounded-2xl p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                      :class="item.quantity <= item.low_stock_threshold
                        ? 'border-amber-200 dark:border-amber-800'
                        : 'border-gray-200 dark:border-gray-800'">

                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ item.name }}</p>
                            <p v-if="item.category" class="text-xs text-gray-400 mt-0.5">{{ item.category }}</p>
                        </div>
                        <AlertTriangle v-if="item.quantity <= item.low_stock_threshold"
                                       class="w-4 h-4 text-amber-500 shrink-0 ml-2" />
                    </div>

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-2xl font-semibold"
                               :class="item.quantity <= item.low_stock_threshold
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : 'text-gray-900 dark:text-white'">
                                {{ item.quantity }}
                            </p>
                            <p class="text-xs text-gray-400">{{ item.unit }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">{{ item.building?.name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Min: {{ item.low_stock_threshold }} {{ item.unit }}
                            </p>
                        </div>
                    </div>
                </Link>

                <div v-if="items.data.length === 0"
                     class="col-span-full text-center py-16">
                    <Package class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No stock items found.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="items.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="link in items.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-sm transition-all',
                        link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                    ]"
                      v-html="link.label" />
            </div>
        </div>
    </ManageLayout>
</template>
