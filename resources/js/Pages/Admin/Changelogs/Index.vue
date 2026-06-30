<script setup>
import { Head, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import { Plus, Send, Trash2, CheckCircle, Clock, X } from 'lucide-vue-next'
import { ref } from 'vue'

// Plain-text preview from stored HTML for the compact list row
function preview(html) {
    const tmp = document.createElement('div')
    tmp.innerHTML = html ?? ''
    return (tmp.textContent || '').replace(/\s+/g, ' ').trim()
}

defineOptions({ layout: ManageLayout })

const props = defineProps({
    changelogs: Array,
})

const showForm = ref(false)
const selected = ref(null)

function openDetail(entry) {
    selected.value = entry
}

const typeColors = {
    feature:     'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    fix:         'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    improvement: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    security:    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
}

const form = useForm({
    title:      '',
    body:       '',
    version:    '',
    type:       'improvement',
    send_email: true,
})

function submit() {
    form.post(route('manage.changelogs.store'), {
        onSuccess: () => {
            form.reset()
            showForm.value = false
        },
    })
}

function publish(id) {
    router.post(route('manage.changelogs.publish', id))
}

function destroy(id) {
    if (!confirm('Delete this changelog entry?')) return
    router.delete(route('manage.changelogs.destroy', id))
}

function formatDate(iso) {
    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

const inputClass  = 'w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all'
const labelClass  = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5'
</script>

<template>
    <Head title="Platform Changelogs" />

    <div class="p-6 lg:p-8">
        <div class="max-w-3xl">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Platform Updates</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track and publish changes to admin staff</p>
                </div>
                <button @click="showForm = !showForm"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                    <Plus class="w-4 h-4" />
                    New Entry
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showForm" class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 mb-6">
                <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">New Changelog Entry</h2>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label :class="labelClass">Title</label>
                            <input v-model="form.title" type="text" :class="inputClass" placeholder="e.g. Fixed booking conflict bug" />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Version <span class="text-gray-400">(optional)</span></label>
                            <input v-model="form.version" type="text" :class="inputClass" placeholder="e.g. v1.4.2" />
                        </div>
                    </div>

                    <div>
                        <label :class="labelClass">Type</label>
                        <select v-model="form.type" :class="inputClass">
                            <option value="feature">Feature</option>
                            <option value="fix">Bug Fix</option>
                            <option value="improvement">Improvement</option>
                            <option value="security">Security</option>
                        </select>
                    </div>

                    <div>
                        <label :class="labelClass">Details</label>
                        <RichTextEditor v-model="form.body" />
                        <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input v-model="form.send_email" type="checkbox" id="send_email"
                               class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                        <label for="send_email" class="text-sm text-gray-700 dark:text-gray-300">
                            Send email to admins when published
                        </label>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button @click="submit" :disabled="form.processing"
                                class="px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-colors">
                            Save Draft
                        </button>
                        <button @click="showForm = false"
                                class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div v-if="changelogs.length"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="entry in changelogs" :key="entry.id"
                     @click="openDetail(entry)"
                     class="group flex items-center gap-3 px-4 py-3 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors cursor-pointer">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                            <span :class="['text-xs font-medium px-2 py-0.5 rounded-md capitalize', typeColors[entry.type]]">
                                {{ entry.type }}
                            </span>
                            <span v-if="entry.version" class="text-xs text-gray-400 font-mono">{{ entry.version }}</span>
                            <span v-if="entry.is_published"
                                  class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <CheckCircle class="w-3 h-3" /> {{ formatDate(entry.published_at) }}
                            </span>
                            <span v-else class="inline-flex items-center gap-1 text-xs text-gray-400">
                                <Clock class="w-3 h-3" /> Draft
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ entry.title }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">by {{ entry.author }} · {{ preview(entry.body) }}</p>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button v-if="!entry.is_published"
                                @click.stop="publish(entry.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                            <Send class="w-3.5 h-3.5" />
                            Publish
                        </button>
                        <button @click.stop="destroy(entry.id)"
                                class="p-1.5 text-gray-400 hover:text-red-500 transition-colors rounded opacity-0 group-hover:opacity-100">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16 text-gray-400 text-sm">
                No changelog entries yet. Create your first one above.
            </div>

        </div>
    </div>

    <!-- Detail modal -->
    <Modal :show="!!selected" max-width="lg" @close="selected = null">
        <div v-if="selected" class="p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-md capitalize', typeColors[selected.type]]">
                        {{ selected.type }}
                    </span>
                    <span v-if="selected.version" class="text-xs text-gray-400 font-mono">{{ selected.version }}</span>
                    <span v-if="selected.is_published"
                          class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                        <CheckCircle class="w-3 h-3" /> Published {{ formatDate(selected.published_at) }}
                    </span>
                    <span v-else class="inline-flex items-center gap-1 text-xs text-gray-400">
                        <Clock class="w-3 h-3" /> Draft
                    </span>
                </div>
                <button @click="selected = null"
                        class="p-1.5 -mt-1 -mr-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selected.title }}</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">by {{ selected.author }}</p>

            <div class="rt-content mt-4" v-html="selected.body" />

            <div class="flex justify-end gap-2 mt-6">
                <button v-if="!selected.is_published"
                        @click="publish(selected.id); selected = null"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-colors">
                    <Send class="w-3.5 h-3.5" /> Publish
                </button>
                <button @click="selected = null"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    Close
                </button>
            </div>
        </div>
    </Modal>
</template>
