<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Pencil, AlertTriangle, User, Calendar, Building2 } from 'lucide-vue-next'
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    task: Object,
})

const user = computed(() => usePage().props.auth.user)
const canManage = computed(() => user.value?.permissions?.includes('manage-tasks'))
const isAssigned = computed(() => user.value?.id === props.task.assigned_to)

const statusForm = useForm({ status: props.task.status })

function updateStatus(status) {
    statusForm.status = status
    statusForm.post(route('manage.tasks.status', props.task.id), {
        preserveScroll: true,
    })
}

function toggleSubtask(subtask) {
    router.post(route('manage.tasks.subtask.toggle', {
        task:    props.task.id,
        subtask: subtask.id,
    }), {}, { preserveScroll: true })
}

const priorityConfig = {
    low:    { label: 'Low',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    medium: { label: 'Medium', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    high:   { label: 'High',   class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    urgent: { label: 'Urgent', class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

function formatDateTime(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : '—'
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric'
    }) : '—'
}

function deleteTask() {
    if (confirm('Delete this task?')) {
        router.delete(route('manage.tasks.destroy', props.task.id))
    }
}

</script>

<template>
    <ManageLayout>
        <Head :title="task.title" />

        <div class="p-6 lg:p-8 max-w-3xl">

            <Link :href="route('manage.tasks.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Tasks
            </Link>

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span :class="[priorityConfig[task.priority].class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                            {{ priorityConfig[task.priority].label }}
                        </span>
                        <span v-if="task.is_overdue"
                              class="text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
                            <AlertTriangle class="w-3 h-3" /> Overdue
                        </span>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"
                        :class="task.status === 'completed' ? 'line-through opacity-60' : ''">
                        {{ task.title }}
                    </h1>
                </div>
                <Link v-if="canManage" :href="route('manage.tasks.edit', task.id)"
                      class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all shrink-0">
                    <Pencil class="w-4 h-4" /> Edit
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Description -->
                    <div v-if="task.description"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Description</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ task.description }}</p>
                    </div>

                    <!-- Subtasks -->
                    <div v-if="task.subtasks?.length"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Subtasks
                            </h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ task.subtasks.filter(s => s.completed).length }}/{{ task.subtasks.length }}
                            </span>
                        </div>

                        <!-- Progress bar -->
                        <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mb-4 overflow-hidden">
                            <div class="h-full bg-emerald-400 rounded-full transition-all"
                                 :style="{ width: task.completion_percent + '%' }" />
                        </div>

                        <div class="space-y-2">
                            <div v-for="subtask in task.subtasks" :key="subtask.id"
                                 class="flex items-center gap-3 py-2 border-b border-gray-50 dark:border-gray-800 last:border-0">
                                <button
                                    v-if="canManage || isAssigned"
                                    @click="toggleSubtask(subtask)"
                                    :class="subtask.completed
                                        ? 'bg-emerald-500 border-emerald-500'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-emerald-400'"
                                    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all shrink-0">
                                    <svg v-if="subtask.completed" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <div v-else
                                     :class="subtask.completed ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 dark:border-gray-600'"
                                     class="w-5 h-5 rounded border-2 flex items-center justify-center shrink-0">
                                    <svg v-if="subtask.completed" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span :class="subtask.completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'"
                                      class="text-sm flex-1">
                                    {{ subtask.title }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">

                    <!-- Status control -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Status</h3>
                        <div v-if="canManage || isAssigned" class="space-y-2">
                            <button v-for="s in ['pending','in_progress','completed','cancelled']" :key="s"
                                    @click="updateStatus(s)"
                                    :class="task.status === s
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                    : 'border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    class="w-full px-4 py-2 rounded-xl text-sm font-medium capitalize transition-all text-left">
                                {{ s.replace('_', ' ') }}
                            </button>
                        </div>
                        <div v-else
                             class="px-4 py-2 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm text-gray-600 dark:text-gray-400 capitalize">
                            {{ task.status.replace('_', ' ') }}
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-3">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Assigned To</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                <User class="w-4 h-4 text-gray-400" />
                                {{ task.assigned_to?.name ?? 'Unassigned' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Building</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                <Building2 class="w-4 h-4 text-gray-400" />
                                {{ task.building?.name }}
                            </p>
                        </div>
                        <div v-if="task.due_date">
                            <p class="text-xs text-gray-400 mb-0.5">Due Date</p>
                            <p class="text-sm font-medium flex items-center gap-1.5"
                               :class="task.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                                <Calendar class="w-4 h-4" />
                                {{ formatDate(task.due_date) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Created By</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ task.created_by?.name }}</p>
                        </div>
                        <div v-if="task.completed_at">
                            <p class="text-xs text-gray-400 mb-0.5">Completed</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ formatDateTime(task.completed_at) }}</p>
                        </div>
                    </div>

                    <!-- Delete -->
                    <div v-if="canManage">
                        <button @click="deleteTask"
                                class="w-full px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium transition-all">
                            Delete Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
