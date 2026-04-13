<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, MapPin, User, Calendar, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
    complaint: Object,
})

const resolveForm = useForm({
    status:           props.complaint.status === 'open' ? 'in_progress' : 'resolved',
    resolution_notes: props.complaint.resolution_notes ?? '',
})

function submitResolve() {
    resolveForm.post(route('manage.complaints.resolve', props.complaint.id), {
        preserveScroll: true,
    })
}

const severityConfig = {
    low:    { label: 'Low',    class: 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400' },
    medium: { label: 'Medium', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    high:   { label: 'High',   class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    urgent: { label: 'Urgent', class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

const statusConfig = {
    open:        'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
    in_progress: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
    resolved:    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400',
}

function formatDateTime(d) {
    return new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}
</script>

<template>
    <ManageLayout>
        <Head :title="complaint.title" />

        <div class="p-6 lg:p-8 max-w-3xl">

            <Link :href="route('manage.complaints.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Complaints
            </Link>

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span :class="[severityConfig[complaint.severity].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                            {{ severityConfig[complaint.severity].label }}
                        </span>
                        <span :class="[statusConfig[complaint.status], 'text-xs font-medium px-2.5 py-1 rounded-full capitalize']">
                            {{ complaint.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ complaint.title }}</h1>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main content -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Details</h2>
                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ complaint.description }}</p>

                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <User class="w-4 h-4" />
                                <span>Reported by <span class="font-medium text-gray-900 dark:text-white">{{ complaint.submitted_by?.name }}</span></span>
                            </div>
                            <div v-if="complaint.location" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <MapPin class="w-4 h-4" />
                                <span>{{ complaint.location }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <Calendar class="w-4 h-4" />
                                <span>{{ formatDateTime(complaint.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Photos -->
                    <div v-if="complaint.photo_urls?.length"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Photos</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <a v-for="(url, i) in complaint.photo_urls" :key="i"
                               :href="url" target="_blank">
                                <img :src="url" class="w-full aspect-square object-cover rounded-xl hover:opacity-90 transition-all" />
                            </a>
                        </div>
                    </div>

                    <!-- Resolution (if resolved) -->
                    <div v-if="complaint.resolution_notes"
                         class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-3">Resolution</h2>
                        <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ complaint.resolution_notes }}</p>
                        <p v-if="complaint.resolved_by" class="text-xs text-emerald-600 dark:text-emerald-500 mt-2">
                            Resolved by {{ complaint.resolved_by?.name }} · {{ formatDateTime(complaint.resolved_at) }}
                        </p>
                    </div>
                </div>

                <!-- Sidebar actions -->
                <div class="space-y-4">
                    <div v-if="!complaint.isResolved"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Update Status</h3>

                        <form @submit.prevent="submitResolve" class="space-y-3">
                            <select v-model="resolveForm.status"
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                                <option value="in_progress">Mark In Progress</option>
                                <option value="resolved">Mark Resolved</option>
                            </select>
                            <textarea v-model="resolveForm.resolution_notes"
                                      rows="3" placeholder="Add resolution notes..."
                                      class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                            <p v-if="resolveForm.errors.resolution_notes" class="text-xs text-red-600">
                                {{ resolveForm.errors.resolution_notes }}
                            </p>
                            <button type="submit" :disabled="resolveForm.processing"
                                    class="w-full px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                {{ resolveForm.processing ? 'Saving...' : 'Update' }}
                            </button>
                        </form>
                    </div>

                    <!-- Building info -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Building</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ complaint.building?.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
