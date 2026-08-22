<script setup>
/**
 * A titled group of checklist items (Living Room, Kitchen, Bedroom 1, …) with a
 * live answered/total counter and a fail tally, matching the client's "(0/6)"
 * section headers.
 */
import { computed } from 'vue'
import ChecklistItem from './ChecklistItem.vue'
import { CheckCircle2, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
    section:  { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
})

const total    = computed(() => props.section.items.length)
const answered = computed(() => props.section.items.filter(i => i.result).length)
const fails    = computed(() => props.section.items.filter(i => i.result === 'fail').length)
const done     = computed(() => answered.value === total.value && total.value > 0)
</script>

<template>
    <section class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
        <header class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ section.title }}</h3>
            <div class="flex items-center gap-2">
                <span v-if="fails > 0" class="inline-flex items-center gap-1 text-[11px] font-medium px-1.5 py-0.5 rounded bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                    <AlertTriangle class="w-3 h-3" /> {{ fails }}
                </span>
                <span
                    :class="done ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'"
                    class="inline-flex items-center gap-1 text-xs font-medium tabular-nums">
                    <CheckCircle2 v-if="done" class="w-3.5 h-3.5" />
                    {{ answered }}/{{ total }}
                </span>
            </div>
        </header>

        <div class="px-4 divide-y divide-gray-100 dark:divide-gray-800">
            <ChecklistItem
                v-for="item in section.items"
                :key="item.id"
                :item="item"
                :read-only="readOnly" />
        </div>
    </section>
</template>
