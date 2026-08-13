<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { ChevronDown, Check, Search, X } from 'lucide-vue-next'

const props = defineProps({
    modelValue:  { default: null },
    options:     { type: Array, default: () => [] }, // [{ value, label, sublabel? }]
    placeholder: { type: String, default: 'Select…' },
    clearable:   { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const open     = ref(false)
const search   = ref('')
const root     = ref(null)
const searchEl = ref(null)

const selected = computed(() => props.options.find(o => o.value == props.modelValue) ?? null)
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.options
    return props.options.filter(o => `${o.label} ${o.sublabel ?? ''}`.toLowerCase().includes(q))
})

function choose(o) { emit('update:modelValue', o.value); open.value = false }
function clear(e)  { e.stopPropagation(); emit('update:modelValue', null) }
function toggle()  { open.value = !open.value }

watch(open, (v) => { if (v) { search.value = ''; nextTick(() => searchEl.value?.focus()) } })
function onDoc(e) { if (root.value && !root.value.contains(e.target)) open.value = false }
onMounted(() => document.addEventListener('click', onDoc))
onUnmounted(() => document.removeEventListener('click', onDoc))
</script>

<template>
    <div ref="root" class="relative">
        <button type="button" @click="toggle"
                class="w-full flex items-center justify-between gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-left focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all">
            <span v-if="selected" class="min-w-0 truncate text-gray-900 dark:text-white">
                {{ selected.label }}<span v-if="selected.sublabel" class="text-gray-400"> · {{ selected.sublabel }}</span>
            </span>
            <span v-else class="text-gray-400 truncate">{{ placeholder }}</span>
            <span class="flex items-center gap-1 shrink-0">
                <X v-if="clearable && selected" @click="clear" class="w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" />
                <ChevronDown class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" />
            </span>
        </button>

        <div v-if="open"
             class="absolute z-30 mt-1 w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-lg shadow-gray-300/40 dark:shadow-black/40 overflow-hidden">
            <div class="p-2 border-b border-gray-100 dark:border-gray-800">
                <div class="relative">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                    <input ref="searchEl" v-model="search" type="text" placeholder="Search…"
                           class="w-full pl-8 pr-2 py-1.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-900 dark:focus:ring-white" />
                </div>
            </div>
            <ul class="max-h-56 overflow-y-auto py-1">
                <li v-for="o in filtered" :key="o.value">
                    <button type="button" @click="choose(o)"
                            :class="o.value == modelValue ? 'bg-gray-50 dark:bg-gray-800/60' : ''"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <span class="min-w-0 truncate">
                            <span class="font-medium text-gray-900 dark:text-white">{{ o.label }}</span>
                            <span v-if="o.sublabel" class="text-gray-400"> · {{ o.sublabel }}</span>
                        </span>
                        <Check v-if="o.value == modelValue" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                    </button>
                </li>
                <li v-if="filtered.length === 0" class="px-3 py-3 text-sm text-gray-400 text-center">No match.</li>
            </ul>
        </div>
    </div>
</template>
