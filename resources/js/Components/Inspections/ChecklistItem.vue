<script setup>
/**
 * One checklist line: a Pass / Fail / N-A segmented control, plus a note and
 * photo area that is revealed — and required — the moment the item is failed.
 * The parent owns result + note (mutated in place on the reactive `item`, then
 * autosaved); photos upload immediately, on their own, via a small request.
 */
import { ref, computed } from 'vue'
import axios from 'axios'
import { Check, X, Camera, Loader2, ImageOff } from 'lucide-vue-next'

const props = defineProps({
    item:     { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
})

const uploading = ref(false)
const showOptional = ref(false) // manual reveal for optional note/photo on a pass

const isFail = computed(() => props.item.result === 'fail')
// The evidence block shows when failing (required), when there's already
// content, or when the QC opts to add optional evidence on a pass.
const showEvidence = computed(() =>
    isFail.value || showOptional.value || !!props.item.note || (props.item.photos?.length > 0)
)
const needsPhoto = computed(() => isFail.value && props.item.requires_photo_on_fail && !(props.item.photos?.length))
const needsNote  = computed(() => isFail.value && !props.item.note)

const options = [
    { value: 'pass', label: 'Pass', on: 'bg-emerald-500 text-white border-emerald-500', off: 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' },
    { value: 'fail', label: 'Fail', on: 'bg-red-500 text-white border-red-500',         off: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10' },
    { value: 'na',   label: 'N/A',  on: 'bg-gray-500 text-white border-gray-500',        off: 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' },
]

function choose(value) {
    if (props.readOnly) return
    props.item.result = props.item.result === value ? null : value
}

async function onPickPhotos(e) {
    const files = Array.from(e.target.files || [])
    e.target.value = ''
    if (!files.length) return

    const data = new FormData()
    files.forEach(f => data.append('photos[]', f))

    uploading.value = true
    try {
        const res = await axios.post(route('manage.inspections.results.photos', props.item.id), data)
        props.item.photos = res.data.photos
    } finally {
        uploading.value = false
    }
}

async function removePhoto(path) {
    if (props.readOnly) return
    const res = await axios.delete(route('manage.inspections.results.photos.delete', props.item.id), { data: { path } })
    props.item.photos = res.data.photos
}
</script>

<template>
    <div class="py-3.5">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm text-gray-800 dark:text-gray-200 leading-snug pt-1.5">{{ item.label }}</p>

            <!-- Segmented Pass / Fail / N-A -->
            <div class="shrink-0 inline-flex rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                <button
                    v-for="(opt, i) in options"
                    :key="opt.value"
                    type="button"
                    :disabled="readOnly"
                    @click="choose(opt.value)"
                    :class="[
                        item.result === opt.value ? opt.on : (readOnly ? 'text-gray-300 dark:text-gray-700' : opt.off),
                        i > 0 ? 'border-l border-gray-200 dark:border-gray-800' : '',
                    ]"
                    class="px-3 py-1.5 text-xs font-semibold transition-colors disabled:cursor-default">
                    {{ opt.label }}
                </button>
            </div>
        </div>

        <!-- Optional-evidence prompt on a pass -->
        <button
            v-if="!readOnly && !showEvidence"
            type="button"
            @click="showOptional = true"
            class="mt-1.5 text-[11px] text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            + Add note or photo
        </button>

        <!-- Note + photo evidence -->
        <div v-if="showEvidence" class="mt-2.5 space-y-2.5">
            <div>
                <textarea
                    v-if="!readOnly"
                    v-model="item.note"
                    rows="2"
                    :placeholder="isFail ? 'What\'s wrong? (required)' : 'Add a note (optional)…'"
                    :class="needsNote ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-800'"
                    class="w-full px-3 py-2 bg-white dark:bg-gray-950 border rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                <p v-else-if="item.note" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ item.note }}</p>
            </div>

            <!-- Photos -->
            <div class="flex flex-wrap items-center gap-2">
                <div
                    v-for="p in item.photos"
                    :key="p.path"
                    class="relative w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 group">
                    <img :src="p.url" class="w-full h-full object-cover" />
                    <button
                        v-if="!readOnly"
                        type="button"
                        @click="removePhoto(p.path)"
                        class="absolute top-0.5 right-0.5 p-0.5 rounded bg-black/55 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                        <X class="w-3 h-3" />
                    </button>
                </div>

                <label
                    v-if="!readOnly"
                    :class="needsPhoto ? 'border-red-300 dark:border-red-800 text-red-500' : 'border-gray-300 dark:border-gray-700 text-gray-400 hover:border-gray-400'"
                    class="w-16 h-16 shrink-0 flex flex-col items-center justify-center gap-0.5 border-2 border-dashed rounded-lg cursor-pointer transition-colors">
                    <Loader2 v-if="uploading" class="w-4 h-4 animate-spin" />
                    <Camera v-else class="w-4 h-4" />
                    <span class="text-[9px] font-medium">{{ uploading ? '…' : 'Photo' }}</span>
                    <input type="file" accept="image/*" multiple class="hidden" @change="onPickPhotos" />
                </label>

                <p v-if="readOnly && !item.photos?.length" class="inline-flex items-center gap-1 text-xs text-gray-400">
                    <ImageOff class="w-3.5 h-3.5" /> No photo
                </p>
            </div>

            <!-- Inline requirement hints -->
            <p v-if="needsNote || needsPhoto" class="text-[11px] text-red-500">
                <Check class="w-3 h-3 inline -mt-0.5" />
                A failed item needs
                <template v-if="needsNote && needsPhoto">a note and a photo.</template>
                <template v-else-if="needsNote">a note.</template>
                <template v-else>a photo.</template>
            </p>
        </div>
    </div>
</template>
