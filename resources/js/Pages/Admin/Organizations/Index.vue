<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { Search, Plus, Briefcase, X, Phone, Mail, Pencil, Trash2 } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    organizations: Object,
    filters:       Object,
})

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')

function debounce(fn, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), w) } }
function go() {
    router.get(route('manage.organizations.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true, preserveScroll: true })
}
watch(search, debounce(go, 350))
watch(status, go)

// ── Create / edit modal ──
const showModal = ref(false)
const editing   = ref(null)
const form = useForm({ name: '', contact_name: '', contact_email: '', contact_phone: '', address: '', notes: '', is_active: true })

function openCreate() {
    editing.value = null
    form.reset(); form.clearErrors()
    showModal.value = true
}
function openEdit(org) {
    editing.value = org
    form.defaults({ ...org }); form.reset(); form.clearErrors()
    Object.assign(form, { name: org.name, contact_name: org.contact_name, contact_email: org.contact_email, contact_phone: org.contact_phone, address: org.address, notes: org.notes, is_active: org.is_active })
    showModal.value = true
}
function submit() {
    const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false } }
    editing.value
        ? form.put(route('manage.organizations.update', editing.value.id), opts)
        : form.post(route('manage.organizations.store'), opts)
}

// ── Delete ──
const deleting = ref(null)
function confirmDelete() {
    router.delete(route('manage.organizations.destroy', deleting.value.id), {
        preserveScroll: true,
        onFinish: () => { deleting.value = null },
    })
}

const inputCls = "w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Organizations" />

    <div class="p-4 lg:p-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Organizations</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Companies that book & pay for guests</p>
            </div>
            <button @click="openCreate"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-all">
                <Plus class="w-4 h-4" /> New
            </button>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2 mb-5">
            <div class="relative col-span-2 sm:flex-1 sm:min-w-[200px]">
                <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input v-model="search" type="text" placeholder="Search name, contact, phone…"
                       class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
            </div>
            <select v-model="status" :class="[inputCls, 'sm:w-auto']">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Empty -->
        <div v-if="organizations.data.length === 0" class="text-center py-20">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                <Briefcase class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">No organizations yet.</p>
        </div>

        <!-- List -->
        <div v-else class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
            <div v-for="o in organizations.data" :key="o.id"
                 class="group flex items-center gap-3 px-4 sm:px-5 py-3.5 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                <Link :href="route('manage.organizations.show', o.id)" class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <Briefcase class="w-4 h-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate flex items-center gap-2">
                            {{ o.name }}
                            <span v-if="!o.is_active" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500">Inactive</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                            <template v-if="o.contact_name">{{ o.contact_name }}</template>
                            <template v-if="o.contact_phone"> · {{ o.contact_phone }}</template>
                        </p>
                    </div>
                </Link>
                <span class="hidden sm:block text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ o.bookings_count }} booking{{ o.bookings_count !== 1 ? 's' : '' }}</span>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                    <button @click="openEdit(o)" class="p-1.5 text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Edit">
                        <Pencil class="w-3.5 h-3.5" />
                    </button>
                    <button @click="deleting = o" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete">
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="organizations.last_page > 1" class="flex justify-center gap-1.5 mt-6">
            <Link v-for="link in organizations.links" :key="link.label" :href="link.url || '#'"
                  :class="['min-w-[36px] h-9 flex items-center justify-center px-3 rounded-lg text-sm transition-all',
                    link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium'
                        : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                    !link.url ? 'opacity-40 pointer-events-none' : '']"
                  v-html="link.label" />
        </div>

        <!-- Create/Edit modal -->
        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ editing ? 'Edit Organization' : 'New Organization' }}</h3>
                    <button @click="showModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" :class="inputCls" placeholder="e.g. Dangote Group" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Contact name</label>
                            <input v-model="form.contact_name" type="text" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Contact phone</label>
                            <input v-model="form.contact_phone" type="text" :class="inputCls" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Contact email</label>
                        <input v-model="form.contact_email" type="email" :class="inputCls" />
                        <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">{{ form.errors.contact_email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Address</label>
                        <input v-model="form.address" type="text" :class="inputCls" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="2" :class="[inputCls, 'resize-none']" />
                    </div>
                    <label v-if="editing" class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-gray-900 dark:focus:ring-white" />
                        <span class="text-xs text-gray-600 dark:text-gray-400">Active</span>
                    </label>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:opacity-90 disabled:opacity-50">
                            {{ editing ? 'Save' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmationModal
            :show="!!deleting"
            title="Delete organization?"
            message="This can't be undone. Organizations with bookings can't be deleted - deactivate instead."
            confirm-text="Delete" variant="danger"
            @confirm="confirmDelete" @close="deleting = null" />
    </div>
</template>
