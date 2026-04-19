<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Trash2, ShieldCheck, Users, ChevronDown, ChevronUp, Save } from 'lucide-vue-next'

const props = defineProps({
    roles:          Array,
    allPermissions: Array,
})

// ─── New role form ────────────────────────────────────────────
const newRoleForm = useForm({ name: '' })

function createRole() {
    newRoleForm.post(route('manage.roles.store'), {
        preserveScroll: true,
        onSuccess: () => newRoleForm.reset(),
    })
}

// ─── Delete role ──────────────────────────────────────────────
function deleteRole(role) {
    if (confirm(`Delete role "${role.name}"?`)) {
        router.delete(route('manage.roles.destroy', role.id), { preserveScroll: true })
    }
}

// ─── Expanded state ───────────────────────────────────────────
const expandedRole = ref(null)
const activeTab    = ref({}) // 'members' | 'permissions'

function toggleRole(roleId) {
    if (expandedRole.value === roleId) {
        expandedRole.value = null
    } else {
        expandedRole.value = roleId
        if (!activeTab.value[roleId]) activeTab.value[roleId] = 'permissions'
    }
}

function setTab(roleId, tab) {
    activeTab.value[roleId] = tab
}

// ─── Permission editing ───────────────────────────────────────
const permissionForms = {}

function getPermissionForm(role) {
    if (!permissionForms[role.id]) {
        permissionForms[role.id] = useForm({
            permissions: role.permissions.map(p => p.name),
        })
    }
    return permissionForms[role.id]
}

function togglePermission(form, permission) {
    const idx = form.permissions.indexOf(permission)
    if (idx === -1) {
        form.permissions.push(permission)
    } else {
        form.permissions.splice(idx, 1)
    }
}

function savePermissions(role) {
    const form = getPermissionForm(role)
    form.put(route('manage.roles.update-permissions', role.id), {
        preserveScroll: true,
    })
}

// ─── Permission groups for display ───────────────────────────
const permissionGroups = computed(() => {
    const groups = {}
    props.allPermissions.forEach(p => {
        const group = p.split('-').slice(1).join('-') || p
        const prefix = p.split('-')[0]
        const key = prefix === 'view' || prefix === 'manage' || prefix === 'submit'
            ? p.split('-').slice(1).join(' ')
            : p.split('-').slice(0, -1).join(' ')

        // Group by domain
        let domain = 'General'
        if (p.includes('booking') || p.includes('checkin') || p.includes('checkout') || p.includes('availability')) domain = 'Bookings'
        else if (p.includes('propert') || p.includes('blocked')) domain = 'Properties'
        else if (p.includes('vendor')) domain = 'Vendors'
        else if (p.includes('complaint')) domain = 'Complaints'
        else if (p.includes('maintenance')) domain = 'Maintenance'
        else if (p.includes('procurement')) domain = 'Procurement'
        else if (p.includes('stock')) domain = 'Stock'
        else if (p.includes('staff') || p.includes('role')) domain = 'HR & Roles'
        else if (p.includes('analytic')) domain = 'Analytics'

        if (!groups[domain]) groups[domain] = []
        groups[domain].push(p)
    })
    return groups
})

// ─── Role reassignment ────────────────────────────────────────
const reassignForms = {}

function getReassignForm(staffId, currentRole) {
    if (!reassignForms[staffId]) {
        reassignForms[staffId] = useForm({ role: currentRole })
    }
    return reassignForms[staffId]
}

function reassign(staffId, form) {
    form.post(route('manage.staff.assign-role', staffId), { preserveScroll: true })
}

// ─── Helpers ─────────────────────────────────────────────────
const protectedRoles = [
    'super-admin', 'manager', 'accountant',
    'ceo', 'head-of-procurement', 'receptionist', 'staff',
]

const roleLabels = {
    'super-admin':         'Super Admin',
    'manager':             'Manager',
    'accountant':          'Accountant',
    'ceo':                 'CEO',
    'head-of-procurement': 'Head of Procurement',
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
    'staff':               'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-800',
}

function getRoleColor(name) {
    return roleColors[name] ?? 'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-800'
}

