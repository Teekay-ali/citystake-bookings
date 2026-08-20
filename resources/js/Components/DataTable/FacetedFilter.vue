<script setup>
/**
 * Faceted multi-select filter — the shadcn "Status / Priority" dropdown.
 * Renders a dashed pill that shows the active selections as badges, opening a
 * checkbox list with optional per-option counts. Emits an array via v-model;
 * the parent decides how that array maps onto its server query.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Check, PlusCircle } from 'lucide-vue-next'

const props = defineProps({
    label:      { type: String, required: true },
    // [{ value, label, count? }]
    options:    { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const root = ref(null)

const selected = computed(() => new Set(props.modelValue))
const selectedOptions = computed(() => props.options.filter(o => selected.value.has(o.value)))

function toggle(value) {
    const next = new Set(props.modelValue)
    next.has(value) ? next.delete(value) : next.add(value)
    emit('update:modelValue', [...next])
}

function clear() {
    emit('update:modelValue', [])
}

function onClickOutside(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            @click="open = !open"
            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
            <PlusCircle class="w-3.5 h-3.5 text-gray-400" />
            {{ label }}
            <template v-if="selectedOptions.length">
                <span class="w-px h-4 bg-gray-200 dark:bg-gray-700" />
                <span v-if="selectedOptions.length > 2"
                      class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300">
                    {{ selectedOptions.length }} selected
                </span>
                <span v-else class="flex items-center gap-1">
                    <span v-for="o in selectedOptions" :key="o.value"
                          class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ o.label }}
                    </span>
                </span>
            </template>
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95 -translate-y-1">
            <div v-if="open"
                 class="absolute left-0 top-full mt-1.5 z-30 w-56 max-h-72 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl shadow-gray-200/60 dark:shadow-none py-1">
                <button
                    v-for="o in options"
                    :key="o.value"
                    type="button"
                    @click="toggle(o.value)"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                    <span :class="[
                        'w-4 h-4 rounded flex items-center justify-center border transition-colors shrink-0',
                        selected.has(o.value)
                            ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white'
                            : 'border-gray-300 dark:border-gray-600'
                    ]">
                        <Check v-if="selected.has(o.value)" class="w-3 h-3 text-white dark:text-gray-900" />
                    </span>
                    <span class="flex-1 text-left truncate">{{ o.label }}</span>
                    <span v-if="o.count != null" class="text-xs tabular-nums text-gray-400">{{ o.count }}</span>
                </button>

                <template v-if="selectedOptions.length">
                    <div class="my-1 border-t border-gray-100 dark:border-gray-800" />
                    <button
                        type="button"
                        @click="clear"
                        class="w-full px-3 py-2 text-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Clear filter
                    </button>
                </template>
            </div>
        </Transition>
    </div>
</template>
