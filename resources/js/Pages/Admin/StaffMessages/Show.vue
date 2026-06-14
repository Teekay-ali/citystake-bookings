<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Send, Users } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    staffMessage: Object,
    authId:       Number,
})

const replyForm = useForm({ body: '' })

function sendReply() {
    replyForm.post(route('manage.staff-messages.reply', props.staffMessage.id), {
        onSuccess: () => replyForm.reset(),
    })
}

function formatTime(iso) {
    return new Date(iso).toLocaleString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

const inputClass = 'w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all'
</script>

<template>
    <Head :title="staffMessage.subject ?? 'Message Thread'" />

    <div class="p-6 lg:p-8">
        <div class="max-w-3xl">

            <!-- Back -->
            <Link :href="route('manage.staff-messages.index')"
                  class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to Messages
            </Link>

            <!-- Thread header -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-4">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
                    {{ staffMessage.subject ?? '(no subject)' }}
                </h1>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>From <strong class="text-gray-700 dark:text-gray-300">{{ staffMessage.sender.name }}</strong></span>
                    <span v-if="staffMessage.broadcast_role" class="inline-flex items-center gap-1">
                        <Users class="w-3 h-3" />
                        Broadcast to all {{ staffMessage.broadcast_role }}s
                    </span>
                    <span v-else>
                        To <strong class="text-gray-700 dark:text-gray-300">{{ staffMessage.recipients.map(r => r.name).join(', ') }}</strong>
                    </span>
                    <span>{{ formatTime(staffMessage.created_at) }}</span>
                </div>
            </div>

            <!-- Original message -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-3">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ staffMessage.body }}</p>
            </div>

            <!-- Replies -->
            <div v-if="staffMessage.replies.length" class="space-y-3 mb-4 pl-4 border-l-2 border-gray-100 dark:border-gray-800">
                <div v-for="reply in staffMessage.replies" :key="reply.id"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4"
                     :class="reply.is_mine ? 'border-gray-300 dark:border-gray-700' : ''">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ reply.sender.name }}
                            <span v-if="reply.is_mine" class="text-gray-400 font-normal">(you)</span>
                        </span>
                        <span class="text-xs text-gray-400">{{ formatTime(reply.created_at) }}</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ reply.body }}</p>
                </div>
            </div>

            <!-- Reply box -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <h3 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Reply</h3>
                <textarea v-model="replyForm.body" rows="4" :class="inputClass"
                          placeholder="Write your reply..." class="mb-3" />
                <p v-if="replyForm.errors.body" class="text-red-500 text-xs mb-2">{{ replyForm.errors.body }}</p>
                <button @click="sendReply" :disabled="replyForm.processing"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-colors">
                    <Send class="w-3.5 h-3.5" />
                    {{ replyForm.processing ? 'Sending...' : 'Send Reply' }}
                </button>
            </div>

        </div>
    </div>
</template>
