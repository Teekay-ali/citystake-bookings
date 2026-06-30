<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, Package, AlertTriangle, Search, LayoutGrid, List, Pencil, X } from 'lucide-vue-next'

function debounce(fn, wait) {
    let t
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait) }
}

defineOptions({ layout: ManageLayout })

const props = defineProps({
    items:      Object,
    buildings:  Array,
    categories: Array,
    stats:      Object,
    filters:    Object,
})

const buildingId = ref(props.filters.building_id || '')
const category   = ref(props.filters.category || '')
const status     = ref(props.filters.status || '')
const sort       = ref(props.filters.sort || 'lowest')
const search     = ref(props.filters.search || '')
const view       = ref(localStorage.getItem('stockView') || 'list')

function go() {
    router.get(route('manage.stock.index'), {
        building_id: buildingId.value || undefined,
        category:    category.value || undefined,
        status:      status.value || undefined,
        sort:        sort.value !== 'lowest' ? sort.value : undefined,
        search:      search.value || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true })
}

watch([buildingId, category, status, sort], go)
watch(search, debounce(go, 350))
watch(view, (v) => localStorage.setItem('stockView', v))

function setStatus(s) {
    status.value = status.value === s ? '' : s
}

function clearFilters() {
    buildingId.value = ''; category.value = ''; status.value = ''; search.value = ''
}

// ── Create / Edit modals ──
const unitOptions = ['units', 'kg', 'g', 'litres', 'ml', 'packs', 'boxes', 'rolls', 'pairs', 'sets']

const showCreate = ref(false)
const createForm = useForm({
    building_id: props.buildings.length === 1 ? props.buildings[0].id : '',
    name: '', category: '', unit: 'units', quantity: 0, low_stock_threshold: 5, notes: '',
})
function openCreate() {
    createForm.reset()
    createForm.clearErrors()
    if (props.buildings.length === 1) createForm.building_id = props.buildings[0].id
    showCreate.value = true
}
function submitCreate() {
    createForm.post(route('manage.stock.store'), {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.reset() },
    })
}

const editing  = ref(null)
const editForm = useForm({
    name: '', category: '', unit: 'units', low_stock_threshold: 5, notes: '', is_active: true,
})
function openEdit(item) {
    editing.value = item
    editForm.clearErrors()
    editForm.name = item.name
    editForm.category = item.category ?? ''
    editForm.unit = item.unit
    editForm.low_stock_threshold = item.low_stock_threshold
    editForm.notes = item.notes ?? ''
    editForm.is_active = item.is_active
}
function submitEdit() {
    editForm.put(route('manage.stock.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null },
    })
}

const fieldCls = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const fieldLabel = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'

