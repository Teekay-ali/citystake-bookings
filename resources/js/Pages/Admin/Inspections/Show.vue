<script setup>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ChecklistSection from '@/Components/Inspections/ChecklistSection.vue'
import Modal from '@/Components/Modal.vue'
import { ArrowLeft, Building2, ClipboardCheck, User, Clock, Check, Loader2, AlertTriangle, Wrench } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({ inspection: Object })

const readOnly = computed(() => props.inspection.status === 'completed')

const sections = reactive(
    props.inspection.groups.map(g => ({
        key: g.key,
        title: g.title,
        items: g.items.map(i => reactive({
            id: i.id,
            label: i.label,
            result: i.result,
            note: i.note ?? '',
            requires_photo_on_fail: i.requires_photo_on_fail,
            photos: i.photos ?? [],
        })),
    }))
)

const allItems = computed(() => sections.flatMap(s => s.items))
const answered = computed(() => allItems.value.filter(i => i.result).length)
const total    = computed(() => allItems.value.length)
const failCount = computed(() => allItems.value.filter(i => i.result === 'fail').length)

const allAnswered   = computed(() => allItems.value.every(i => i.result))
const failsResolved = computed(() => allItems.value.every(i =>
    i.result !== 'fail' || (i.note && (!i.requires_photo_on_fail || i.photos.length))))
const canComplete = computed(() => allAnswered.value && failsResolved.value)

const completeLabel = computed(() => {
    if (!allAnswered.value) return `${total.value - answered.value} item${total.value - answered.value !== 1 ? 's' : ''} left`
    if (!failsResolved.value) return 'Resolve fails'
    return 'Mark completed'
})

const saveState = ref('idle')
let timer = null
function resultsPayload() {
    return allItems.value.map(i => ({ id: i.id, result: i.result, note: i.note }))
}
function scheduleSave() {
    if (readOnly.value) return
    clearTimeout(timer)
    timer = setTimeout(flushSave, 700)
}
function flushSave() {
    if (readOnly.value) return
    saveState.value = 'saving'
    router.post(route('manage.inspections.update', props.inspection.id),
        { results: resultsPayload() },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { saveState.value = 'saved' },
            onError:   () => { saveState.value = 'idle' },
        })
}
watch(sections, scheduleSave, { deep: true })
onBeforeUnmount(() => clearTimeout(timer))

function complete() {
    clearTimeout(timer)
    router.post(route('manage.inspections.complete', props.inspection.id), { results: resultsPayload() })
}

// ── Block unit for maintenance ──
const showBlock = ref(false)
const today = new Date().toISOString().slice(0, 10)
const blockForm = useForm({ blocked_from: today, blocked_to: today, reason: '', raise_maintenance: false })
function submitBlock() {
    blockForm.post(route('manage.inspections.block', props.inspection.id), {
        onSuccess: () => { showBlock.value = false; blockForm.reset() },
    })
}

