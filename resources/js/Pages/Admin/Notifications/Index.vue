<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    Bell, Home, XCircle, Clock, ShoppingCart, Wrench, AlertTriangle,
    CheckSquare, Banknote, ShieldAlert, DollarSign, MessageSquare, Users,
    CheckCheck, LogOut,
} from 'lucide-vue-next'
import axios from 'axios'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    notifications: Array,
    pagination:   Object,
    filter:       String,
    unreadCount:  Number,
})

const localUnread = ref(props.unreadCount)

const icons = {
    booking: Home, booking_cancelled: XCircle, checkout: LogOut, late_checkout: Clock,
    procurement: ShoppingCart, maintenance: Wrench, complaint: AlertTriangle,
    task: CheckSquare, payment_approval: Banknote, caution_fee: Banknote, emergency_fund: ShieldAlert,
    finance: DollarSign, message: MessageSquare, staff: Users,
}

// ── Date grouping ─────────────────────────────────────────────
function bucketOf(dateStr) {
    const d = new Date(dateStr)
    const now = new Date()
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const diffDays = Math.floor((startOfToday - new Date(d.getFullYear(), d.getMonth(), d.getDate())) / 86400000)
    if (diffDays <= 0) return 'Today'
    if (diffDays === 1) return 'Yesterday'
    if (diffDays <= 7) return 'This week'
    if (diffDays <= 30) return 'This month'
    return 'Earlier'
}

const groups = computed(() => {
    const order = ['Today', 'Yesterday', 'This week', 'This month', 'Earlier']
    const map = {}
    for (const n of props.notifications) {
        const b = bucketOf(n.created_at)
        ;(map[b] ??= []).push(n)
    }
    return order.filter(k => map[k]?.length).map(k => ({ label: k, items: map[k] }))
})

function formatTime(dateStr) {
    const date = new Date(dateStr)
    const diff = Math.floor((Date.now() - date) / 1000)
    if (diff < 60) return 'just now'
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return date.toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ── Actions ───────────────────────────────────────────────────
async function open(n) {
    if (!n.read_at) {
        await axios.post(route('manage.notifications.read', n.id))
        n.read_at = new Date().toISOString()
        localUnread.value = Math.max(0, localUnread.value - 1)
    }
    if (n.data?.url) router.visit(n.data.url)
}

async function markAllRead() {
    await axios.post(route('manage.notifications.read-all'))
    props.notifications.forEach(n => { n.read_at = n.read_at ?? new Date().toISOString() })
    localUnread.value = 0
}

function setFilter(f) {
    if (f === props.filter) return
    router.get(route('manage.notifications.page'), { filter: f }, {
        only: ['notifications', 'pagination', 'filter', 'unreadCount'],
        reset: ['notifications'],
        preserveScroll: true,
        preserveState: true,
    })
}

// ── Infinite scroll ───────────────────────────────────────────
const loadingMore = ref(false)
const sentinel = ref(null)
let observer = null

function loadMore() {
    if (!props.pagination.has_more || loadingMore.value) return
    loadingMore.value = true
    router.reload({
        only: ['notifications', 'pagination'],
        data: { page: props.pagination.current + 1, filter: props.filter },
        onFinish: () => { loadingMore.value = false },
    })
}

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) loadMore()
    }, { rootMargin: '200px' })
    if (sentinel.value) observer.observe(sentinel.value)
})

onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
    <Head title="Notifications" />

    <div class="p-4 lg:p-6">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3 mb-5">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Notifications</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ localUnread > 0 ? `${localUnread} unread` : 'All caught up' }}
                    </p>
                </div>
                <button v-if="localUnread > 0" @click="markAllRead"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <CheckCheck class="w-3.5 h-3.5" /> Mark all read
                </button>
            </div>

            <!-- Filter tabs -->
            <div class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1 mb-5">
                <button v-for="f in ['all', 'unread']" :key="f" @click="setFilter(f)"
                        :class="filter === f
                            ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium capitalize transition-all">
                    {{ f }}
                </button>
            </div>

            <!-- Empty -->
            <div v-if="notifications.length === 0"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none py-16 text-center">
                <Bell class="w-9 h-9 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                </p>
            </div>

            <!-- Grouped list -->
            <div v-else class="space-y-6">
                <section v-for="group in groups" :key="group.label">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2 px-1">
                        {{ group.label }}
                    </p>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
                        <button v-for="n in group.items" :key="n.id" @click="open(n)"
                                class="w-full text-left px-4 py-3.5 flex items-start gap-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
                                :class="{ 'bg-blue-50/40 dark:bg-blue-900/10': !n.read_at }">
                            <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <component :is="icons[n.data.icon] ?? Bell" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ n.data.title }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ n.data.message }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">{{ formatTime(n.created_at) }}</p>
                            </div>
                            <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5" />
                        </button>
                    </div>
                </section>

                <!-- Infinite-scroll sentinel / load more -->
                <div ref="sentinel" class="py-2 flex justify-center">
                    <div v-if="loadingMore" class="w-5 h-5 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                    <button v-else-if="pagination.has_more" @click="loadMore"
                            class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        Load more
                    </button>
                    <span v-else class="text-xs text-gray-400 dark:text-gray-600">That's everything</span>
                </div>
            </div>
        </div>
    </div>
</template>