// Stock health: returns { label, pct, bar, text } for an item
function health(item) {
    const qty = Number(item.quantity)
    const min = Number(item.low_stock_threshold) || 0
    if (qty <= 0) return { label: 'Out of stock', pct: 0, bar: 'bg-red-500', text: 'text-red-600 dark:text-red-400' }
    if (qty <= min) return { label: `Low · min ${min}`, pct: Math.max(8, Math.round((qty / Math.max(min, 1)) * 100)), bar: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' }
    // healthy: cap the bar at 100% relative to 2× threshold for a sensible scale
    const pct = min > 0 ? Math.min(100, Math.round((qty / (min * 2)) * 100)) : 100
    return { label: 'In stock', pct: Math.max(pct, 50), bar: 'bg-emerald-500', text: 'text-gray-400 dark:text-gray-500' }
}

const selectClass = "px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Stock Keeping" />

    <div class="p-4 lg:p-6 flex flex-col min-h-full">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Stock Keeping</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Inventory management and usage tracking</p>
            </div>
            <button @click="openCreate"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Item
            </button>
        </div>

        <!-- ── Stat strip ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total items</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-0.5">{{ stats.total }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">In stock</p>
                <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ stats.in_stock }}</p>
            </div>
            <button @click="setStatus('low')"
                    class="text-left bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 transition-all"
                    :class="status === 'low' ? 'border-amber-400 dark:border-amber-600 ring-1 ring-amber-400/40' : 'border-gray-200 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-700'">
                <p class="text-xs text-gray-500 dark:text-gray-400">Low</p>
                <p class="text-2xl font-semibold text-amber-600 dark:text-amber-400 mt-0.5">{{ stats.low }}</p>
            </button>
            <button @click="setStatus('out')"
                    class="text-left bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 transition-all"
                    :class="status === 'out' ? 'border-red-400 dark:border-red-600 ring-1 ring-red-400/40' : 'border-gray-200 dark:border-gray-800 hover:border-red-300 dark:hover:border-red-700'">
                <p class="text-xs text-gray-500 dark:text-gray-400">Out of stock</p>
                <p class="text-2xl font-semibold text-red-600 dark:text-red-400 mt-0.5">{{ stats.out }}</p>
            </button>
        </div>

        <!-- ── Toolbar: search + sort + view ── -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <div class="relative flex-1 min-w-[180px]">
                <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="search" type="text" placeholder="Search items…"
                       class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
            </div>
            <select v-model="buildingId" :class="selectClass">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-if="categories.length" v-model="category" :class="selectClass">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select v-model="sort" :class="selectClass">
                <option value="lowest">Sort: Lowest stock</option>
                <option value="quantity">Sort: Highest quantity</option>
                <option value="name">Sort: Name (A–Z)</option>
                <option value="oldest">Sort: Oldest added</option>
            </select>
            <div class="flex rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                <button @click="view = 'list'" :class="['p-2 transition-all', view === 'list' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200']" title="List view">
                    <List class="w-4 h-4" />
                </button>
                <button @click="view = 'grid'" :class="['p-2 transition-all', view === 'grid' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200']" title="Grid view">
                    <LayoutGrid class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- ── Filter chips ── -->
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <button @click="status = ''"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', !status ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                All <span class="opacity-60">{{ stats.total }}</span>
            </button>
            <button @click="setStatus('in')"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', status === 'in' ? 'bg-emerald-500 text-white border-emerald-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                In stock <span class="opacity-70">{{ stats.in_stock }}</span>
            </button>
            <button @click="setStatus('low')"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', status === 'low' ? 'bg-amber-500 text-white border-amber-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                Low <span class="opacity-70">{{ stats.low }}</span>
            </button>
            <button @click="setStatus('out')"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', status === 'out' ? 'bg-red-500 text-white border-red-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                Out of stock <span class="opacity-70">{{ stats.out }}</span>
            </button>
            <button v-if="buildingId || category || status || search"
                    @click="clearFilters"
                    class="text-xs px-3 py-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Empty state ── -->
        <div v-if="items.data.length === 0" class="text-center py-20">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Package class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">No stock items found.</p>
        </div>

        <!-- ── List view ── -->
        <div v-else-if="view === 'list'" class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
            <div class="hidden sm:grid grid-cols-[1fr_100px_160px] gap-4 px-5 py-2.5 border-b border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                <span>Item</span>
                <span class="text-right">Quantity</span>
                <span>Stock level</span>
            </div>
            <Link v-for="item in items.data" :key="item.id"
                  :href="route('manage.stock.show', item.id)"
                  class="relative group grid grid-cols-[1fr_auto] sm:grid-cols-[1fr_100px_160px] gap-3 sm:gap-4 px-5 py-3.5 items-center border-b border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                        <span v-if="item.category">{{ item.category }} · </span>{{ item.building?.name }}
                    </p>
                </div>
                <div class="text-right whitespace-nowrap">
                    <span class="text-base font-semibold" :class="health(item).text.includes('gray') ? 'text-gray-900 dark:text-white' : health(item).text">{{ item.quantity }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">{{ item.unit }}</span>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all" :class="health(item).bar" :style="{ width: health(item).pct + '%' }" />
                    </div>
                    <p class="text-[11px] mt-1" :class="health(item).text">{{ health(item).label }}</p>
                </div>
                <button @click.prevent.stop="openEdit(item)" title="Edit"
                        class="absolute top-2 right-3 p-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-gray-900 dark:hover:text-white opacity-0 group-hover:opacity-100 transition-all">
                    <Pencil class="w-3.5 h-3.5" />
                </button>
            </Link>
        </div>

        <!-- ── Grid view ── -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <Link v-for="item in items.data" :key="item.id"
                  :href="route('manage.stock.show', item.id)"
                  class="relative group bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                  :class="item.quantity <= 0
                    ? 'border-red-200 dark:border-red-800'
                    : item.quantity <= item.low_stock_threshold
                        ? 'border-amber-200 dark:border-amber-800'
                        : 'border-gray-200 dark:border-gray-800'">
                <button @click.prevent.stop="openEdit(item)" title="Edit"
                        class="absolute top-3 right-3 p-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 hover:text-gray-900 dark:hover:text-white opacity-0 group-hover:opacity-100 transition-all z-10">
                    <Pencil class="w-3.5 h-3.5" />
                </button>
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ item.name }}</p>
                        <p v-if="item.category" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ item.category }}</p>
                    </div>
                    <AlertTriangle v-if="item.quantity <= item.low_stock_threshold"
                                   class="w-4 h-4 shrink-0 ml-2 group-hover:opacity-0 transition-opacity"
                                   :class="item.quantity <= 0 ? 'text-red-500' : 'text-amber-500'" />
                </div>
                <div class="flex items-end justify-between mb-3">
                    <div>
                        <p class="text-2xl font-semibold" :class="item.quantity <= 0 ? 'text-red-600 dark:text-red-400' : item.quantity <= item.low_stock_threshold ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'">{{ item.quantity }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ item.unit }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ item.building?.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Min: {{ item.low_stock_threshold }} {{ item.unit }}</p>
                    </div>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-1.5 rounded-full" :class="health(item).bar" :style="{ width: health(item).pct + '%' }" />
                </div>
            </Link>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="items.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link v-for="link in items.links" :key="link.label"
                  :href="link.url || '#'"
                  :class="[
                    'min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                    link.active
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                        : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                ]"
                  v-html="link.label" />
        </div>

        <!-- ── Create modal ── -->
        <Modal :show="showCreate" max-width="xl" @close="showCreate = false">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Stock Item</h2>
                    <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label :class="fieldLabel">Building *</label>
                        <select v-model="createForm.building_id" :class="fieldCls">
                            <option value="">Select building</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="createForm.errors.building_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.building_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label :class="fieldLabel">Item Name *</label>
                            <input v-model="createForm.name" type="text" placeholder="e.g. Toilet Paper" :class="fieldCls" />
                            <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Category</label>
                            <input v-model="createForm.category" type="text" placeholder="e.g. Cleaning" :class="fieldCls" />
                        </div>
                        <div>
                            <label :class="fieldLabel">Unit *</label>
                            <select v-model="createForm.unit" :class="fieldCls">
                                <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div>
                            <label :class="fieldLabel">Initial Quantity *</label>
                            <input v-model="createForm.quantity" type="number" min="0" :class="fieldCls" />
                            <p v-if="createForm.errors.quantity" class="mt-1 text-xs text-red-600">{{ createForm.errors.quantity }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Low Stock Alert At *</label>
                            <input v-model="createForm.low_stock_threshold" type="number" min="0" :class="fieldCls" />
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Notes</label>
                        <textarea v-model="createForm.notes" rows="2" :class="[fieldCls, 'resize-none']" />
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="createForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ createForm.processing ? 'Saving...' : 'Add Item' }}
                        </button>
                        <button type="button" @click="showCreate = false"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ── Edit modal ── -->
        <Modal :show="!!editing" max-width="xl" @close="editing = null">
            <div v-if="editing" class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Edit {{ editing.name }}</h2>
                    <button @click="editing = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label :class="fieldLabel">Item Name *</label>
                            <input v-model="editForm.name" type="text" :class="fieldCls" />
                            <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-600">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Category</label>
                            <input v-model="editForm.category" type="text" :class="fieldCls" />
                        </div>
                        <div>
                            <label :class="fieldLabel">Unit *</label>
                            <select v-model="editForm.unit" :class="fieldCls">
                                <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label :class="fieldLabel">Low Stock Alert At *</label>
                            <input v-model="editForm.low_stock_threshold" type="number" min="0" :class="fieldCls" />
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Notes</label>
                        <textarea v-model="editForm.notes" rows="2" :class="[fieldCls, 'resize-none']" />
                    </div>
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Active</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Inactive items are hidden from the stock list</p>
                        </div>
                        <button type="button" @click="editForm.is_active = !editForm.is_active"
                                :class="editForm.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="editForm.is_active ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                        </button>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="editForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <button type="button" @click="editing = null"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
