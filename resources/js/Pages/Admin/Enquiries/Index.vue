<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Search, Inbox } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    enquiries: Object,
    buildings: Array,
    counts:    Object,
    filters:   Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const search     = ref(props.filters.search || '')

function debounce(fn, wait) {
    let t
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), wait) }
}

function go() {
    router.get(route('manage.enquiries.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        search:      search.value || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true })
}

watch([buildingId, status], go)
watch(search, debounce(go, 350))

function setStatus(s) {
    status.value = status.value === s ? '' : s
}

const statusBadge = {
    new:       'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    contacted: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    converted: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    closed:    'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400',
}

const statusLabel = { new: 'New', contacted: 'Contacted', converted: 'Converted', closed: 'Closed' }

const fmtDate = (d) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })

const selectClass = "px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Booking Requests" />

    <div class="p-4 lg:p-6 flex flex-col min-h-full">

        <div class="mb-5">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Booking Requests</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Guest enquiries awaiting confirmation</p>
        </div>

        <!-- Status chips -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <button @click="status = ''"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', !status ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                All
            </button>
            <button v-for="s in ['new','contacted','converted','closed']" :key="s" @click="setStatus(s)"
                    :class="['text-xs px-3 py-1.5 rounded-lg border transition-all', status === s ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                {{ statusLabel[s] }} <span class="opacity-60">{{ counts[s] }}</span>
            </button>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <div class="relative flex-1 min-w-[180px]">
                <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="search" type="text" placeholder="Search name, email, phone…"
                       class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
            </div>
            <select v-model="buildingId" :class="selectClass">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>

        <!-- Empty -->
        <div v-if="enquiries.data.length === 0" class="text-center py-20">
            <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Inbox class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">No booking requests found.</p>
        </div>

        <!-- List -->
        <div v-else class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <Link v-for="e in enquiries.data" :key="e.id"
                  :href="route('manage.enquiries.show', e.id)"
                  class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ e.guest_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                        {{ e.unit_type?.name }} · {{ e.building?.name }}
                    </p>
                </div>
                <div class="hidden sm:block text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {{ fmtDate(e.check_in) }} → {{ fmtDate(e.check_out) }}
                </div>
                <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium whitespace-nowrap', statusBadge[e.status]]">
                    {{ statusLabel[e.status] }}
                </span>
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="enquiries.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link v-for="link in enquiries.links" :key="link.label"
                  :href="link.url || '#'"
                  :class="[
                    'min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                    link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                        : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                ]"
                  v-html="link.label" />
        </div>
    </div>
</template>
