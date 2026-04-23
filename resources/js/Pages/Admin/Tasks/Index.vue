<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, CheckCircle2, Clock, AlertTriangle, ChevronRight, User } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    tasks:        Object,
    buildings:    Array,
    staffMembers: Array,
    counts:       Object,
    filters:      Object,
})

const currentUser = computed(() => usePage().props.auth.user)
const canManage   = computed(() => currentUser.value?.permissions?.includes('manage-tasks'))

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
    low:    { label: 'Low',    cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    medium: { label: 'Medium', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    high:   { label: 'High',   cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    urgent: { label: 'Urgent', cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
}

const statusConfig = {
    pending:     { label: 'Pending',     cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    in_progress: { label: 'In Progress', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    completed:   { label: 'Completed',   cls: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' },
    cancelled:   { label: 'Cancelled',   cls: 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-700' },
}

const pipeline = [
    { key: 'pending',     label: 'Pending',        countKey: 'pending',     clickFn: () => { status.value = status.value === 'pending' ? '' : 'pending'; view.value = '' } },
    { key: 'in_progress', label: 'In Progress',     countKey: 'in_progress', clickFn: () => { status.value = status.value === 'in_progress' ? '' : 'in_progress'; view.value = '' } },
    { key: 'overdue',     label: 'Overdue',         countKey: 'overdue',     clickFn: () => { priority.value = priority.value === 'urgent' ? '' : 'urgent'; status.value = ''; view.value = '' } },
    { key: 'mine',        label: 'Assigned to Me',  countKey: 'mine',        clickFn: () => { view.value = view.value === 'mine' ? '' : 'mine'; status.value = '' } },
]

const isActive = (item) => {
    if (item.key === 'pending')     return status.value === 'pending'
    if (item.key === 'in_progress') return status.value === 'in_progress'
    if (item.key === 'overdue')     return priority.value === 'urgent'
    if (item.key === 'mine')        return view.value === 'mine'
    return false
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

function subtaskProgress(task) {
    if (!task.subtasks?.length) return 0
    return Math.round(task.subtasks.filter(s => s.completed).length / task.subtasks.length * 100)
}

const hasActiveFilters = () => status.value || priority.value || assignedTo.value || buildingId.value || view.value

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Tasks" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Tasks</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Assign and track work across your team</p>
            </div>
            <Link v-if="canManage"
                  :href="route('manage.tasks.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Task
            </Link>
        </div>

        <!-- ── Summary cards — clickable filters ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <button
                v-for="item in pipeline"
                :key="item.key"
                @click="item.clickFn()"
                :class="isActive(item)
                    ? 'ring-2 ring-gray-900 dark:ring-white border-transparent'
                    : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                class="bg-white dark:bg-gray-900 border rounded-xl p-4 text-left transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ item.label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ counts[item.countKey] ?? 0 }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    {{ isActive(item) ? 'Click to clear' : 'Click to filter' }}
                </p>
            </button>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-wrap gap-2 mb-6">
            <select v-model="buildingId" :class="selectClass" style="width: auto">
                <option value="">All buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-model="status" :class="selectClass" style="width: auto">
                <option value="">All statuses</option>
                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
            <select v-model="priority" :class="selectClass" style="width: auto">
                <option value="">All priorities</option>
                <option v-for="(cfg, key) in priorityConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
            <select v-model="assignedTo" :class="selectClass" style="width: auto">
                <option value="">All staff</option>
                <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <button
                v-if="hasActiveFilters()"
                @click="status = ''; priority = ''; assignedTo = ''; buildingId = ''; view = ''"
                class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                Clear filters
            </button>
        </div>

        <!-- ── Task list ── -->
        <div class="space-y-2">
            <Link
                v-for="task in tasks.data"
                :key="task.id"
                :href="route('manage.tasks.show', task.id)"
                class="flex items-start gap-3 bg-white dark:bg-gray-900 border rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                :class="isOverdue(task)
                    ? 'border-red-200 dark:border-red-800'
                    : 'border-gray-200 dark:border-gray-800'">

                <!-- Completion indicator -->
                <div class="mt-0.5 shrink-0">
                    <CheckCircle2 v-if="task.status === 'completed'" class="w-4 h-4 text-emerald-500" />
                    <div v-else class="w-4 h-4 rounded-full border-2"
                         :class="isOverdue(task)
                            ? 'border-red-400'
                            : task.status === 'in_progress'
                                ? 'border-blue-400'
                                : 'border-gray-300 dark:border-gray-600'" />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <p :class="task.status === 'completed'
                                ? 'line-through text-gray-400 dark:text-gray-600'
                                : 'text-sm font-semibold text-gray-900 dark:text-white'"
                           class="truncate text-sm">
                            {{ task.title }}
                        </p>
                        <span v-if="isOverdue(task)"
                              class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                            <AlertTriangle class="w-3 h-3" /> Overdue
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span :class="[priorityConfig[task.priority]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ priorityConfig[task.priority]?.label }}
                        </span>
                        <span :class="[statusConfig[task.status]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ statusConfig[task.status]?.label }}
                        </span>
                        <span v-if="task.assigned_to" class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                            <User class="w-3 h-3" />
                            {{ task.assigned_to?.name }}
                        </span>
                        <span v-if="task.due_date" class="text-xs flex items-center gap-1"
                              :class="isOverdue(task) ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'">
                            <Clock class="w-3 h-3" />
                            {{ formatDate(task.due_date) }}
                        </span>
                        <span v-if="task.subtasks?.length" class="text-xs text-gray-400 dark:text-gray-500">
                            {{ task.subtasks.filter(s => s.completed).length }}/{{ task.subtasks.length }} subtasks
                        </span>
                    </div>
                </div>

                <!-- Subtask progress bar -->
                <div v-if="task.subtasks?.length" class="hidden sm:flex flex-col items-end gap-1 shrink-0">
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ subtaskProgress(task) }}%</span>
                    <div class="w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-400 rounded-full transition-all"
                             :style="{ width: subtaskProgress(task) + '%' }" />
                    </div>
                </div>

                <ChevronRight class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
            </Link>

            <!-- Empty state -->
            <div v-if="tasks.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <CheckCircle2 class="w-6 h-6 text-gray-400" />
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No tasks found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ hasActiveFilters() ? 'Try adjusting your filters.' : 'Create your first task to get started.' }}
                </p>
                <button
                    v-if="hasActiveFilters()"
                    @click="status = ''; priority = ''; assignedTo = ''; buildingId = ''; view = ''"
                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    Clear filters
                </button>
                <Link v-else-if="canManage"
                      :href="route('manage.tasks.create')"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                    <Plus class="w-3.5 h-3.5" /> Create First Task
                </Link>
            </div>
        </div>

        <!-- ── Pagination ── -->
        <div v-if="tasks.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link
                v-for="link in tasks.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                    'min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                    link.active
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                        : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                ]"
                v-html="link.label" />
        </div>

    </div>
</template>
