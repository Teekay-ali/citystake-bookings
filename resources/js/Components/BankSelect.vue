<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { ChevronDown, Check, Search } from 'lucide-vue-next'
import { NIGERIAN_BANKS } from '@/data/nigerianBanks'

const props = defineProps({
    modelValue:  { type: String, default: '' },
    placeholder: { type: String, default: 'Select bank…' },
})
const emit = defineEmits(['update:modelValue'])

const open     = ref(false)
const search   = ref('')
const root     = ref(null)
const searchEl = ref(null)

// Keep any prefilled/legacy value that isn't in the list so it's never dropped.
const options = computed(() => {
    const v = props.modelValue
    return v && !NIGERIAN_BANKS.includes(v) ? [v, ...NIGERIAN_BANKS] : NIGERIAN_BANKS
})
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    return q ? options.value.filter(b => b.toLowerCase().includes(q)) : options.value
})

function choose(bank) {
    emit('update:modelValue', bank)
    open.value = false
}
function toggle() {
    open.value = !open.value
}
watch(open, (v) => {
    if (v) { search.value = ''; nextTick(() => searchEl.value?.focus()) }
})

function onDocClick(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>

<template>
    <div ref="root" class="relative">
        <button type="button" @click="toggle"
                class="w-full flex items-center justify-between gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-left focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all">
            <span :class="modelValue ? 'text-gray-900 dark:text-white' : 'text-gray-400'" class="truncate">{{ modelValue || placeholder }}</span>
            <ChevronDown class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" />
        </button>

        <div v-if="open"
             class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-lg shadow-gray-300/40 dark:shadow-black/40 overflow-hidden">
            <div class="p-2 border-b border-gray-100 dark:border-gray-800">
                <div class="relative">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input ref="searchEl" v-model="search" type="text" placeholder="Search bank…"
                           class="w-full pl-8 pr-2 py-1.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-white" />
                </div>
            </div>
            <ul class="max-h-56 overflow-y-auto py-1">
                <li v-for="bank in filtered" :key="bank">
                    <button type="button" @click="choose(bank)"
                            :class="bank === modelValue ? 'bg-gray-50 dark:bg-gray-800/60 text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <span class="truncate">{{ bank }}</span>
                        <Check v-if="bank === modelValue" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                    </button>
                </li>
                <li v-if="filtered.length === 0" class="px-3 py-3 text-sm text-gray-400 text-center">No bank matches “{{ search }}”.</li>
            </ul>
        </div>
    </div>
</template>
