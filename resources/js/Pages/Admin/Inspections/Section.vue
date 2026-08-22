<script setup>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ChecklistSection from '@/Components/Inspections/ChecklistSection.vue'
import { ArrowLeft, Building2, ClipboardCheck, Check, Loader2, AlertTriangle } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({ section: Object })

const readOnly = computed(() => props.section.status === 'completed')

const sections = reactive(
    props.section.groups.map(g => ({
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
    router.post(route('manage.inspections.section.update', props.section.id),
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
    router.post(route('manage.inspections.section.complete', props.section.id), { results: resultsPayload() })
}

const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'
</script>

<template>
    <Head :title="`Inspection · ${section.title}`" />

    <div class="p-4 lg:p-6" :class="!readOnly ? 'pb-24 lg:pb-6' : ''">

        <!-- ── Fixed topbar ── -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-5 flex items-center justify-between gap-3 flex-wrap bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 min-w-0">
                <Link :href="route('manage.inspections.round', section.round_id)"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ section.title }}</h1>
                        <span v-if="readOnly" class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium"
                              :class="section.result === 'fail' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'">
                            {{ section.result === 'fail' ? 'Fail' : 'Pass' }}
                        </span>
                        <span v-else class="shrink-0 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse" /> In progress
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate flex items-center gap-1.5">
                        <Building2 class="w-3 h-3" /> {{ section.building_name }}
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
                    <span v-if="failCount > 0" class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
                        <AlertTriangle class="w-3 h-3" /> {{ failCount }} fail{{ failCount !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full rounded-full bg-gray-900 dark:bg-white transition-all"
                         :style="{ width: (total ? answered / total * 100 : 0) + '%' }" />
                </div>
            </div>

            <ChecklistSection
                v-for="s in sections"
                :key="s.key"
                :section="s"
                :read-only="readOnly" />
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
    </div>
</template>