function getRoleLabel(name) {
    return roleLabels[name] ?? name.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function formatPermission(p) {
    return p.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}
</script>

<template>
    <ManageLayout>
        <Head title="Roles & Permissions" />

        <div class="p-6 lg:p-8 max-w-4xl">

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">Roles & Permissions</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    Manage roles, their permissions, and staff assignments
                </p>
            </div>

            <!-- Create custom role -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">
                    Create Custom Role
                </h2>
                <form @submit.prevent="createRole" class="flex gap-3">
                    <div class="flex-1">
                        <input v-model="newRoleForm.name" type="text"
                               placeholder="e.g. Finance Officer, IT Support"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="newRoleForm.errors.name" class="mt-1 text-xs text-red-600">{{ newRoleForm.errors.name }}</p>
                        <p class="mt-1 text-xs text-gray-400">Name will be lowercased and hyphenated automatically</p>
                    </div>
                    <button type="submit" :disabled="newRoleForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all shrink-0">
                        <Plus class="w-4 h-4" />
                        Create
                    </button>
                </form>
            </div>

            <!-- Roles list -->
            <div class="space-y-3">
                <div v-for="role in roles" :key="role.id"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">

                    <!-- Role header -->
                    <div class="flex items-center justify-between p-5 cursor-pointer select-none"
                         @click="toggleRole(role.id)">
                        <div class="flex items-center gap-3">
                            <span :class="[getRoleColor(role.name), 'text-xs font-medium px-2.5 py-1 rounded-full border flex items-center gap-1']">
                                <ShieldCheck class="w-3 h-3" />
                                {{ getRoleLabel(role.name) }}
                            </span>
                            <span v-if="!protectedRoles.includes(role.name)"
                                  class="text-xs text-gray-400 italic">custom</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <Users class="w-4 h-4" />
                                {{ role.users_count }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ role.permissions.length }} permissions
                            </span>
                            <button v-if="!protectedRoles.includes(role.name)"
                                    @click.stop="deleteRole(role)"
                                    class="p-1.5 border border-red-200 dark:border-red-800 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                            <ChevronDown v-if="expandedRole !== role.id" class="w-4 h-4 text-gray-400" />
                            <ChevronUp v-else class="w-4 h-4 text-gray-400" />
                        </div>
                    </div>

                    <!-- Expanded content -->
                    <div v-if="expandedRole === role.id"
                         class="border-t border-gray-100 dark:border-gray-800">

                        <!-- Tabs -->
                        <div class="flex border-b border-gray-100 dark:border-gray-800">
                            <button
                                @click="setTab(role.id, 'permissions')"
                                :class="activeTab[role.id] === 'permissions'
                                    ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                    : 'text-gray-500 dark:text-gray-400'"
                                class="px-5 py-3 text-sm font-medium transition-all">
                                Permissions
                            </button>
                            <button
                                @click="setTab(role.id, 'members')"
                                :class="activeTab[role.id] === 'members'
                                    ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white'
                                    : 'text-gray-500 dark:text-gray-400'"
                                class="px-5 py-3 text-sm font-medium transition-all">
                                Members ({{ role.users_count }})
                            </button>
                        </div>

                        <!-- Permissions tab -->
                        <div v-if="activeTab[role.id] === 'permissions'" class="p-5">
                            <div class="space-y-5">
                                <div v-for="(perms, domain) in permissionGroups" :key="domain">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                        {{ domain }}
                                    </p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <label v-for="perm in perms" :key="perm"
                                               class="flex items-center gap-2 cursor-pointer group"
                                               :class="role.name === 'super-admin' ? 'opacity-60 cursor-not-allowed' : ''">
                                            <input type="checkbox"
                                                   :checked="getPermissionForm(role).permissions.includes(perm)"
                                                   :disabled="role.name === 'super-admin'"
                                                   @change="togglePermission(getPermissionForm(role), perm)"
                                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                                            <span class="text-xs text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                                                {{ formatPermission(perm) }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <p v-if="role.name === 'super-admin'" class="text-xs text-gray-400">
                                    Super Admin always has all permissions and cannot be modified.
                                </p>
                                <div v-else class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400">
                                        {{ getPermissionForm(role).permissions.length }} of {{ allPermissions.length }} selected
                                    </span>
                                    <button @click="savePermissions(role)"
                                            :disabled="getPermissionForm(role).processing"
                                            class="flex items-center gap-2 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                        <Save class="w-4 h-4" />
                                        {{ getPermissionForm(role).processing ? 'Saving...' : 'Save Permissions' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Members tab -->
                        <div v-if="activeTab[role.id] === 'members'">
                            <div v-if="role.users.length === 0"
                                 class="px-5 py-8 text-center text-sm text-gray-400">
                                No staff members have this role yet.
                            </div>
                            <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                                <div v-for="member in role.users" :key="member.id"
                                     class="flex items-center justify-between px-5 py-4 gap-4">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                {{ member.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="building in member.buildings" :key="building.id"
                                                      class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded">
                                                    {{ building.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <select
                                            :value="role.name"
                                            @change="(e) => {
                                                const f = getReassignForm(member.id, role.name)
                                                f.role = e.target.value
                                                reassign(member.id, f)
                                            }"
                                            class="px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                                            <option v-for="r in roles" :key="r.id" :value="r.name">
                                                {{ getRoleLabel(r.name) }}
                                            </option>
                                        </select>
                                        <Link :href="route('manage.staff.edit', member.id)"
                                              class="px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                            Edit
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
