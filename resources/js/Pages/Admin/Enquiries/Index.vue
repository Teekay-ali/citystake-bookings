<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Search, Inbox, Users, Moon, ArrowRight, CalendarDays, Building2 } from 'lucide-vue-next'

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

const totalCount = computed(() =>
    Object.values(props.counts).reduce((a, b) => a + b, 0)
)

const tabs = [
    { key: '',          label: 'All' },
    { key: 'new',       label: 'New' },
    { key: 'contacted', label: 'Contacted' },
    { key: 'converted', label: 'Converted' },
    { key: 'closed',    label: 'Closed' },
]

const statusLabel = { new: 'New', contacted: 'Contacted', converted: 'Converted', closed: 'Closed' }

const statusBadge = {
    new:       'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 ring-1 ring-blue-100 dark:ring-blue-900/50',
    contacted: 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-100 dark:ring-amber-900/50',
    converted: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-100 dark:ring-emerald-900/50',
    closed:    'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 ring-1 ring-gray-200 dark:ring-gray-700',
}

const statusAccent = {
    new:       'bg-blue-400 dark:bg-blue-500',
    contacted: 'bg-amber-400 dark:bg-amber-500',
    converted: 'bg-emerald-400 dark:bg-emerald-500',
    closed:    'bg-gray-300 dark:bg-gray-700',
}

const avatarTint = {
    new:       'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    contacted: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    converted: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
    closed:    'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400',
}

const initials = (name) =>
    (name ?? '').split(' ').filter(Boolean).slice(0, 2).map(n => n[0]).join('').toUpperCase() || '?'

const fmtDate = (d) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })

const nights = (a, b) => {
    const ms = new Date(b) - new Date(a)
    return Math.max(1, Math.round(ms / 86400000))
}

const relTime = (d) => {
    const diff = (Date.now() - new Date(d)) / 1000
    if (diff < 60)     return 'just now'
    if (diff < 3600)   return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400)  return `${Math.floor(diff / 3600)}h ago`
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

const selectClass = "w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Booking Requests" />

    <div class="p-4 lg:p-6 flex flex-col min-h-full">

        <!-- Header -->
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Booking Requests</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Guest enquiries awaiting confirmation</p>
            </div>
            <div v-if="counts.new" class="hidden sm:flex items-center gap-2 shrink-0 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-100 dark:ring-blue-900/40">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                <span class="text-xs font-medium text-blue-700 dark:text-blue-400">{{ counts.new }} new to review</span>
            </div>
        </div>

        <!-- Status tabs -->
        <div class="flex flex-wrap items-center gap-1.5 mb-4">
            <button v-for="t in tabs" :key="t.key"
                    @click="t.key === '' ? status = '' : setStatus(t.key)"
                    :class="['inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border transition-all',
                        status === t.key
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white'
                            : 'border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/60']">
                {{ t.label }}
                <span :class="['tabular-nums', status === t.key ? 'opacity-70' : 'text-gray-400 dark:text-gray-500']">
                    {{ t.key === '' ? totalCount : counts[t.key] }}
                </span>
            </button>
        </div>

        <!-- Toolbar -->
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 mb-5">
            <div class="relative col-span-2 sm:flex-1 sm:min-w-[200px]">
                <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="search" type="text" placeholder="Search name, email, phone…"
                       class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
            </div>
            <select v-model="buildingId" :class="[selectClass, 'sm:w-auto']">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>

        <!-- Empty -->
        <div v-if="enquiries.data.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-20">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mb-4">
                <Inbox class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No booking requests found</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters or search.</p>
        </div>

        <!-- List -->
        <div v-else class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
            <Link v-for="e in enquiries.data" :key="e.id"
                  :href="route('manage.enquiries.show', e.id)"
                  class="group relative flex items-center gap-3 sm:gap-4 pl-4 sm:pl-5 pr-4 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">

                <!-- Status accent -->
                <span :class="['absolute left-0 top-0 bottom-0 w-1', statusAccent[e.status]]"></span>

                <!-- Avatar -->
                <div :class="['w-10 h-10 rounded-full shrink-0 flex items-center justify-center text-xs font-semibold', avatarTint[e.status]]">
                    {{ initials(e.guest_name) }}
                </div>

                <!-- Guest + property -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ e.guest_name }}</p>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-1 mt-0.5">
                        <Building2 class="w-3 h-3 shrink-0" />
                        {{ e.unit_type?.name }} · {{ e.building?.name }}
                    </p>
                </div>

                <!-- Stay meta -->
                <div class="hidden md:flex flex-col items-end shrink-0 text-right">
                    <div class="flex items-center gap-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        <CalendarDays class="w-3.5 h-3.5 text-gray-400" />
                        {{ fmtDate(e.check_in) }}
                        <ArrowRight class="w-3 h-3 text-gray-300 dark:text-gray-600" />
                        {{ fmtDate(e.check_out) }}
                    </div>
                    <div class="flex items-center gap-2.5 text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                        <span class="inline-flex items-center gap-0.5"><Moon class="w-3 h-3" />{{ nights(e.check_in, e.check_out) }} night{{ nights(e.check_in, e.check_out) > 1 ? 's' : '' }}</span>
                        <span v-if="e.guests" class="inline-flex items-center gap-0.5"><Users class="w-3 h-3" />{{ e.guests }}</span>
                    </div>
                </div>

                <!-- Submitted -->
                <div class="hidden lg:block shrink-0 w-20 text-right text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                    {{ relTime(e.created_at) }}
                </div>

                <!-- Status -->
                <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium whitespace-nowrap shrink-0', statusBadge[e.status]]">
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
