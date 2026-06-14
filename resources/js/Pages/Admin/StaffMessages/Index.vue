<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Send, Plus, X, Users, User, Inbox, Clock } from 'lucide-vue-next'
import { ref, computed } from 'vue'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    inbox:      Array,
    sent:       Array,
    staffUsers: Array,
    roles:      Array,
})

const tab       = ref('inbox')
const showForm  = ref(false)

const form = useForm({
    subject:        '',
    body:           '',
    recipient_type: 'user',
    recipient_id:   '',
    recipient_role: '',
})

function submit() {
    form.post(route('manage.staff-messages.store'), {
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}

function openThread(id) {
    router.visit(route('manage.staff-messages.show', id))
}

function formatTime(iso) {
    const date = new Date(iso)
    const diff = Math.floor((Date.now() - date) / 1000)
    if (diff < 60)    return 'just now'
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

const currentList = computed(() => tab.value === 'inbox' ? props.inbox : props.sent)

const inputClass = 'w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all'
const labelClass = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5'
</script>

<template>
    <Head title="Messages" />

    <div class="p-6 lg:p-8">
        <div class="max-w-3xl">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Messages</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Internal staff communications</p>
                </div>
                <button @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                    <Plus class="w-4 h-4" />
                    New Message
                </button>
            </div>

            <!-- Compose form -->
            <div v-if="showForm" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">New Message</h2>
                    <button @click="showForm = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Recipient type toggle -->
                    <div>
                        <label :class="labelClass">Send to</label>
                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <button @click="form.recipient_type = 'user'"
                                    :class="form.recipient_type === 'user'
                                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                        : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium transition-colors">
                                <User class="w-3.5 h-3.5" /> Individual
                            </button>
                            <button @click="form.recipient_type = 'role'"
                                    :class="form.recipient_type === 'role'
                                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                        : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 text-sm font-medium transition-colors border-l border-gray-200 dark:border-gray-800">
                                <Users class="w-3.5 h-3.5" /> Role broadcast
                            </button>
                        </div>
                    </div>

                    <!-- Recipient select -->
                    <div v-if="form.recipient_type === 'user'">
                        <label :class="labelClass">Recipient</label>
                        <select v-model="form.recipient_id" :class="inputClass">
                            <option value="">Select staff member...</option>
                            <option v-for="u in staffUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <p v-if="form.errors.recipient_id" class="text-red-500 text-xs mt-1">{{ form.errors.recipient_id }}</p>
                    </div>

                    <div v-else>
                        <label :class="labelClass">Role</label>
                        <select v-model="form.recipient_role" :class="inputClass">
                            <option value="">Select role...</option>
                            <option v-for="r in roles" :key="r" :value="r">{{ r.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') }}</option>
                        </select>
                        <p v-if="form.errors.recipient_role" class="text-red-500 text-xs mt-1">{{ form.errors.recipient_role }}</p>
                    </div>

                    <div>
                        <label :class="labelClass">Subject <span class="text-gray-400">(optional)</span></label>
                        <input v-model="form.subject" type="text" :class="inputClass" placeholder="e.g. Reminder: Checkout procedure" />
                    </div>

                    <div>
                        <label :class="labelClass">Message</label>
                        <textarea v-model="form.body" rows="4" :class="inputClass" placeholder="Write your message..." />
                        <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button @click="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-colors">
                            <Send class="w-3.5 h-3.5" />
                            {{ form.processing ? 'Sending...' : 'Send' }}
                        </button>
                        <button @click="showForm = false"
                                class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 mb-4">
                <button @click="tab = 'inbox'"
                        :class="tab === 'inbox'
                            ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-all">
                    <Inbox class="w-3.5 h-3.5" />
                    Inbox
                    <span v-if="inbox.filter(m => !m.is_read).length"
                          class="bg-blue-500 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        {{ inbox.filter(m => !m.is_read).length }}
                    </span>
                </button>
                <button @click="tab = 'sent'"
                        :class="tab === 'sent'
                            ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-all">
                    <Clock class="w-3.5 h-3.5" />
                    Sent
                </button>
            </div>

            <!-- Message list -->
            <div v-if="currentList.length" class="space-y-2">
                <button v-for="m in currentList" :key="m.id"
                        @click="openThread(m.id)"
                        class="w-full text-left bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl px-5 py-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                        :class="{ 'border-blue-200 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-900/10': !m.is_read && tab === 'inbox' }">

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span v-if="!m.is_read && tab === 'inbox'"
                                      class="w-2 h-2 rounded-full bg-blue-500 shrink-0" />
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ m.subject }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span v-if="tab === 'inbox'">From {{ m.sender.name }}</span>
                                <span v-else>
                                    To
                                    <span v-if="m.broadcast_role">all {{ m.broadcast_role }}s</span>
                                    <span v-else>{{ m.recipients.map(r => r.name).join(', ') }}</span>
                                </span>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">{{ m.body }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-400">{{ formatTime(m.created_at) }}</p>
                            <p v-if="m.replies_count" class="text-xs text-gray-400 mt-1">{{ m.replies_count }} repl{{ m.replies_count === 1 ? 'y' : 'ies' }}</p>
                        </div>
                    </div>
                </button>
            </div>

            <div v-else class="text-center py-16 text-gray-400 text-sm">
                {{ tab === 'inbox' ? 'Your inbox is empty.' : 'No sent messages yet.' }}
            </div>

        </div>
    </div>
</template>
