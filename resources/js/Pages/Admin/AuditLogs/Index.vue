<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ShieldCheck, Search, X, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    logs:    Object,
    actions: Array,
    filters: Object,
})

const search = ref({
    action: props.filters.action ?? '',
    date:   props.filters.date   ?? '',
})

function applyFilters() {
    router.get(route('manage.audit-logs.index'), {
        action: search.value.action || undefined,
        date:   search.value.date   || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = { action: '', date: '' }
    router.get(route('manage.audit-logs.index'), {}, { preserveState: false })
}

const hasFilters = computed(() => search.value.action || search.value.date)

function formatAction(action) {
    return action.replace(/[._]/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatDate(date) {
    return new Date(date).toLocaleString('en-GB', {
        day:    '2-digit',
        month:  'short',
        year:   'numeric',
        hour:   '2-digit',
        minute: '2-digit',
    })
}

function actionColor(action) {
    if (action.includes('delete') || action.includes('removed')) return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20'
    if (action.includes('create') || action.includes('added'))   return 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20'
    if (action.includes('update') || action.includes('changed')) return 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20'
    return 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800'
}
</script>

<template>
    <ManageLayout>
        <Head title="Audit Logs" />

        <div class="px-4 sm:px-6 lg:px-8 py-8 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <ShieldCheck class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Audit Logs</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track all admin actions across the platform</p>
                    </div>
                </div>
                <span class="text-sm text-gray-400">{{ logs.total.toLocaleString() }} entries</span>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Action</label>
                        <select
                            v-model="search.action"
                            @change="applyFilters"
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                        >
                            <option value="">All actions</option>
                            <option v-for="action in actions" :key="action" :value="action">
                                {{ formatAction(action) }}
                            </option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Date</label>
                        <input
                            v-model="search.date"
                            type="date"
                            @change="applyFilters"
                            class="w-full px-3 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                        />
                    </div>

                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all"
                    >
                        <X class="w-4 h-4" /> Clear
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                <div v-if="logs.data.length === 0" class="py-16 text-center">
                    <ShieldCheck class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <p class="text-sm text-gray-400">No audit logs found</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">When</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Action</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Target</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">IP Address</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                        >
                            <td class="px-5 py-3.5 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div v-if="log.user" class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ log.user.name }}</span>
                                    <span class="text-xs text-gray-400">{{ log.user.email }}</span>
                                </div>
                                <span v-else class="text-xs text-gray-400 italic">System</span>
                            </td>
                            <td class="px-5 py-3.5">
                                    <span :class="['inline-flex px-2.5 py-1 rounded-lg text-xs font-medium', actionColor(log.action)]">
                                        {{ formatAction(log.action) }}
                                    </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span v-if="log.model_type">
                                        {{ log.model_type.split('\\').pop() }}
                                        <span v-if="log.model_id" class="text-gray-400">#{{ log.model_id }}</span>
                                    </span>
                                <span v-else class="text-gray-300 dark:text-gray-700">-</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-mono text-gray-400">
                                {{ log.ip_address ?? '-' }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-xs text-gray-400">
                        Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total.toLocaleString() }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-if="logs.prev_page_url"
                            :href="logs.prev_page_url"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 transition-colors"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <span class="px-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ logs.current_page }} / {{ logs.last_page }}
                        </span>
                        <Link
                            v-if="logs.next_page_url"
                            :href="logs.next_page_url"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 transition-colors"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