function relTime(d) {
    if (!d) return null
    const diff = Math.floor((Date.now() - new Date(d)) / 60000)
    if (diff < 1) return 'just now'
    if (diff < 60) return `${diff}m ago`
    if (diff < 1440) return `${Math.floor(diff / 60)}h ago`
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'
</script>

<template>
    <Head :title="`Inspection · Unit ${inspection.unit_number}`" />

    <div class="p-4 lg:p-6" :class="!readOnly ? 'pb-24 lg:pb-6' : ''">

        <!-- ── Fixed topbar ── -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-5 flex items-center justify-between gap-3 flex-wrap bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="inspection.round_id ? route('manage.inspections.round', inspection.round_id) : route('manage.inspections.index')"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">Unit {{ inspection.unit_number }}</h1>
                        <span v-if="readOnly" class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium"
                              :class="inspection.overall_result === 'concerns' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'">
                            {{ inspection.overall_result === 'concerns' ? 'Fail' : 'Pass' }}
                        </span>
                        <span v-else class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" /> In progress
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-1.5">
                        <Building2 class="w-3 h-3" /> {{ inspection.building_name }} · {{ inspection.unit_type }}
                    </p>
                </div>
            </div>

            <!-- Desktop action -->
            <div v-if="!readOnly" class="hidden lg:flex items-center gap-3 shrink-0">
                <span class="text-xs text-gray-400 inline-flex items-center gap-1.5">
                    <Loader2 v-if="saveState === 'saving'" class="w-3.5 h-3.5 animate-spin" />
                    <Check v-else-if="saveState === 'saved'" class="w-3.5 h-3.5 text-emerald-500" />
                    {{ saveState === 'saving' ? 'Saving' : saveState === 'saved' ? 'Saved' : '' }}
                </span>
                <button @click="complete" :disabled="!canComplete"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg shadow-sm hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    <ClipboardCheck class="w-3.5 h-3.5" /> {{ completeLabel }}
                </button>
            </div>
        </div>

        <!-- ── Content ── -->
        <div class="max-w-3xl mx-auto space-y-4">

            <!-- Progress -->
            <div :class="card" class="p-4">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                    <span class="tabular-nums">{{ answered }} of {{ total }} checked</span>
                    <div class="flex items-center gap-3">
                        <span v-if="inspection.started_at" class="hidden sm:inline-flex items-center gap-1.5"><Clock class="w-3 h-3" /> {{ relTime(inspection.started_at) }}</span>
                        <span v-if="failCount > 0" class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
                            <AlertTriangle class="w-3 h-3" /> {{ failCount }} fail{{ failCount !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
                <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full rounded-full bg-gray-900 dark:bg-white transition-all"
                         :style="{ width: (total ? answered / total * 100 : 0) + '%' }" />
                </div>
            </div>

            <ChecklistSection
                v-for="section in sections"
                :key="section.key"
                :section="section"
                :read-only="readOnly" />

            <!-- Maintenance block (foot of the checklist) -->
            <div v-if="!readOnly" class="bg-white dark:bg-gray-900 border border-red-100 dark:border-red-900/40 rounded-2xl p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Needs repair?</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Block this unit for maintenance — it won't be bookable for the dates you set.</p>
                </div>
                <button @click="showBlock = true"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-all">
                    <Wrench class="w-3.5 h-3.5" /> Block unit
                </button>
            </div>
        </div>

        <!-- ── Mobile sticky action ── -->
        <div v-if="!readOnly"
             class="lg:hidden fixed bottom-0 inset-x-0 z-30 px-4 py-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-t border-gray-200 dark:border-gray-800 flex items-center gap-3">
            <span class="text-xs text-gray-400 inline-flex items-center gap-1.5 shrink-0 w-14">
                <Loader2 v-if="saveState === 'saving'" class="w-3.5 h-3.5 animate-spin" />
                <Check v-else-if="saveState === 'saved'" class="w-3.5 h-3.5 text-emerald-500" />
                {{ saveState === 'saving' ? 'Saving' : saveState === 'saved' ? 'Saved' : '' }}
            </span>
            <button @click="complete" :disabled="!canComplete"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:opacity-90 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <ClipboardCheck class="w-4 h-4" /> {{ completeLabel }}
            </button>
        </div>

        <!-- Block-unit modal -->
        <Modal :show="showBlock" @close="showBlock = false">
            <div class="p-5">
                <div class="flex items-center gap-2.5 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-500/10 flex items-center justify-center">
                        <Wrench class="w-4 h-4 text-red-500" />
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Block unit for maintenance</h2>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Unit {{ inspection.unit_number }} will be unbookable for these dates and shown on Blocked Dates.</p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">From</label>
                        <input v-model="blockForm.blocked_from" type="date"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="blockForm.errors.blocked_from" class="text-xs text-red-600 mt-1">{{ blockForm.errors.blocked_from }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">To</label>
                        <input v-model="blockForm.blocked_to" type="date" :min="blockForm.blocked_from"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="blockForm.errors.blocked_to" class="text-xs text-red-600 mt-1">{{ blockForm.errors.blocked_to }}</p>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reason</label>
                    <textarea v-model="blockForm.reason" rows="2" placeholder="What needs fixing?"
                              class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    <p v-if="blockForm.errors.reason" class="text-xs text-red-600 mt-1">{{ blockForm.errors.reason }}</p>
                </div>

                <label class="mt-3 flex items-center gap-2.5 cursor-pointer">
                    <input v-model="blockForm.raise_maintenance" type="checkbox"
                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-gray-900 focus:ring-gray-900 dark:focus:ring-white" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Also raise a maintenance request</span>
                </label>

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button @click="showBlock = false" type="button"
                            class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button @click="submitBlock" :disabled="blockForm.processing"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition-all">
                        <Wrench class="w-3.5 h-3.5" /> Block unit
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
