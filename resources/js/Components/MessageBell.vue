<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { MessageSquare } from 'lucide-vue-next'

const page         = usePage()
const unreadCount = ref(page.props.unreadStaffMessages ?? 0)
let pollInterval   = null

async function fetchUnreadCount() {
    try {
        const res = await fetch(route('manage.staff-messages.unread-count'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (res.status === 401) { clearInterval(pollInterval); return }
        const data = await res.json()
        unreadCount.value = data.count
    } catch {}
}

function goToMessages() {
    router.visit(route('manage.staff-messages.index'))
}

const onVisibilityChange = () => { if (!document.hidden) fetchUnreadCount() }

onMounted(() => {
    pollInterval = setInterval(fetchUnreadCount, 45000)
    document.addEventListener('visibilitychange', onVisibilityChange)
})

onUnmounted(() => {
    clearInterval(pollInterval)
    document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>

<template>
    <button @click="goToMessages"
            class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            aria-label="Messages">
        <MessageSquare class="w-5 h-5" />
        <span v-if="unreadCount > 0"
              class="absolute top-1 right-1 min-w-[16px] h-4 px-0.5 bg-blue-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </button>
</template>
