<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { X, Zap, Wrench, TrendingUp, Shield, ChevronLeft } from 'lucide-vue-next'

const page   = usePage()
const show   = ref(false)
const entries = ref([])

// ── Carousel: one update at a time ──
const current = ref(0)
const total   = computed(() => entries.value.length)
const active  = computed(() => entries.value[current.value] ?? null)
const isLast  = computed(() => current.value >= total.value - 1)
function prev() { if (current.value > 0) current.value-- }
function next() { isLast.value ? dismiss() : current.value++ }

const typeIcons = { feature: Zap, fix: Wrench, improvement: TrendingUp, security: Shield }
const typeColors = {
    feature:     'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    fix:         'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    improvement: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    security:    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function dismiss() {
    show.value = false
    const ids = entries.value.map(e => e.id)
    await axios.post(route('manage.changelogs.mark-read'), { ids })
}

onMounted(() => {
    const unread = page.props.unreadChangelogs
    if (unread && unread.length > 0) {
        entries.value = unread
        show.value    = true
    }
})
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">

                <Transition enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                    <div v-if="show"
                         class="relative flex flex-col w-full max-w-lg max-h-[calc(100dvh-2rem)] bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800 shrink-0">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">What's New</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span v-if="total > 1">Update {{ current + 1 }} of {{ total }}</span>
                                    <span v-else>{{ total }} update since your last visit</span>
                                </p>
                            </div>
                            <button @click="dismiss"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Current update -->
                        <div v-if="active" :key="active.id" class="flex-1 min-h-0 overflow-y-auto px-6 py-4">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span :class="['inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full', typeColors[active.type]]">
                                    <component :is="typeIcons[active.type]" class="w-3 h-3" />
                                    {{ active.type }}
                                </span>
                                <span v-if="active.version" class="text-xs text-gray-400 font-mono">{{ active.version }}</span>
                                <span class="text-xs text-gray-400">{{ formatDate(active.published_at) }}</span>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ active.title }}</p>
                            <div class="rt-content mt-1" v-html="active.body" />

                            <!-- Posted by -->
                            <div v-if="active.author" class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <div class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center shrink-0">
                                    <span class="text-white dark:text-gray-900 text-[10px] font-semibold">{{ active.author.name?.charAt(0)?.toUpperCase() }}</span>
                                </div>
                                <div class="min-w-0 leading-tight">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ active.author.name }}</p>
                                    <p v-if="active.author.role" class="text-[10px] text-gray-400 dark:text-gray-500 truncate">{{ active.author.role }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer / carousel controls -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 shrink-0">
                            <div class="min-w-16">
                                <button v-if="total > 1 && current > 0" @click="prev"
                                        class="inline-flex items-center gap-1 px-2.5 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    <ChevronLeft class="w-4 h-4" /> Back
                                </button>
                            </div>

                            <div v-if="total > 1" class="flex items-center gap-1.5">
                                <button v-for="(e, i) in entries" :key="e.id" @click="current = i"
                                        :aria-label="`Go to update ${i + 1}`"
                                        :class="i === current ? 'w-4 bg-gray-900 dark:bg-white' : 'w-1.5 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400 dark:hover:bg-gray-600'"
                                        class="h-1.5 rounded-full transition-all" />
                            </div>

                            <div class="min-w-16 flex justify-end">
                                <button @click="next"
                                        class="px-5 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                                    {{ isLast ? 'Got it' : 'Next' }}
                                </button>
                            </div>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
