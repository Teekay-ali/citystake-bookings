<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { MessageSquare, Send, User, ArrowLeft } from 'lucide-vue-next'
import { ref, nextTick, watch } from 'vue'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    conversations:      Object,
    activeConversation: Object,
    activeBookingId:    String,
})

const threadEl = ref(null)

const form = useForm({ body: '' })

function selectConversation(bookingReference) {
    router.visit(route('manage.messages.index', { booking: bookingReference }), {
        preserveScroll: false,
    })
}

function sendReply() {
    if (!props.activeConversation) return
    form.post(route('manage.bookings.messages.send', props.activeConversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body')
            scrollThread()
        },
    })
}

function scrollThread() {
    nextTick(() => {
        if (threadEl.value) {
            threadEl.value.scrollTop = threadEl.value.scrollHeight
        }
    })
}

watch(() => props.activeConversation, () => scrollThread(), { immediate: true })

function formatTime(date) {
    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    })
}

function timeAgo(date) {
    const d = new Date(date)
    const diff = Date.now() - d.getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1)  return 'just now'
    if (mins < 60) return `${mins}m ago`
    const hrs = Math.floor(mins / 60)
    if (hrs < 24)  return `${hrs}h ago`
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
}

// Guest name helper - messages[0] doesn't carry guest_name, booking does
function guestName(conv) {
    return conv.guest_name ?? 'Guest'
}
</script>

<template>
    <Head title="Messages" />

    <!-- Full-height split panel -->
    <div class="flex h-[calc(100vh-64px)] overflow-hidden">

        <!-- ── LEFT: Conversation List ── -->
        <div class="w-full md:w-80 lg:w-96 flex-shrink-0 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex flex-col"
             :class="activeConversation ? 'hidden md:flex' : 'flex'">

            <!-- Header -->
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white tracking-tight">Messages</h1>
                <p class="text-xs text-gray-400 mt-0.5">Guest conversations</p>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                <div v-if="conversations.data.length === 0" class="flex flex-col items-center justify-center py-20 px-6 text-center">
                    <MessageSquare class="w-9 h-9 text-gray-200 dark:text-gray-700 mb-3" />
                    <p class="text-sm text-gray-400">No guest messages yet.</p>
                </div>

                <button
                    v-for="conv in conversations.data"
                    :key="conv.id"
                    @click="selectConversation(conv.booking_reference)"
                    class="w-full text-left flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900"
                    :class="activeBookingId === conv.booking_reference
                        ? 'bg-gray-100 dark:bg-gray-900 border-l-2 border-l-gray-900 dark:border-l-white'
                        : ''"
                >
                    <!-- Avatar -->
                    <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <User class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <!-- Unread dot -->
                            <span v-if="conv.unread_count > 0"
                                  class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" />
                            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ guestName(conv) }}
                            </span>
                            <span class="ml-auto text-[10px] text-gray-400 flex-shrink-0">
                                {{ conv.messages[0] ? timeAgo(conv.messages[0].created_at) : '' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ conv.messages[0]?.body ?? '' }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            {{ conv.building?.name }} · {{ conv.booking_reference }}
                        </p>
                    </div>
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="conversations.last_page > 1"
                 class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-1 flex-shrink-0">
                <Link v-for="link in conversations.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                          'min-w-[28px] h-7 flex items-center justify-center px-2 rounded text-xs transition-all',
                          link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800',
                          !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                      ]"
                      v-html="link.label" />
            </div>
        </div>

        <!-- ── RIGHT: Thread Panel ── -->
        <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900 min-w-0"
             :class="activeConversation ? 'flex' : 'hidden md:flex'">

            <!-- No conversation selected -->
            <div v-if="!activeConversation" class="flex-1 flex flex-col items-center justify-center text-center px-6">
                <MessageSquare class="w-12 h-12 text-gray-200 dark:text-gray-700 mb-4" />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No conversation selected</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Choose a guest conversation from the list</p>
            </div>

            <!-- Active thread -->
            <template v-else>
                <!-- Thread header -->
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex items-center gap-3 flex-shrink-0">
                    <!-- Mobile back button -->
                    <button @click="router.visit(route('manage.messages.index'))"
                            class="md:hidden p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <ArrowLeft class="w-4 h-4 text-gray-500" />
                    </button>

                    <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <User class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ activeConversation.guest_name }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ activeConversation.booking_reference }} ·
                            {{ activeConversation.building?.name }} ·
                            {{ activeConversation.unit_type?.name }}
                        </p>
                    </div>
                    <Link :href="route('manage.bookings.show', activeConversation.booking_reference)"
                          class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex-shrink-0 transition-colors">
                        View booking →
                    </Link>
                </div>

                <!-- Messages thread -->
                <div ref="threadEl" class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                    <div v-for="msg in activeConversation.messages" :key="msg.id"
                         class="flex"
                         :class="msg.sender_type === 'staff' ? 'justify-end' : 'justify-start'">

                        <div class="max-w-[72%]"
                             :class="msg.sender_type === 'staff' ? 'items-end' : 'items-start'">
                            <!-- Sender label -->
                            <p class="text-[10px] text-gray-400 mb-1 px-1"
                               :class="msg.sender_type === 'staff' ? 'text-right' : 'text-left'">
                                {{ msg.sender_type === 'staff' ? (msg.sender?.name ?? 'Staff') : activeConversation.guest_name }}
                                · {{ formatTime(msg.created_at) }}
                            </p>
                            <!-- Bubble -->
                            <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed"
                                 :class="msg.sender_type === 'staff'
                                     ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-br-sm'
                                     : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-bl-sm'">
                                {{ msg.body }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reply box -->
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex-shrink-0">
                    <div class="flex items-end gap-3">
                        <textarea
                            v-model="form.body"
                            rows="2"
                            placeholder="Type a reply..."
                            @keydown.enter.exact.prevent="sendReply"
                            class="flex-1 resize-none px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                        />
                        <button
                            @click="sendReply"
                            :disabled="form.processing || !form.body.trim()"
                            class="p-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex-shrink-0"
                        >
                            <Send class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.body" class="text-xs text-red-500 mt-1.5">{{ form.errors.body }}</p>
                    <p class="text-[10px] text-gray-400 mt-1.5">Press Enter to send</p>
                </div>
            </template>
        </div>
    </div>
</template>
