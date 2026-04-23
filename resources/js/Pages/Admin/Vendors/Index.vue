<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Search, Pencil, Trash2, Phone, Mail, Star, BookOpen } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    vendors:    Object,
    categories: Object,
    filters:    Object,
})

const search   = ref(props.filters.search || '')
const category = ref(props.filters.category || '')
const status   = ref(props.filters.status || '')

let timeout = null
watch(search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 400)
})
watch([category, status], applyFilters)

function applyFilters() {
    router.get(route('manage.vendors.index'), {
        search:   search.value || undefined,
        category: category.value || undefined,
        status:   status.value || undefined,
    }, { preserveState: true, replace: true })
}

function destroy(vendor) {
    if (confirm(`Remove ${vendor.name} from the directory?`)) {
        router.delete(route('manage.vendors.destroy', vendor.id))
    }
}

const categoryColors = {
    plumbing:    'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
    electrical:  'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800',
    ac_hvac:     'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800',
    carpentry:   'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
    cleaning:    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
    security:    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800',
    it_telecoms: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border border-violet-200 dark:border-violet-800',
    landscaping: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800',
    painting:    'bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-400 border border-pink-200 dark:border-pink-800',
    general:     'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700',
    other:       'bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-500 border border-gray-200 dark:border-gray-700',
}

const hasActiveFilters = () => search.value || category.value || status.value

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Vendors & Contractors" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Vendors & Contractors</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Directory of all service providers</p>
            </div>
            <Link :href="route('manage.vendors.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Vendor
            </Link>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                <input v-model="search" type="text" placeholder="Search name, company, phone..."
                       class="pl-9 pr-4 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all w-60" />
            </div>
            <select v-model="category" :class="selectClass" style="width: auto">
                <option value="">All categories</option>
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button
                v-if="hasActiveFilters()"
                @click="search = ''; category = ''; status = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Table ── -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rating</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="vendor in vendors.data" :key="vendor.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">

                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-900 dark:text-white">{{ vendor.name }}</p>
                            <p v-if="vendor.company" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ vendor.company }}</p>
                        </td>

                        <td class="px-5 py-3.5">
                                <span :class="[categoryColors[vendor.category], 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                                    {{ categories[vendor.category] }}
                                </span>
                        </td>

                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <Phone class="w-3 h-3 flex-shrink-0" />
                                {{ vendor.phone }}
                            </div>
                            <div v-if="vendor.email" class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 mt-1">
                                <Mail class="w-3 h-3 flex-shrink-0" />
                                {{ vendor.email }}
                            </div>
                        </td>

                        <td class="px-5 py-3.5">
                            <div v-if="vendor.rating" class="flex items-center gap-1">
                                <Star class="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ vendor.rating }}</span>
                            </div>
                            <span v-else class="text-xs text-gray-300 dark:text-gray-600">—</span>
                        </td>

                        <td class="px-5 py-3.5">
                                <span :class="vendor.is_active
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                    : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800'"
                                      class="text-xs font-medium px-2 py-0.5 rounded-lg">
                                    {{ vendor.is_active ? 'Active' : 'Inactive' }}
                                </span>
                        </td>

                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link :href="route('manage.vendors.edit', vendor.id)"
                                      class="p-1.5 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all">
                                    <Pencil class="w-3.5 h-3.5" />
                                </Link>
                                <button @click="destroy(vendor)"
                                        class="p-1.5 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:border-red-200 dark:hover:border-red-800 transition-all">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state inside table -->
                    <tr v-if="vendors.data.length === 0">
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                                <BookOpen class="w-5 h-5 text-gray-400" />
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No vendors found.</p>
                            <Link v-if="!hasActiveFilters()"
                                  :href="route('manage.vendors.create')"
                                  class="inline-flex items-center gap-1.5 mt-3 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <Plus class="w-3 h-3" />
                                Add your first vendor
                            </Link>
                            <button v-else
                                    @click="search = ''; category = ''; status = ''"
                                    class="mt-3 text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline transition-colors">
                                Clear filters
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="vendors.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link v-for="link in vendors.links" :key="link.label"
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

    </div>
</template>
