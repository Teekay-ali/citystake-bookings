<script setup>
import { computed } from 'vue'
import { Minus, Plus } from 'lucide-vue-next'

const props = defineProps({
    modelValue: [Number, String],
    min:   { type: Number, default: null },
    max:   { type: Number, default: null },
    step:  { type: Number, default: 1 },
    prefix: { type: String, default: '' },
    placeholder: { type: String, default: '0' },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const num = computed(() => Number(props.modelValue) || 0)

const atMin = computed(() => props.min !== null && num.value <= props.min)
const atMax = computed(() => props.max !== null && num.value >= props.max)

function clamp(v) {
    if (props.min !== null) v = Math.max(props.min, v)
    if (props.max !== null) v = Math.min(props.max, v)
    return v
}
function bump(dir) {
    if (props.disabled) return
    const base = props.modelValue === '' || props.modelValue == null
        ? (props.min ?? 0)
        : num.value
    emit('update:modelValue', clamp(base + dir * props.step))
}
function onInput(e) {
    const raw = e.target.value
    emit('update:modelValue', raw === '' ? '' : Number(raw))
}
</script>

<template>
    <div :class="['flex items-stretch rounded-lg border overflow-hidden bg-white dark:bg-gray-950 focus-within:ring-2 transition-all',
            invalid ? 'border-red-300 focus-within:ring-red-500'
                    : 'border-gray-200 dark:border-gray-800 focus-within:ring-gray-900 dark:focus-within:ring-white',
            disabled ? 'opacity-50 pointer-events-none' : '']">
        <button type="button" @click="bump(-1)" @mousedown.prevent :disabled="atMin"
                class="grid place-items-center w-9 shrink-0 select-none text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:hover:bg-transparent transition-colors border-r border-gray-200 dark:border-gray-800">
            <Minus class="w-3.5 h-3.5" />
        </button>

        <div class="relative flex-1 min-w-0">
            <span v-if="prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">{{ prefix }}</span>
            <input :value="modelValue" @input="onInput" type="number"
                   :min="min ?? undefined" :max="max ?? undefined" :step="step" :placeholder="placeholder"
                   :class="['w-full py-2 pr-2 bg-transparent text-sm text-gray-900 dark:text-white text-center tabular-nums focus:outline-none', prefix ? 'pl-7' : 'pl-2']" />
        </div>

        <button type="button" @click="bump(1)" @mousedown.prevent :disabled="atMax"
                class="grid place-items-center w-9 shrink-0 select-none text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:hover:bg-transparent transition-colors border-l border-gray-200 dark:border-gray-800">
            <Plus class="w-3.5 h-3.5" />
        </button>
    </div>
</template>
