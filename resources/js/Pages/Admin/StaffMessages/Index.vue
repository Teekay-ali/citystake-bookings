<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Send, Plus, X, Users, User, Inbox, Clock, MessageSquare, ArrowLeft } from 'lucide-vue-next'
import { ref, computed, nextTick, watch } from 'vue'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    inbox:        Array,
    sent:         Array,
    staffUsers:   Array,
    roles:        Array,
    activeThread: Object,
    filters:      Object,
})

const tab      = ref(props.filters?.tab ?? 'inbox')
const showForm = ref(false)
const threadEl = ref(null)

const currentList = computed(() => tab.value === 'inbox' ? props.inbox : props.sent)
const inboxUnread = computed(() => props.inbox.filter(m => !m.is_read).length)

// Original message + replies as one ordered bubble list
const threadMessages = computed(() => {
    if (!props.activeThread) return []
    return [
        {
            id: 'root',
            body: props.activeThread.body,
            sender: props.activeThread.sender,
            created_at: props.activeThread.created_at,
            is_mine: props.activeThread.is_mine,
        },
        ...props.activeThread.replies,
    ]
})

function selectThread(id) {
    router.visit(route('manage.staff-messages.index', { thread: id, tab: tab.value }), { preserveScroll: false })
}

function closeThread() {
    router.visit(route('manage.staff-messages.index', { tab: tab.value }))
}

const composeForm = useForm({
    subject: '', body: '', recipient_type: 'user', recipient_id: '', recipient_role: '',
})

function sendCompose() {
    composeForm.post(route('manage.staff-messages.store'), {
        preserveScroll: true,
        onSuccess: () => { composeForm.reset(); showForm.value = false },
    })
}

const replyForm = useForm({ body: '' })

function sendReply() {
    if (!props.activeThread || !replyForm.body.trim()) return
    replyForm.post(route('manage.staff-messages.reply', props.activeThread.id), {
        preserveScroll: true,
        onSuccess: () => { replyForm.reset('body'); scrollThread() },
    })
}

function scrollThread() {
    nextTick(() => { if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight })
}
watch(() => props.activeThread, () => scrollThread(), { immediate: true })

function formatTime(iso) {
    return new Date(iso).toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}
