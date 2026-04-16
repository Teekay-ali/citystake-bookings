<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'

const page = usePage()
const open = ref(false)
const notifications = ref([])
const unreadCount = ref(page.props.unreadNotifications ?? 0)
const loading = ref(false)
let pollInterval = null

const icons = {
    booking:           '🏠',
    booking_cancelled: '❌',
    late_checkout:     '🕐',
    procurement:       '🛒',
    maintenance:       '🔧',
    complaint:         '⚠️',
    task:              '✅',
}

const props = defineProps({
    dropdownDirection: {
        type: String,
        default: 'down' // 'up' or 'down'
    }
})
async function fetchNotifications() {
    loading.value = true
    try {
        const res = await fetch(route('manage.notifications.index'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        const data = await res.json()
        notifications.value = data.data
    } finally {
        loading.value = false
    }
}

async function fetchUnreadCount() {
    try {
        const res = await fetch(route('manage.notifications.unread-count'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        const data = await res.json()
        unreadCount.value = data.count
    } catch {}
}

async function markRead(notification) {
    if (!notification.read_at) {
        await fetch(route('manage.notifications.read', notification.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
                    ? decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)[1])
                    : '',
                'Accept': 'application/json',
            }
        })
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
    open.value = false
    window.location.href = notification.data.url
}

async function markAllRead() {
    await fetch(route('manage.notifications.read-all'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
                ? decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)[1])
                : '',
            'Accept': 'application/json',
        }
    })
    notifications.value.forEach(n => n.read_at = n.read_at ?? new Date().toISOString())
    unreadCount.value = 0
}

function toggleOpen() {
    open.value = !open.value
    if (open.value) fetchNotifications()
}

function handleClickOutside(e) {
    if (!e.target.closest('[data-notification-bell]')) {
        open.value = false
    }
}

function formatTime(dateStr) {
    const date = new Date(dateStr)
    const diff = Math.floor((Date.now() - date) / 1000)
    if (diff < 60) return 'just now'
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return date.toLocaleDateString('en-NG', { day: 'numeric', month: 'short' })
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    // Poll every 45 seconds
    pollInterval = setInterval(fetchUnreadCount, 45000)
    // Also refresh on tab focus
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) fetchUnreadCount()
    })
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    clearInterval(pollInterval)
})
</script>

<template>
    <div class="relative" data-notification-bell>
        <!-- Bell button -->
        <button
            @click="toggleOpen"
            class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            aria-label="Notifications"
        >
            <Bell class="w-5 h-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 min-w-[16px] h-4 px-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="open"
                :class="props.dropdownDirection === 'up'
                    ? 'fixed bottom-16 left-2 right-2 sm:left-auto sm:right-auto sm:w-80'
                    : 'absolute right-0 top-full mt-2 w-80'"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-[100] overflow-hidden"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</span>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="py-8 flex items-center justify-center">
                    <div class="w-5 h-5 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                </div>

                <!-- Empty -->
                <div v-else-if="notifications.length === 0" class="py-10 text-center">
                    <Bell class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>

                <!-- List -->
                <div v-else class="max-h-96 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-800">
                    <button
                        v-for="n in notifications"
                        :key="n.id"
                        @click="markRead(n)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors flex items-start gap-3"
                        :class="{ 'bg-blue-50/60 dark:bg-blue-900/10': !n.read_at }"
                    >
                        <span class="text-lg flex-shrink-0 mt-0.5">{{ icons[n.data.icon] ?? '🔔' }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ n.data.title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                {{ n.data.message }}
                            </p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-600 mt-1">
                                {{ formatTime(n.created_at) }}
                            </p>
                        </div>
                        <span
                            v-if="!n.read_at"
                            class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
