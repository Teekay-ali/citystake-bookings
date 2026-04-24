<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { MessageSquare } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    conversations: Object,
})

function timeAgo(date) {
    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Messages" />

    <div class="p-6 lg:p-8">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Messages</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Guest conversations across all bookings</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div v-if="conversations.data.length === 0" class="px-6 py-16 text-center">
                <MessageSquare class="w-10 h-10 text-gray-200 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-sm text-gray-400 dark:text-gray-500">No guest messages yet.</p>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                <Link v-for="conv in conversations.data" :key="conv.id"
                      :href="route('manage.bookings.show', conv.id)"
                      class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">

                    <!-- Unread indicator -->
                    <div class="w-2 h-2 rounded-full flex-shrink-0"
                         :class="conv.unread_count > 0 ? 'bg-red-500' : 'bg-transparent'" />

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ conv.messages[0]?.booking?.guest_name ?? 'Guest' }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono flex-shrink-0">
                                {{ conv.booking_reference }}
                            </span>
                            <span v-if="conv.unread_count > 0"
                                  class="ml-auto flex-shrink-0 text-[10px] font-bold bg-red-500 text-white px-1.5 py-0.5 rounded-full">
                                {{ conv.unread_count }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ conv.messages[0]?.body ?? '' }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            {{ conv.building?.name }} ·
                            {{ conv.messages[0] ? timeAgo(conv.messages[0].created_at) : '' }}
                        </p>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="conversations.last_page > 1"
                 class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-1.5">
                <Link v-for="link in conversations.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                          'min-w-[32px] h-8 flex items-center justify-center px-2.5 rounded-lg text-xs transition-all',
                          link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800',
                          !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                      ]"
                      v-html="link.label" />
            </div>
        </div>
    </div>
</template>
