<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import { Plus, Pencil, CheckCircle2, XCircle, Mail, Building2, ShieldCheck, Users, X } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    staff:     Object,
    roles:     Array,
    buildings: Array,
})

const roleLabels = {
    'super-admin':         'Super Admin',
    'manager':             'Manager',
    'accountant':          'Accountant',
    'ceo':                 'CEO',
    'head-of-procurement': 'Procurement Officer',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}

const roleColors = {
    'super-admin':         'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    'manager':             'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    'accountant':          'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
    'ceo':                 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800',
    'head-of-procurement': 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    'receptionist':        'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800',
    'staff':               'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',
}

function toggleActive(member) {
    router.post(route('manage.staff.toggle-active', member.id), {}, {
        preserveScroll: true,
    })
}

function initials(name) {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

// ── Create / Edit modal ──
const showModal = ref(false)
const editing   = ref(null)
const isEdit    = computed(() => !!editing.value)

const staffForm = useForm({
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    role: '', building_ids: [], is_active: true,
})

function openCreate() {
    editing.value = null
    staffForm.reset(); staffForm.clearErrors()
    showModal.value = true
}
function openEdit(member) {
    editing.value = member
    staffForm.clearErrors()
    staffForm.name = member.name
    staffForm.email = member.email
    staffForm.phone = member.phone ?? ''
    staffForm.password = ''
    staffForm.password_confirmation = ''
    staffForm.role = member.roles[0]?.name ?? ''
    staffForm.building_ids = member.buildings.map(b => b.id)
    staffForm.is_active = member.is_active
    showModal.value = true
}
function toggleBuilding(id) {
    const idx = staffForm.building_ids.indexOf(id)
    if (idx === -1) staffForm.building_ids.push(id)
    else staffForm.building_ids.splice(idx, 1)
}
function submitStaff() {
    const opts = { preserveScroll: true, onSuccess: () => { showModal.value = false; staffForm.reset() } }
    if (isEdit.value) staffForm.put(route('manage.staff.update', editing.value.id), opts)
    else staffForm.post(route('manage.staff.store'), opts)
}

const fieldCls = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const fieldLabel = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'
</script>

<template>
    <Head title="Staff Management" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Staff Management</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Manage staff accounts, roles and building access
                </p>
            </div>
            <button @click="openCreate"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Staff
            </button>
        </div>

        <!-- ── Staff list ── -->
        <div class="space-y-2">
            <div
                v-for="member in staff.data"
                :key="member.id"
                class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center justify-between gap-4 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                :class="!member.is_active ? 'opacity-50' : ''">

                <div class="flex items-center gap-3 flex-1 min-w-0">

                    <!-- Avatar - initials, square -->
                    <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                            {{ initials(member.name) }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                            <span v-if="!member.is_active"
                                  class="text-xs px-1.5 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-md border border-red-200 dark:border-red-800">
                                Inactive
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate mb-2">{{ member.email }}</p>

                        <div class="flex items-center gap-1.5 flex-wrap">
                            <!-- Role badge -->
                            <span v-if="member.roles[0]"
                                  :class="['text-xs px-2 py-0.5 rounded-lg border font-medium inline-flex items-center gap-1', roleColors[member.roles[0].name] ?? roleColors['staff']]">
                                <ShieldCheck class="w-3 h-3" />
                                {{ roleLabels[member.roles[0].name] ?? member.roles[0].name }}
                            </span>

                            <!-- Building badges -->
                            <span v-for="building in member.buildings" :key="building.id"
                                  class="text-xs px-2 py-0.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 inline-flex items-center gap-1">
                                <Building2 class="w-3 h-3" />
                                {{ building.name }}
                            </span>

                            <span v-if="!member.welcome_sent_at"
                                  class="text-xs px-2 py-0.5 rounded-lg border font-medium inline-flex items-center gap-1 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800">
                                <Mail class="w-3 h-3" />
                                Not notified
                            </span>

                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button @click="openEdit(member)"
                          class="p-1.5 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all">
                        <Pencil class="w-3.5 h-3.5" />
                    </button>
                    <button
                        @click="toggleActive(member)"
                        :class="member.is_active
                            ? 'border-gray-200 dark:border-gray-800 text-gray-400 hover:border-red-200 dark:hover:border-red-800 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20'
                            : 'border-gray-200 dark:border-gray-800 text-gray-400 hover:border-emerald-200 dark:hover:border-emerald-800 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'"
                        class="p-1.5 border rounded-lg transition-all"
                        :title="member.is_active ? 'Deactivate' : 'Activate'">
                        <XCircle v-if="member.is_active" class="w-3.5 h-3.5" />
                        <CheckCircle2 v-else class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="staff.data.length === 0" class="text-center py-20">
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-4">
                    <Users class="w-6 h-6 text-gray-400" />
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No staff members yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Add your first staff member to get started.</p>
                <button @click="openCreate"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                    <Plus class="w-3.5 h-3.5" />
                    Add First Staff Member
                </button>
            </div>
        </div>

        <!-- ── Create / Edit modal ── -->
        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ isEdit ? 'Edit Staff Member' : 'Add Staff Member' }}</h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><X class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="submitStaff" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">Full Name *</label>
                            <input v-model="staffForm.name" type="text" :class="fieldCls" />
                            <p v-if="staffForm.errors.name" class="mt-1 text-xs text-red-600">{{ staffForm.errors.name }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Phone</label>
                            <input v-model="staffForm.phone" type="text" :class="fieldCls" />
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Email *</label>
                        <input v-model="staffForm.email" type="email" :class="fieldCls" />
                        <p v-if="staffForm.errors.email" class="mt-1 text-xs text-red-600">{{ staffForm.errors.email }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label :class="fieldLabel">
                                {{ isEdit ? 'New Password' : 'Password *' }}
                                <span v-if="isEdit" class="text-gray-400 font-normal">(leave blank to keep)</span>
                            </label>
                            <input v-model="staffForm.password" type="password" :class="fieldCls" />
                            <p v-if="staffForm.errors.password" class="mt-1 text-xs text-red-600">{{ staffForm.errors.password }}</p>
                        </div>
                        <div>
                            <label :class="fieldLabel">Confirm Password</label>
                            <input v-model="staffForm.password_confirmation" type="password" :class="fieldCls" />
                        </div>
                    </div>
                    <div>
                        <label :class="fieldLabel">Role *</label>
                        <select v-model="staffForm.role" :class="fieldCls">
                            <option value="">Select a role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ roleLabels[role.name] ?? role.name }}</option>
                        </select>
                        <p v-if="staffForm.errors.role" class="mt-1 text-xs text-red-600">{{ staffForm.errors.role }}</p>
                    </div>
                    <div>
                        <label :class="fieldLabel">Building Access * <span class="text-gray-400 font-normal">(one or more)</span></label>
                        <div class="grid grid-cols-1 gap-2">
                            <label v-for="building in buildings" :key="building.id"
                                   :class="staffForm.building_ids.includes(building.id) ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                                   class="flex items-center gap-3 px-4 py-2.5 border rounded-xl cursor-pointer transition-all">
                                <input type="checkbox" :checked="staffForm.building_ids.includes(building.id)" @change="toggleBuilding(building.id)"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ building.name }}</span>
                            </label>
                        </div>
                        <p v-if="staffForm.errors.building_ids" class="mt-1 text-xs text-red-600">{{ staffForm.errors.building_ids }}</p>
                    </div>
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Account Active</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Inactive staff cannot log in</p>
                        </div>
                        <button type="button" @click="staffForm.is_active = !staffForm.is_active"
                                :class="staffForm.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="staffForm.is_active ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                        </button>
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="staffForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ staffForm.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Staff') }}
                        </button>
                        <button type="button" @click="showModal = false"
                                class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
