<script setup>
/**
 * Clickable column header — cycles a column through desc → asc → (its icon
 * reflects the active direction). Sorting is server-driven: this only reports
 * the desired sort_by/sort_order; the parent pushes it onto the Inertia query.
 */
import { computed } from 'vue'
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-vue-next'

const props = defineProps({
    label:     { type: String, required: true },
    field:     { type: String, required: true },
    sortBy:    { type: String, default: '' },
    sortOrder: { type: String, default: 'desc' },
    align:     { type: String, default: 'left' }, // left | right
})
const emit = defineEmits(['sort'])

const active = computed(() => props.sortBy === props.field)

function onClick() {
    // First click on a fresh column sorts descending; clicking the active
    // column flips the direction.
    const order = active.value && props.sortOrder === 'desc' ? 'asc' : 'desc'
    emit('sort', { sort_by: props.field, sort_order: order })
}
</script>

<template>
    <button
        type="button"
        @click="onClick"
        :class="align === 'right' ? 'flex-row-reverse' : ''"
        class="group inline-flex items-center gap-1.5 font-medium hover:text-gray-900 dark:hover:text-white transition-colors">
        <span :class="active ? 'text-gray-900 dark:text-white' : ''">{{ label }}</span>
        <ChevronUp   v-if="active && sortOrder === 'asc'"  class="w-3.5 h-3.5 text-gray-900 dark:text-white" />
        <ChevronDown v-else-if="active"                    class="w-3.5 h-3.5 text-gray-900 dark:text-white" />
        <ChevronsUpDown v-else class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 group-hover:text-gray-400" />
    </button>
</template>
