<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { X, Zap, Wrench, TrendingUp, Shield } from 'lucide-vue-next'

const page   = usePage()
const show   = ref(false)
const entries = ref([])

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
                         class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">What's New</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ entries.length }} update{{ entries.length !== 1 ? 's' : '' }} since your last visit
                                </p>
                            </div>
                            <button @click="dismiss"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Entries -->
                        <div class="overflow-y-auto max-h-[60vh] divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="entry in entries" :key="entry.id" class="px-6 py-4">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span :class="['inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full', typeColors[entry.type]]">
                                        <component :is="typeIcons[entry.type]" class="w-3 h-3" />
                                        {{ entry.type }}
                                    </span>
                                    <span v-if="entry.version" class="text-xs text-gray-400 font-mono">{{ entry.version }}</span>
                                    <span class="text-xs text-gray-400">{{ formatDate(entry.published_at) }}</span>
                                </div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ entry.title }}</p>
                                <div class="rt-content mt-1" v-html="entry.body" />
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                            <button @click="dismiss"
                                    class="px-5 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                                Got it
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
