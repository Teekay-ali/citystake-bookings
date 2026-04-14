<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    buildings:    Array,
    staffMembers: Array,
})

const form = useForm({
    building_id:  props.buildings.length === 1 ? props.buildings[0].id : '',
    assigned_to:  '',
    title:        '',
    description:  '',
    priority:     'medium',
    due_date:     '',
    subtasks:     [],
})

function addSubtask() {
    form.subtasks.push({ title: '' })
}

function removeSubtask(index) {
    form.subtasks.splice(index, 1)
}

function submit() {
    // Remove empty subtasks
    form.subtasks = form.subtasks.filter(s => s.title.trim())
    form.post(route('manage.tasks.store'))
}
</script>

<template>
    <ManageLayout>
        <Head title="New Task" />

        <div class="p-6 lg:p-8 max-w-2xl">

            <Link :href="route('manage.tasks.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Tasks
            </Link>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">New Task</h1>

            <form @submit.prevent="submit" class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title *</label>
                    <input v-model="form.title" type="text" placeholder="What needs to be done?"
                           class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Building *</label>
                        <select v-model="form.building_id"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select building</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Assign To</label>
                        <select v-model="form.assigned_to"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Unassigned</option>
                            <option v-for="s in staffMembers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Priority *</label>
                        <select v-model="form.priority"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Due Date</label>
                        <input v-model="form.due_date" type="date"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Subtasks -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subtasks</label>
                        <button type="button" @click="addSubtask"
                                class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-all">
                            <Plus class="w-4 h-4" /> Add
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(subtask, index) in form.subtasks" :key="index"
                             class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 shrink-0" />
                            <input v-model="subtask.title" type="text" placeholder="Subtask description"
                                   class="flex-1 px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <button type="button" @click="removeSubtask(index)"
                                    class="text-red-400 hover:text-red-600 transition-all">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <button v-if="form.subtasks.length === 0" type="button" @click="addSubtask"
                            class="mt-2 w-full px-4 py-2.5 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-400 hover:border-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all">
                        + Add subtasks (optional)
                    </button>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        {{ form.processing ? 'Creating...' : 'Create Task' }}
                    </button>
                    <Link :href="route('manage.tasks.index')"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </ManageLayout>
</template>
