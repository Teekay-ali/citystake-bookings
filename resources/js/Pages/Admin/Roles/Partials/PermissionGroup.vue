<script setup>
import { computed } from 'vue'
import { Check, Minus } from 'lucide-vue-next'

const props = defineProps({
    domain:   String,
    perms:    Array,          // permission names in this domain
    selected: Array,          // all currently-selected permission names
    disabled: Boolean,
})
const emit = defineEmits(['toggle', 'toggle-all'])

const count = computed(() => props.perms.filter(p => props.selected.includes(p)).length)
const allOn  = computed(() => props.perms.length > 0 && count.value === props.perms.length)
const someOn = computed(() => count.value > 0 && !allOn.value)

const fmt = (p) => p.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
</script>

<template>
    <div class="border border-gray-200/80 dark:border-gray-800 rounded-xl overflow-hidden">
        <!-- Domain header with tri-state select-all -->
        <div class="flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-800/50">
            <button type="button" :disabled="disabled" @click="emit('toggle-all')"
                    class="flex items-center gap-2 text-xs font-semibold text-gray-700 dark:text-gray-300 disabled:opacity-60 disabled:cursor-not-allowed">
                <span :class="(allOn || someOn) ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white' : 'border-gray-300 dark:border-gray-600'"
                      class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-all">
                    <Check v-if="allOn" class="w-2.5 h-2.5 text-white dark:text-gray-900" />
                    <Minus v-else-if="someOn" class="w-2.5 h-2.5 text-white dark:text-gray-900" />
                </span>
                {{ domain }}
            </button>
            <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums">{{ count }}/{{ perms.length }}</span>
        </div>

        <!-- Permission toggles -->
        <div class="grid grid-cols-2 sm:grid-cols-3 divide-x divide-y divide-gray-100 dark:divide-gray-800">
            <label v-for="perm in perms" :key="perm"
                   class="flex items-center gap-2 px-3 py-2 transition-colors"
                   :class="disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50'">
                <span :class="selected.includes(perm) ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white' : 'border-gray-300 dark:border-gray-600'"
                      class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-all">
                    <Check v-if="selected.includes(perm)" class="w-2.5 h-2.5 text-white dark:text-gray-900" />
                </span>
                <input type="checkbox" class="sr-only" :checked="selected.includes(perm)" :disabled="disabled"
                       @change="emit('toggle', perm)" />
                <span class="text-xs text-gray-600 dark:text-gray-400 leading-tight">{{ fmt(perm) }}</span>
            </label>
        </div>
    </div>
</template>
