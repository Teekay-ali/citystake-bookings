<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, CheckCircle2, Clock, AlertTriangle, ChevronRight, User, Trash2, X } from 'lucide-vue-next'

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

// ── Create modal ──
const showCreate = ref(false)
const createForm = useForm({
    building_id: props.buildings.length === 1 ? props.buildings[0].id : '',
    assigned_to: '', title: '', description: '', priority: 'medium', due_date: '', subtasks: [],
})
function openCreate() {
    createForm.reset(); createForm.clearErrors()
    if (props.buildings.length === 1) createForm.building_id = props.buildings[0].id
    showCreate.value = true
}
function addSubtask() { createForm.subtasks.push({ title: '' }) }
function removeSubtask(i) { createForm.subtasks.splice(i, 1) }
function submitCreate() {
    createForm.subtasks = createForm.subtasks.filter(s => s.title.trim())
    createForm.post(route('manage.tasks.store'), {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.reset() },
    })
}

const fieldCls = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const fieldLabel = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'
</script>

<template>
    <Head title="Tasks" />

    <div class="p-4 lg:p-6 flex flex-col gap-1 min-h-full">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Tasks</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Assign and track work across your team</p>
            </div>
            <button v-if="canManage" @click="openCreate"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                New Task
            </button>
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
                <button v-else-if="canManage" @click="openCreate"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                    <Plus class="w-3.5 h-3.5" /> Create First Task
                </button>
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

        <!-- ── Create modal ── -->
        <Modal :show="showCreate" max-width="2xl" @close="showCreate = false">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">New Task</h2>
                    <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label :class="fieldLabel">Title *</label>
                        <input v-model="createForm.title" type="text" placeholder="What needs to be done?" :class="fieldCls" />
                        <p v-if="createForm.errors.title" class="mt-1 text-xs text-red-600">{{ createForm.errors.title }}</p>
                    </div>
                    <div>
                        <label :class="fieldLabel">Description</label>
                        <textarea v-model="createForm.description" rows="3" :class="[fieldCls, 'resize-none']" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Building *</label>
                            <select v-model="createForm.building_id" :class="fieldCls">
                                <option value="">Select building</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="createForm.errors.building_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.building_id }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Assign To</label>
                            <select v-model="createForm.assigned_to" :class="fieldCls">
                                <option value="">Unassigned</option>
                                <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Priority *</label>
                            <select v-model="createForm.priority" :class="fieldCls">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label :class="fieldLabel">Due Date</label>
                            <input v-model="createForm.due_date" type="date" :class="fieldCls" />
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label :class="fieldLabel + ' mb-0'">Subtasks</label>
                            <button type="button" @click="addSubtask" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-all">
                                <Plus class="w-4 h-4" /> Add
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(subtask, index) in createForm.subtasks" :key="index" class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 shrink-0" />
                                <input v-model="subtask.title" type="text" placeholder="Subtask description"
                                       class="flex-1 px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                <button type="button" @click="removeSubtask(index)" class="text-red-400 hover:text-red-600 transition-all"><Trash2 class="w-4 h-4" /></button>
                            </div>
                        </div>
                        <button v-if="createForm.subtasks.length === 0" type="button" @click="addSubtask"
                                class="mt-2 w-full px-4 py-2.5 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-400 hover:border-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all">
                            + Add subtasks (optional)
                        </button>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="createForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ createForm.processing ? 'Creating...' : 'Create Task' }}
                        </button>
                        <button type="button" @click="showCreate = false"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
