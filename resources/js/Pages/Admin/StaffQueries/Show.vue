<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    query: Object,
    types: Object,
})

const resolveForm = useForm({
    resolution: props.query.resolution ?? '',
})

function submitResolve() {
    resolveForm.post(route('manage.staff-queries.resolve', props.query.id), {
        preserveScroll: true,
    })
}

function destroy() {
    if (confirm('Delete this query record permanently?')) {
        resolveForm.delete(route('manage.staff-queries.destroy', props.query.id))
    }
}

const statusConfig = {
    open:      { label: 'Open',      class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
    responded: { label: 'Responded', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    closed:    { label: 'Closed',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
}

function formatDateTime(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : '—'
}
</script>

<template>
    <ManageLayout>
        <Head :title="query.subject" />

        <div class="p-6 lg:p-8 max-w-3xl">

            <Link :href="route('manage.staff-queries.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Staff Queries
            </Link>

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span :class="[statusConfig[query.status].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                            {{ statusConfig[query.status].label }}
                        </span>
                        <span class="text-xs text-gray-400">{{ types[query.type] }}</span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ query.subject }}</h1>
                </div>
                <button @click="destroy"
                        class="p-2 border border-red-200 dark:border-red-800 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0">
                    <Trash2 class="w-4 h-4" />
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Query Details</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ query.description }}</p>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Staff Member</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ query.staff?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Issued By</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ query.issued_by?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Building</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ query.building?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Date Issued</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatDateTime(query.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Resolution -->
                    <div v-if="query.resolution"
                         class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-3">Resolution</h2>
                        <p class="text-sm text-emerald-800 dark:text-emerald-300 whitespace-pre-wrap">{{ query.resolution }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-2">
                            Closed {{ formatDateTime(query.closed_at) }}
                        </p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Close query form -->
                    <div v-if="query.status !== 'closed'"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Close Query</h3>
                        <form @submit.prevent="submitResolve" class="space-y-3">
                            <textarea v-model="resolveForm.resolution" rows="3"
                                      placeholder="Resolution or outcome..."
                                      class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                            <p v-if="resolveForm.errors.resolution" class="text-xs text-red-600">
                                {{ resolveForm.errors.resolution }}
                            </p>
                            <button type="submit" :disabled="resolveForm.processing"
                                    class="w-full px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                Mark Closed
                            </button>
                        </form>
                    </div>

                    <!-- Closed state -->
                    <div v-else
                         class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Query Closed</p>
                        <p class="text-xs text-gray-400 mt-1">{{ formatDateTime(query.closed_at) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