function timeAgo(iso) {
    const diff = Math.floor((Date.now() - new Date(iso)) / 1000)
    if (diff < 60)    return 'just now'
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
}
function prettyRole(r) {
    return r.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const inputClass = 'w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all'
const labelClass = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5'
</script>

<template>
    <Head title="Messages" />

    <div class="flex h-[calc(100vh-64px)] overflow-hidden">

        <!-- ── LEFT: list ── -->
        <div class="w-full md:w-80 lg:w-96 flex-shrink-0 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex flex-col"
             :class="activeThread ? 'hidden md:flex' : 'flex'">

            <!-- Header -->
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white tracking-tight">Messages</h1>
                    <p class="text-xs text-gray-400 mt-0.5">Internal staff communications</p>
                </div>
                <button @click="showForm = true"
                        class="p-2 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors" title="New message">
                    <Plus class="w-4 h-4" />
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-100 dark:border-gray-800">
                <button @click="tab = 'inbox'"
                        :class="tab === 'inbox' ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-all">
                    <Inbox class="w-3.5 h-3.5" /> Inbox
                    <span v-if="inboxUnread" class="bg-blue-500 text-white text-[10px] font-bold min-w-4 h-4 px-1 rounded-full flex items-center justify-center">{{ inboxUnread }}</span>
                </button>
                <button @click="tab = 'sent'"
                        :class="tab === 'sent' ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-all">
                    <Clock class="w-3.5 h-3.5" /> Sent
                </button>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                <div v-if="currentList.length === 0" class="flex flex-col items-center justify-center py-20 px-6 text-center">
                    <MessageSquare class="w-9 h-9 text-gray-200 dark:text-gray-700 mb-3" />
                    <p class="text-sm text-gray-400">{{ tab === 'inbox' ? 'Your inbox is empty.' : 'No sent messages yet.' }}</p>
                </div>

                <button v-for="m in currentList" :key="m.id"
                        @click="selectThread(m.id)"
                        class="w-full text-left flex items-start gap-3 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900"
                        :class="String(activeThread?.id) === String(m.id) ? 'bg-gray-100 dark:bg-gray-900 border-l-2 border-l-gray-900 dark:border-l-white' : ''">
                    <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <Users v-if="m.broadcast_role" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                        <User v-else class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <span v-if="!m.is_read && tab === 'inbox'" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0" />
                            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ m.subject }}</span>
                            <span class="ml-auto text-[10px] text-gray-400 flex-shrink-0">{{ timeAgo(m.created_at) }}</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                            <span v-if="tab === 'inbox'">From {{ m.sender.name }}</span>
                            <span v-else-if="m.broadcast_role">To all {{ prettyRole(m.broadcast_role) }}</span>
                            <span v-else>To {{ m.recipients.map(r => r.name).join(', ') }}</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ m.body }}</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- ── RIGHT: thread ── -->
        <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900 min-w-0"
             :class="activeThread ? 'flex' : 'hidden md:flex'">

            <div v-if="!activeThread" class="flex-1 flex flex-col items-center justify-center text-center px-6">
                <MessageSquare class="w-12 h-12 text-gray-200 dark:text-gray-700 mb-4" />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No conversation selected</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Choose a message from the list</p>
            </div>

            <template v-else>
                <!-- Thread header -->
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex items-center gap-3 flex-shrink-0">
                    <button @click="closeThread" class="md:hidden p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <ArrowLeft class="w-4 h-4 text-gray-500" />
                    </button>
                    <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <Users v-if="activeThread.broadcast_role" class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                        <User v-else class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ activeThread.subject ?? '(no subject)' }}</p>
                        <p class="text-xs text-gray-400 truncate">
                            <span>From {{ activeThread.sender.name }}</span>
                            <span v-if="activeThread.broadcast_role"> · Broadcast to all {{ prettyRole(activeThread.broadcast_role) }}</span>
                            <span v-else-if="activeThread.recipients.length"> · To {{ activeThread.recipients.map(r => r.name).join(', ') }}</span>
                        </p>
                    </div>
                </div>

                <!-- Bubbles -->
                <div ref="threadEl" class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                    <div v-for="msg in threadMessages" :key="msg.id" class="flex" :class="msg.is_mine ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[72%]">
                            <p class="text-[10px] text-gray-400 mb-1 px-1" :class="msg.is_mine ? 'text-right' : 'text-left'">
                                {{ msg.is_mine ? 'You' : msg.sender.name }} · {{ formatTime(msg.created_at) }}
                            </p>
                            <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed whitespace-pre-line"
                                 :class="msg.is_mine
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
                        <textarea v-model="replyForm.body" rows="2" placeholder="Type a reply..."
                                  @keydown.enter.exact.prevent="sendReply"
                                  class="flex-1 resize-none px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                        <button @click="sendReply" :disabled="replyForm.processing || !replyForm.body.trim()"
                                class="p-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex-shrink-0">
                            <Send class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="replyForm.errors.body" class="text-xs text-red-500 mt-1.5">{{ replyForm.errors.body }}</p>
                    <p class="text-[10px] text-gray-400 mt-1.5">Press Enter to send</p>
                </div>
            </template>
        </div>

        <!-- ── Compose modal ── -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showForm = false">
            <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">New Message</h2>
                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label :class="labelClass">Send to</label>
                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <button @click="composeForm.recipient_type = 'user'"
                                    :class="composeForm.recipient_type === 'user' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium transition-colors">
                                <User class="w-3.5 h-3.5" /> Individual
                            </button>
                            <button @click="composeForm.recipient_type = 'role'"
                                    :class="composeForm.recipient_type === 'role' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium transition-colors border-l border-gray-200 dark:border-gray-800">
                                <Users class="w-3.5 h-3.5" /> Role broadcast
                            </button>
                        </div>
                    </div>

                    <div v-if="composeForm.recipient_type === 'user'">
                        <label :class="labelClass">Recipient</label>
                        <select v-model="composeForm.recipient_id" :class="inputClass">
                            <option value="">Select staff member...</option>
                            <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <p v-if="composeForm.errors.recipient_id" class="text-red-500 text-xs mt-1">{{ composeForm.errors.recipient_id }}</p>
                    </div>
                    <div v-else>
                        <label :class="labelClass">Role</label>
                        <select v-model="composeForm.recipient_role" :class="inputClass">
                            <option value="">Select role...</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ prettyRole(r) }}</option>
                        </select>
                        <p v-if="composeForm.errors.recipient_role" class="text-red-500 text-xs mt-1">{{ composeForm.errors.recipient_role }}</p>
                    </div>

                    <div>
                        <label :class="labelClass">Subject <span class="text-gray-400">(optional)</span></label>
                        <input v-model="composeForm.subject" type="text" :class="inputClass" placeholder="e.g. Reminder: Checkout procedure" />
                    </div>
                    <div>
                        <label :class="labelClass">Message</label>
                        <textarea v-model="composeForm.body" rows="4" :class="inputClass" placeholder="Write your message..." />
                        <p v-if="composeForm.errors.body" class="text-red-500 text-xs mt-1">{{ composeForm.errors.body }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="sendCompose" :disabled="composeForm.processing"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-colors">
                            <Send class="w-3.5 h-3.5" /> {{ composeForm.processing ? 'Sending...' : 'Send' }}
                        </button>
                        <button @click="showForm = false" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
