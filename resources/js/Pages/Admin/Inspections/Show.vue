<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Building2, CheckCircle, AlertTriangle, Plus, Trash2, ImagePlus, X, ClipboardCheck, User, Clock } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    inspection: Object,
    categories: { type: Array, default: () => [] },
    severities: { type: Array, default: () => [] },
})

const readOnly = computed(() => props.inspection.status === 'completed')

const form = useForm({
    overall_result: props.inspection.overall_result ?? '',
    summary:        props.inspection.summary ?? '',
    findings:       (props.inspection.findings ?? []).map(f => ({ ...f })),
    photos:         [],
    remove_photos:  [],
})

const visiblePhotos = computed(() =>
    (props.inspection.photos ?? []).filter(p => !form.remove_photos.includes(p.path))
)

const newPreviews = ref([])
const dragging = ref(false)
function addFiles(files) {
    const imgs = Array.from(files).filter(f => f.type.startsWith('image/'))
    form.photos.push(...imgs)
    imgs.forEach(f => newPreviews.value.push({ name: f.name, url: URL.createObjectURL(f) }))
}
function onPickPhotos(e) { addFiles(e.target.files); e.target.value = '' }
function onDrop(e) { dragging.value = false; if (!readOnly.value) addFiles(e.dataTransfer.files) }
function removeNewPhoto(idx) { form.photos.splice(idx, 1); newPreviews.value.splice(idx, 1) }
function removeExistingPhoto(path) { form.remove_photos.push(path) }

function setResult(r) {
    form.overall_result = r
    if (r === 'ok') form.findings = []
    else if (form.findings.length === 0) addFinding()
}
function addFinding() { form.findings.push({ category: 'other', severity: 'low', description: '' }) }
function removeFinding(i) { form.findings.splice(i, 1) }

function afterSubmit() { form.photos = []; newPreviews.value = [] }
function save() {
    form.post(route('manage.inspections.update', props.inspection.id), { forceFormData: true, preserveScroll: true, onSuccess: afterSubmit })
}
function complete() {
    form.post(route('manage.inspections.complete', props.inspection.id), { forceFormData: true })
}

