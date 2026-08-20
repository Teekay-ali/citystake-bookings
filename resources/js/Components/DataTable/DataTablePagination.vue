<script setup>
/**
 * Pagination footer mirroring the shadcn data-table: a range summary, a
 * rows-per-page selector, and first/prev/next/last controls. Server-driven —
 * page links come straight from the Laravel paginator; changing rows-per-page
 * emits so the parent can push `per_page` onto the query.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronsLeft, ChevronLeft, ChevronRight, ChevronsRight } from 'lucide-vue-next'

const props = defineProps({
    // A Laravel length-aware paginator object (with meta + links).
    paginator: { type: Object, required: true },
    perPage:   { type: [Number, String], default: 10 },
    perPageOptions: { type: Array, default: () => [10, 20, 30, 50] },
})
const emit = defineEmits(['update:perPage'])

const meta = computed(() => props.paginator)
const firstUrl = computed(() => meta.value.first_page_url)
const lastUrl  = computed(() => meta.value.last_page_url)
const prevUrl  = computed(() => meta.value.prev_page_url)
const nextUrl  = computed(() => meta.value.next_page_url)

const navBtn = 'inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all'
const navDisabled = 'opacity-40 cursor-not-allowed pointer-events-none'
</script>

<template>
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Range summary -->
        <p class="text-xs text-gray-500 dark:text-gray-400 order-2 sm:order-none">
            <span class="tabular-nums">{{ meta.total ? meta.from : 0 }}–{{ meta.to ?? 0 }}</span>
            of <span class="tabular-nums">{{ meta.total }}</span>
        </p>

        <div class="flex items-center gap-4 order-1 sm:order-none">
            <!-- Rows per page -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline">Rows per page</span>
                <select
                    :value="perPage"
                    @change="emit('update:perPage', Number($event.target.value))"
                    class="h-8 pl-2.5 pr-7 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>

            <!-- Page indicator -->
            <span class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                Page {{ meta.current_page }} of {{ meta.last_page }}
            </span>

            <!-- Nav -->
            <div class="flex items-center gap-1">
                <Link :href="firstUrl || '#'" :class="[navBtn, !prevUrl && navDisabled]" preserve-scroll aria-label="First page">
                    <ChevronsLeft class="w-4 h-4" />
                </Link>
                <Link :href="prevUrl || '#'" :class="[navBtn, !prevUrl && navDisabled]" preserve-scroll aria-label="Previous page">
                    <ChevronLeft class="w-4 h-4" />
                </Link>
                <Link :href="nextUrl || '#'" :class="[navBtn, !nextUrl && navDisabled]" preserve-scroll aria-label="Next page">
                    <ChevronRight class="w-4 h-4" />
                </Link>
                <Link :href="lastUrl || '#'" :class="[navBtn, !nextUrl && navDisabled]" preserve-scroll aria-label="Last page">
                    <ChevronsRight class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </div>
</template>
