<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, AlertTriangle, CheckCircle2, Clock, ChevronRight, MapPin, Paperclip, Upload, X } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    complaints: Object,
    buildings:  Array,
    filters:    Object,
    counts:     Object,
})

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const severity   = ref(props.filters.severity || '')

watch([buildingId, status, severity], () => {
    router.get(route('manage.complaints.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        severity:    severity.value || undefined,
    }, { preserveState: true, replace: true })
})

const severityConfig = {
    low:    { label: 'Low',    cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    medium: { label: 'Medium', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    high:   { label: 'High',   cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    urgent: { label: 'Urgent', cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
}

const statusConfig = {
    open:        { label: 'Open',        icon: AlertTriangle, cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
    in_progress: { label: 'In Progress', icon: Clock,         cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    resolved:    { label: 'Resolved',    icon: CheckCircle2,  cls: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' },
}

// Pipeline summary cards — clickable filters
const pipeline = [
    { key: 'open',        label: 'Open',        countKey: 'open' },
    { key: 'in_progress', label: 'In Progress',  countKey: 'in_progress' },
    { key: 'resolved',    label: 'Resolved',     countKey: 'resolved' },
]

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"

// ── Create modal ──
const showCreate = ref(false)
const previews = ref([])
const createForm = useForm({
    building_id: props.buildings.length === 1 ? props.buildings[0].id : '',
    title: '', description: '', location: '', severity: 'medium', photos: [],
})
function openCreate() {
    createForm.reset(); createForm.clearErrors(); previews.value = []
    if (props.buildings.length === 1) createForm.building_id = props.buildings[0].id
    showCreate.value = true
}
function handlePhotos(e) {
    const files = Array.from(e.target.files)
    if (files.length + createForm.photos.length > 5) { alert('Maximum 5 photos allowed.'); return }
    createForm.photos = [...createForm.photos, ...files]
    files.forEach(file => {
        const reader = new FileReader()
        reader.onload = (ev) => previews.value.push(ev.target.result)
        reader.readAsDataURL(file)
    })
}
function removePhoto(i) { createForm.photos.splice(i, 1); previews.value.splice(i, 1) }
function submitCreate() {
    createForm.post(route('manage.complaints.store'), {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.reset(); previews.value = [] },
    })
}

const fieldCls = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const fieldLabel = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'
</script>

<template>
    <Head title="Complaint Reports" />

    <div class="p-4 lg:p-6 flex flex-col gap-4 min-h-full">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Complaint Reports</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Staff-reported issues and resolutions</p>
            </div>
            <button @click="openCreate"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                Report Issue
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
                class="bg-white dark:bg-gray-900 border rounded-xl p-4 text-left transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts[item.countKey] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ status === item.key ? 'Click to clear' : 'Click to filter' }}
                </p>
            </button>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-6">
            <select v-model="buildingId" :class="selectClass" style="width: auto">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>

            <select v-model="severity" :class="selectClass" style="width: auto">
                <option value="">All severities</option>
                <option v-for="(cfg, key) in severityConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>

            <button
                v-if="buildingId || status || severity"
                @click="buildingId = ''; status = ''; severity = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Complaints list ── -->
        <div class="space-y-2">
            <Link
                v-for="complaint in complaints.data"
                :key="complaint.id"
                :href="route('manage.complaints.show', complaint.id)"
                class="flex items-start justify-between gap-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all">

                <div class="flex-1 min-w-0">
                    <!-- Badges -->
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span :class="[severityConfig[complaint.severity]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ severityConfig[complaint.severity]?.label }}
                        </span>
                        <span :class="[statusConfig[complaint.status]?.cls, 'inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-lg']">
                            <component :is="statusConfig[complaint.status]?.icon" class="w-3 h-3" />
                            {{ statusConfig[complaint.status]?.label }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ complaint.building?.name }}</span>
                    </div>

                    <!-- Title -->
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate mb-1">{{ complaint.title }}</p>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400 dark:text-gray-500">
                        <span>{{ complaint.submitted_by?.name }}</span>
                        <span>·</span>
                        <span>{{ formatDate(complaint.created_at) }}</span>
                        <span v-if="complaint.location" class="flex items-center gap-1">
                            <span>·</span>
                            <MapPin class="w-3 h-3" />
                            {{ complaint.location }}
                        </span>
                        <span v-if="complaint.photos?.length" class="flex items-center gap-1">
                            <span>·</span>
                            <Paperclip class="w-3 h-3" />
                            {{ complaint.photos.length }} photo{{ complaint.photos.length > 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
            </Link>

            <!-- Empty state -->
            <div v-if="complaints.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <AlertTriangle class="w-6 h-6 text-gray-400" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">No complaints found.</p>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="complaints.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link
                v-for="link in complaints.links"
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
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Report an Issue</h2>
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
                    <div>
                        <label :class="fieldLabel">Issue Title *</label>
                        <input v-model="createForm.title" type="text" placeholder="Brief description of the issue" :class="fieldCls" />
                        <p v-if="createForm.errors.title" class="mt-1 text-xs text-red-600">{{ createForm.errors.title }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Location</label>
                            <input v-model="createForm.location" type="text" placeholder="e.g. Unit 5, Lobby" :class="fieldCls" />
                        </div>
                        <div>
                            <label :class="fieldLabel">Severity *</label>
                            <select v-model="createForm.severity" :class="fieldCls">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Description *</label>
                        <textarea v-model="createForm.description" rows="4" placeholder="Describe the issue in detail..." :class="[fieldCls, 'resize-none']" />
                        <p v-if="createForm.errors.description" class="mt-1 text-xs text-red-600">{{ createForm.errors.description }}</p>
                    </div>
                    <div>
                        <label :class="fieldLabel">Photos <span class="text-gray-400 font-normal">(up to 5, max 5MB each)</span></label>
                        <div v-if="previews.length" class="grid grid-cols-3 gap-2 mb-3">
                            <div v-for="(src, i) in previews" :key="i" class="relative aspect-square">
                                <img :src="src" class="w-full h-full object-cover rounded-xl" />
                                <button type="button" @click="removePhoto(i)" class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-all">
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                        <label v-if="createForm.photos.length < 5"
                               class="flex items-center gap-3 px-4 py-3 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-gray-400 dark:hover:border-gray-500 transition-all">
                            <Upload class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">Click to upload photos</span>
                            <input type="file" accept="image/*" multiple class="sr-only" @change="handlePhotos" />
                        </label>
                        <p v-if="createForm.errors.photos" class="mt-1 text-xs text-red-600">{{ createForm.errors.photos }}</p>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="createForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ createForm.processing ? 'Submitting...' : 'Submit Complaint' }}
                        </button>
                        <button type="button" @click="showCreate = false"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
