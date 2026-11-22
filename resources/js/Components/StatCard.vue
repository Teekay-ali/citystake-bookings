<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

/**
 * Compact stat/KPI card — the house style (label / value / optional sublabel).
 * Defaults match exactly; pass an icon, a link, an accent ring, or a coloured
 * label for the richer variants.
 */
const props = defineProps({
    label:      { type: String, default: '' },
    value:      { type: [String, Number], default: null },
    sublabel:   { type: String, default: null },
    icon:       { type: [Object, Function], default: null },
    href:       { type: String, default: null },
    // Accent ring shown when `active` is true: rose|amber|blue|violet|emerald|sky.
    accent:     { type: String, default: null },
    active:     { type: Boolean, default: false },
    // Override the label colour for attention cards, e.g. 'text-rose-600 dark:text-rose-400'.
    labelClass: { type: String, default: 'text-gray-400 dark:text-gray-500' },
})

const accentRing = {
    rose:    'ring-1 ring-rose-300/60 dark:ring-rose-700/40',
    amber:   'ring-1 ring-amber-300/60 dark:ring-amber-700/40',
    blue:    'ring-1 ring-blue-300/60 dark:ring-blue-700/40',
    violet:  'ring-1 ring-violet-300/60 dark:ring-violet-700/40',
    emerald: 'ring-1 ring-emerald-300/60 dark:ring-emerald-700/40',
    sky:     'ring-1 ring-sky-300/60 dark:ring-sky-700/40',
}

const tag     = computed(() => (props.href ? Link : 'div'))
const ringCls = computed(() => (props.active && props.accent ? (accentRing[props.accent] ?? '') : ''))
</script>

<template>
    <component :is="tag" :href="href"
        class="block rounded-lg border border-gray-200/80 dark:border-gray-800 bg-white dark:bg-gray-950 px-3 py-2"
        :class="[ringCls, href ? 'hover:border-gray-300 dark:hover:border-gray-700 transition-all' : '']">
        <p class="text-[10px] uppercase tracking-wider flex items-center gap-1" :class="labelClass">
            <component :is="icon" v-if="icon" class="w-3 h-3 shrink-0" />
            <slot name="label">{{ label }}</slot>
        </p>
        <p class="text-lg font-semibold tabular-nums text-gray-900 dark:text-white">
            <slot name="value">{{ value }}</slot>
        </p>
        <p v-if="sublabel || $slots.sublabel" class="text-[10px] text-gray-400 dark:text-gray-500">
            <slot name="sublabel">{{ sublabel }}</slot>
        </p>
    </component>
</template>
