<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, FileText, ChevronRight, X } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    queries:      Object,
    buildings:    Array,
    staffMembers: Array,
    types:        Object,
    filters:      Object,
    counts:       Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const type       = ref(props.filters.type || '')
const staffId    = ref(props.filters.staff_id || '')

watch([buildingId, status, type, staffId], () => {
    router.get(route('manage.staff-queries.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        type:        type.value || undefined,
        staff_id:    staffId.value || undefined,
    }, { preserveState: true, replace: true })
})

const statusConfig = {
    open:      { label: 'Open',      cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
    responded: { label: 'Responded', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    closed:    { label: 'Closed',    cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
}

const pipeline = [
    { key: 'open',      label: 'Open'      },
    { key: 'responded', label: 'Responded' },
]

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const hasActiveFilters = () => buildingId.value || status.value || type.value || staffId.value

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"

// ── Create modal ──
const showCreate = ref(false)
const createForm = useForm({
    building_id: props.buildings.length === 1 ? props.buildings[0].id : '',
    staff_id: '', subject: '', description: '', type: 'other',
})
function openCreate() {
    createForm.reset(); createForm.clearErrors()
    if (props.buildings.length === 1) createForm.building_id = props.buildings[0].id
    showCreate.value = true
}
function submitCreate() {
    createForm.post(route('manage.staff-queries.store'), {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.reset() },
    })
}

const fieldCls = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const fieldLabel = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'
</script>

<template>
    <Head title="Staff Queries" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Staff Queries</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">HR records - confidential</p>
            </div>
            <button @click="openCreate"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Query
            </button>
        </div>

        <!-- ── Summary cards — clickable filters ── -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <button
                v-for="item in pipeline"
                :key="item.key"
                @click="status = status === item.key ? '' : item.key"
                :class="status === item.key
                    ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
                    : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 text-left transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts[item.key] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ status === item.key ? 'Click to clear' : 'Click to filter' }}
                </p>
            </button>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Total</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ queries.total }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">All queries</p>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-6">
            <select v-model="buildingId" :class="selectClass" style="width: auto">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-model="staffId" :class="selectClass" style="width: auto">
                <option value="">All staff</option>
                <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <select v-model="type" :class="selectClass" style="width: auto">
                <option value="">All types</option>
                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
            <button
                v-if="hasActiveFilters()"
                @click="buildingId = ''; status = ''; type = ''; staffId = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Query list ── -->
        <div class="space-y-2">
            <Link
                v-for="q in queries.data"
                :key="q.id"
                :href="route('manage.staff-queries.show', q.id)"
                class="flex items-start justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span :class="[statusConfig[q.status]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ statusConfig[q.status]?.label }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ types[q.type] }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ q.building?.name }}</span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate mb-1">{{ q.subject }}</p>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                        <span>Staff: <span class="font-medium text-gray-600 dark:text-gray-300">{{ q.staff?.name }}</span></span>
                        <span>·</span>
                        <span>By: {{ q.issued_by?.name }}</span>
                        <span>·</span>
                        <span>{{ formatDate(q.created_at) }}</span>
                    </div>
                </div>
                <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
            </Link>

            <!-- Empty state -->
            <div v-if="queries.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <FileText class="w-6 h-6 text-gray-400" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No staff queries recorded.</p>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="queries.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link
                v-for="link in queries.links"
                :key="link.label"
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
        <Modal :show="showCreate" max-width="2xl" @close="showCreate = false">
            <div class="p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">New Staff Query</h2>
                    <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><X class="w-4 h-4" /></button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Confidential — visible only to managers and admins.</p>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Building *</label>
                            <select v-model="createForm.building_id" :class="fieldCls">
                                <option value="">Select building</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="createForm.errors.building_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.building_id }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Staff Member *</label>
                            <select v-model="createForm.staff_id" :class="fieldCls">
                                <option value="">Select staff</option>
                                <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="createForm.errors.staff_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.staff_id }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Query Type *</label>
                            <select v-model="createForm.type" :class="fieldCls">
                                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label :class="fieldLabel">Subject *</label>
                            <input v-model="createForm.subject" type="text" placeholder="Brief subject line" :class="fieldCls" />
                            <p v-if="createForm.errors.subject" class="mt-1 text-xs text-red-600">{{ createForm.errors.subject }}</p>
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Description *</label>
                        <textarea v-model="createForm.description" rows="5" placeholder="Detailed description of the query, incident, or concern..." :class="[fieldCls, 'resize-none']" />
                        <p v-if="createForm.errors.description" class="mt-1 text-xs text-red-600">{{ createForm.errors.description }}</p>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="createForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ createForm.processing ? 'Saving...' : 'Record Query' }}
                        </button>
                        <button type="button" @click="showCreate = false"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
