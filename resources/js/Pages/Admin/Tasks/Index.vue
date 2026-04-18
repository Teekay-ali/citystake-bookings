<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, CheckCircle2, Clock, AlertTriangle, ChevronRight, User } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    tasks:        Object,
    buildings:    Array,
    staffMembers: Array,
    counts:       Object,
    filters:      Object,
})

const currentUser = computed(() => usePage().props.auth.user)

const buildingId = ref(props.filters.building_id || '')
const status     = ref(props.filters.status || '')
const priority   = ref(props.filters.priority || '')
const assignedTo = ref(props.filters.assigned_to || '')
const view       = ref(props.filters.view || '')

watch([buildingId, status, priority, assignedTo, view], () => {
    router.get(route('manage.tasks.index'), {
        building_id: buildingId.value || undefined,
        status:      status.value || undefined,
        priority:    priority.value || undefined,
        assigned_to: assignedTo.value || undefined,
        view:        view.value || undefined,
    }, { preserveState: true, replace: true })
})

const priorityConfig = {
    low:    { label: 'Low',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    medium: { label: 'Medium', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    high:   { label: 'High',   class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    urgent: { label: 'Urgent', class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

const statusConfig = {
    pending:     { label: 'Pending',     class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    in_progress: { label: 'In Progress', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    completed:   { label: 'Completed',   class: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' },
    cancelled:   { label: 'Cancelled',   class: 'bg-gray-100 dark:bg-gray-800 text-gray-400' },
}

function formatDate(d) {
    if (!d) return null
    return new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' })
}

function isOverdue(task) {
    return task.due_date &&
        new Date(task.due_date) < new Date() &&
        !['completed', 'cancelled'].includes(task.status)
}
</script>

<template>
    <ManageLayout>
        <Head title="Tasks" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Tasks</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Assign and track work across your team</p>
                </div>
                <Link v-if="$page.props.auth.user.permissions?.includes('manage-tasks')"
                      :href="route('manage.tasks.create')"
                      class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    New Task
                </Link>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div @click="status = 'pending'; view = ''"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 cursor-pointer transition-all hover:border-gray-300 dark:hover:border-gray-700"
                     :class="status === 'pending' ? 'ring-2 ring-gray-900 dark:ring-white' : ''">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts.pending }}</p>
                </div>
                <div @click="status = 'in_progress'; view = ''"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 cursor-pointer transition-all hover:border-gray-300 dark:hover:border-gray-700"
                     :class="status === 'in_progress' ? 'ring-2 ring-gray-900 dark:ring-white' : ''">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">In Progress</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts.in_progress }}</p>
                </div>
                <div @click="status = ''; view = ''; priority = 'urgent'"
                     class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-4 cursor-pointer transition-all">
                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Overdue</p>
                    <p class="text-2xl font-semibold text-red-700 dark:text-red-400">{{ counts.overdue }}</p>
                </div>
                <div @click="view = view === 'mine' ? '' : 'mine'; status = ''"
                     class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4 cursor-pointer transition-all"
                     :class="view === 'mine' ? 'ring-2 ring-blue-500' : ''">
                    <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Assigned to Me</p>
                    <p class="text-2xl font-semibold text-blue-700 dark:text-blue-400">{{ counts.mine }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mb-6">
                <select v-model="buildingId"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="status"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="priority"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Priority</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select v-model="assignedTo"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="">All Staff</option>
                    <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <button v-if="status || priority || assignedTo || buildingId || view"
                        @click="status = ''; priority = ''; assignedTo = ''; buildingId = ''; view = ''"
                        class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-all">
                    Clear
                </button>
            </div>

            <!-- Task list -->
            <div class="space-y-2">
                <Link v-for="task in tasks.data" :key="task.id"
                      :href="route('manage.tasks.show', task.id)"
                      class="block bg-white dark:bg-gray-900 border rounded-2xl p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                      :class="isOverdue(task)
                        ? 'border-red-200 dark:border-red-800'
                        : 'border-gray-200 dark:border-gray-800'">

                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <!-- Completion indicator -->
                            <div class="mt-0.5 shrink-0">
                                <CheckCircle2 v-if="task.status === 'completed'"
                                              class="w-5 h-5 text-emerald-500" />
                                <div v-else
                                     class="w-5 h-5 rounded-full border-2"
                                     :class="isOverdue(task)
                                        ? 'border-red-400'
                                        : task.status === 'in_progress'
                                            ? 'border-blue-400'
                                            : 'border-gray-300 dark:border-gray-600'" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <p :class="task.status === 'completed'
                                            ? 'line-through text-gray-400'
                                            : 'text-gray-900 dark:text-white'"
                                       class="font-medium truncate">
                                        {{ task.title }}
                                    </p>
                                    <span v-if="isOverdue(task)"
                                          class="text-xs font-medium text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" /> Overdue
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2 items-center">
                                    <span :class="[priorityConfig[task.priority].class, 'text-xs font-medium px-2 py-0.5 rounded-full']">
                                        {{ priorityConfig[task.priority].label }}
                                    </span>
                                    <span :class="[statusConfig[task.status].class, 'text-xs font-medium px-2 py-0.5 rounded-full']">
                                        {{ statusConfig[task.status].label }}
                                    </span>
                                    <span v-if="task.assigned_to"
                                          class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <User class="w-3 h-3" />
                                        {{ task.assigned_to?.name }}
                                    </span>
                                    <span v-if="task.due_date"
                                          class="text-xs flex items-center gap-1"
                                          :class="isOverdue(task)
                                            ? 'text-red-600 dark:text-red-400'
                                            : 'text-gray-500 dark:text-gray-400'">
                                        <Clock class="w-3 h-3" />
                                        {{ formatDate(task.due_date) }}
                                    </span>
                                    <span v-if="task.subtasks?.length"
                                          class="text-xs text-gray-400">
                                        {{ task.subtasks.filter(s => s.completed).length }}/{{ task.subtasks.length }} subtasks
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress bar for tasks with subtasks -->
                        <div v-if="task.subtasks?.length" class="hidden sm:flex flex-col items-end gap-1 shrink-0">
                            <span class="text-xs text-gray-400">
                                {{ Math.round(task.subtasks.filter(s => s.completed).length / task.subtasks.length * 100) }}%
                            </span>
                            <div class="w-20 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-400 rounded-full transition-all"
                                     :style="{ width: Math.round(task.subtasks.filter(s => s.completed).length / task.subtasks.length * 100) + '%' }" />
                            </div>
                        </div>

                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
                    </div>
                </Link>

                <div v-if="tasks.data.length === 0" class="text-center py-16">
                    <CheckCircle2 class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No tasks found.</p>
                    <Link v-if="$page.props.auth.user.permissions?.includes('manage-tasks')"
                          :href="route('manage.tasks.create')"
                          class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                        <Plus class="w-4 h-4" /> Create First Task
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tasks.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Link v-for="link in tasks.links" :key="link.label"
                      :href="link.url || '#'"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-sm transition-all',
                        link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                    ]"
                      v-html="link.label" />
            </div>
        </div>
    </ManageLayout>
</template>
