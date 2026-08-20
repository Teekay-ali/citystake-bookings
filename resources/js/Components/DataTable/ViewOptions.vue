<script setup>
/**
 * "View" menu — toggles column visibility, shadcn-style. Purely client-side:
 * the parent binds the visible-key array with v-model and uses it to hide
 * columns. Choices persist per table via `storageKey` (localStorage).
 */
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { SlidersHorizontal, Check } from 'lucide-vue-next'

const props = defineProps({
    // [{ key, label, locked? }] — locked columns can't be hidden.
    columns:    { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] }, // visible keys
    storageKey: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)

onMounted(() => {
    if (props.storageKey) {
        const saved = localStorage.getItem(props.storageKey)
        if (saved) {
            try {
                // Persisted state is a { key: visible } map so the stored prefs
                // survive the column set changing: a column added after the prefs
                // were saved isn't in the map and defaults to visible, rather than
                // silently disappearing. Unknown/removed keys are ignored.
                const savedMap = JSON.parse(saved)
                const next = props.columns
                    .filter(c => c.locked || savedMap[c.key] !== false)
                    .map(c => c.key)
                emit('update:modelValue', next)
            } catch { /* ignore malformed prefs */ }
        }
    }
    document.addEventListener('mousedown', onClickOutside)
})
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))

watch(() => props.modelValue, (v) => {
    if (!props.storageKey) return
    const map = {}
    props.columns.forEach(c => { map[c.key] = v.includes(c.key) })
    localStorage.setItem(props.storageKey, JSON.stringify(map))
}, { deep: true })

function isVisible(key) { return props.modelValue.includes(key) }

function toggle(col) {
    if (col.locked) return
    const next = isVisible(col.key)
        ? props.modelValue.filter(k => k !== col.key)
        : [...props.modelValue, col.key]
    emit('update:modelValue', next)
}

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false
}
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
            <SlidersHorizontal class="w-3.5 h-3.5 text-gray-400" />
            View
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95 -translate-y-1">
            <div v-if="open"
                 class="absolute right-0 top-full mt-1.5 z-30 w-48 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl shadow-gray-200/60 dark:shadow-none py-1">
                <p class="px-3 py-1.5 text-xs font-medium text-gray-400 uppercase tracking-wide">Toggle columns</p>
                <button
                    v-for="col in columns"
                    :key="col.key"
                    type="button"
                    @click="toggle(col)"
                    :disabled="col.locked"
                    :class="col.locked ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 transition-colors">
                    <span :class="[
                        'w-4 h-4 rounded flex items-center justify-center border transition-colors shrink-0',
                        isVisible(col.key)
                            ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white'
                            : 'border-gray-300 dark:border-gray-600'
                    ]">
                        <Check v-if="isVisible(col.key)" class="w-3 h-3 text-white dark:text-gray-900" />
                    </span>
                    <span class="flex-1 text-left truncate">{{ col.label }}</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