function relTime(d) {
    if (!d) return null
    const diff = Math.floor((Date.now() - new Date(d)) / 60000)
    if (diff < 1) return 'just now'
    if (diff < 60) return `${diff}m ago`
    if (diff < 1440) return `${Math.floor(diff / 60)}h ago`
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

const cap = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : s
const photoCount = computed(() => visiblePhotos.value.length + newPreviews.value.length)
const severityCls = {
    low:    'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    medium: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
    high:   'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400',
}
const selectCls = 'px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const cardCls = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'
</script>

<template>
    <Head :title="`Inspection · Unit ${inspection.unit_number}`" />

    <div class="p-4 lg:p-6 max-w-3xl mx-auto">

        <Link :href="route('manage.inspections.index')" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors mb-4">
            <ArrowLeft class="w-4 h-4" /> Inspections
        </Link>

        <!-- Hero -->
        <div :class="cardCls" class="p-5 mb-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight">Unit {{ inspection.unit_number }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5">
                        <Building2 class="w-3.5 h-3.5" /> {{ inspection.building_name }} · {{ inspection.unit_type }}
                    </p>
                </div>
                <span v-if="readOnly" class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium"
                      :class="inspection.overall_result === 'concerns' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'">
                    <ClipboardCheck class="w-3.5 h-3.5" /> Completed
                </span>
                <span v-else class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" /> In progress
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="inspection.inspector" class="inline-flex items-center gap-1.5"><User class="w-3.5 h-3.5" /> {{ inspection.inspector }}</span>
                <span v-if="inspection.started_at" class="inline-flex items-center gap-1.5"><Clock class="w-3.5 h-3.5" /> Started {{ relTime(inspection.started_at) }}</span>
                <span v-if="photoCount" class="inline-flex items-center gap-1.5"><ImagePlus class="w-3.5 h-3.5" /> {{ photoCount }} photo{{ photoCount !== 1 ? 's' : '' }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Overall result -->
            <div :class="cardCls" class="p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Overall result</p>
                <div v-if="!readOnly" class="grid grid-cols-2 gap-3">
                    <button type="button" @click="setResult('ok')"
                            :class="form.overall_result === 'ok' ? 'ring-2 ring-emerald-500 border-transparent bg-emerald-50/60 dark:bg-emerald-500/10' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                            class="flex flex-col items-center gap-2 px-4 py-5 border rounded-xl transition-all">
                        <CheckCircle class="w-7 h-7 text-emerald-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Everything OK</span>
                    </button>
                    <button type="button" @click="setResult('concerns')"
                            :class="form.overall_result === 'concerns' ? 'ring-2 ring-amber-500 border-transparent bg-amber-50/60 dark:bg-amber-500/10' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                            class="flex flex-col items-center gap-2 px-4 py-5 border rounded-xl transition-all">
                        <AlertTriangle class="w-7 h-7 text-amber-500" />
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Report concern</span>
                    </button>
                </div>
                <div v-else class="flex items-center gap-2">
                    <component :is="inspection.overall_result === 'concerns' ? AlertTriangle : CheckCircle"
                               :class="inspection.overall_result === 'concerns' ? 'text-amber-500' : 'text-emerald-500'" class="w-5 h-5" />
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ inspection.overall_result === 'concerns' ? 'Concerns reported' : 'Everything OK' }}
                    </span>
                </div>
                <p v-if="form.errors.overall_result" class="mt-2 text-xs text-red-600">{{ form.errors.overall_result }}</p>
            </div>

            <!-- Concerns -->
            <div v-if="form.overall_result === 'concerns'" :class="cardCls" class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Concerns</p>
                    <button v-if="!readOnly" type="button" @click="addFinding" class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <Plus class="w-3.5 h-3.5" /> Add
                    </button>
                </div>

                <div v-if="readOnly" class="space-y-2">
                    <div v-for="(f, i) in inspection.findings" :key="i" class="flex items-start gap-2 p-3 border border-gray-100 dark:border-gray-800 rounded-lg">
                        <span class="shrink-0 text-[11px] font-medium px-1.5 py-0.5 rounded" :class="severityCls[f.severity]">{{ cap(f.severity) }}</span>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white">{{ cap(f.category) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ f.description }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="(f, i) in form.findings" :key="i" class="p-3 border border-gray-100 dark:border-gray-800 rounded-lg space-y-2">
                        <div class="flex items-center gap-2">
                            <select v-model="f.category" :class="selectCls" class="flex-1">
                                <option v-for="c in categories" :key="c" :value="c">{{ cap(c) }}</option>
                            </select>
                            <select v-model="f.severity" :class="selectCls">
                                <option v-for="s in severities" :key="s" :value="s">{{ cap(s) }}</option>
                            </select>
                            <button type="button" @click="removeFinding(i)" class="shrink-0 p-2 text-gray-400 hover:text-red-500 transition-colors">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                        <textarea v-model="f.description" rows="2" placeholder="Describe the concern…"
                                  class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                    <p v-if="form.findings.length === 0" class="text-xs text-gray-400 text-center py-2">Add at least one concern.</p>
                </div>
            </div>

            <!-- Photos -->
            <div :class="cardCls" class="p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Photos</p>
                <div v-if="visiblePhotos.length || newPreviews.length" class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-3">
                    <div v-for="p in visiblePhotos" :key="p.path" class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                        <img :src="p.url" class="w-full h-full object-cover" />
                        <button v-if="!readOnly" type="button" @click="removeExistingPhoto(p.path)"
                                class="absolute top-1 right-1 p-1 rounded-md bg-black/50 text-white hover:bg-black/70"><X class="w-3 h-3" /></button>
                    </div>
                    <div v-for="(p, i) in newPreviews" :key="p.url" class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 ring-2 ring-emerald-400">
                        <img :src="p.url" class="w-full h-full object-cover" />
                        <button type="button" @click="removeNewPhoto(i)"
                                class="absolute top-1 right-1 p-1 rounded-md bg-black/50 text-white hover:bg-black/70"><X class="w-3 h-3" /></button>
                    </div>
                </div>

                <label v-if="!readOnly"
                       @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop"
                       :class="dragging ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800/50' : 'border-gray-300 dark:border-gray-700'"
                       class="flex flex-col items-center justify-center gap-1.5 py-6 border-2 border-dashed rounded-xl text-center cursor-pointer hover:border-gray-400 transition-colors">
                    <ImagePlus class="w-6 h-6 text-gray-400" />
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Drop photos here or tap to upload</span>
                    <span class="text-[11px] text-gray-400">JPG, PNG or WebP · up to 5&nbsp;MB each</span>
                    <input type="file" accept="image/*" multiple class="hidden" @change="onPickPhotos" />
                </label>
                <p v-else-if="!visiblePhotos.length" class="text-sm text-gray-400">No photos.</p>
                <p v-if="form.errors['photos.0']" class="mt-2 text-xs text-red-600">Photos must be images under 5&nbsp;MB.</p>
            </div>

            <!-- Notes -->
            <div :class="cardCls" class="p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Notes</p>
                <textarea v-if="!readOnly" v-model="form.summary" rows="3" placeholder="General notes (optional)…"
                          class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                <p v-else class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ inspection.summary || '-' }}</p>
            </div>
        </div>

        <!-- Sticky action bar -->
        <div v-if="!readOnly"
             class="sticky bottom-0 z-30 -mx-4 lg:-mx-6 mt-4 px-4 lg:px-6 py-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-t border-gray-200 dark:border-gray-800 flex items-center gap-2">
            <button @click="save" :disabled="form.processing"
                    class="px-4 py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all disabled:opacity-50">
                Save
            </button>
            <button @click="complete" :disabled="form.processing"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all disabled:opacity-50">
                <ClipboardCheck class="w-4 h-4" /> Mark inspection completed
            </button>
        </div>
    </div>
</template>
