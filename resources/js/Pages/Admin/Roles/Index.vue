<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Trash2, ShieldCheck, Users, ChevronDown, Save, Check } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

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
const activeTab    = ref({})

function toggleRole(roleId) {
    expandedRole.value = expandedRole.value === roleId ? null : roleId
    if (!activeTab.value[roleId]) activeTab.value[roleId] = 'permissions'
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
    if (idx === -1) form.permissions.push(permission)
    else form.permissions.splice(idx, 1)
}

function toggleDomain(form, perms) {
    const allChecked = perms.every(p => form.permissions.includes(p))
    if (allChecked) {
        form.permissions = form.permissions.filter(p => !perms.includes(p))
    } else {
        perms.forEach(p => { if (!form.permissions.includes(p)) form.permissions.push(p) })
    }
}

function savePermissions(role) {
    getPermissionForm(role).put(route('manage.roles.update-permissions', role.id), {
        preserveScroll: true,
    })
}

// ─── Permission groups ────────────────────────────────────────
const permissionGroups = computed(() => {
    const groups = {}
    props.allPermissions.forEach(p => {
        let domain = 'General'
        if (p.includes('booking') || p.includes('checkin') || p.includes('checkout') || p.includes('availability')) domain = 'Bookings'
        else if (p.includes('propert') || p.includes('blocked')) domain = 'Properties'
        else if (p.includes('vendor'))      domain = 'Vendors'
        else if (p.includes('complaint'))   domain = 'Complaints'
        else if (p.includes('maintenance')) domain = 'Maintenance'
        else if (p.includes('procurement')) domain = 'Procurement'
        else if (p.includes('stock'))       domain = 'Stock'
        else if (p.includes('staff') || p.includes('role')) domain = 'HR & Roles'
        else if (p.includes('analytic'))    domain = 'Analytics'
        if (!groups[domain]) groups[domain] = []
        groups[domain].push(p)
    })
    return groups
})

// Domain check — how many of a domain's permissions a role has
function domainCount(form, perms) {
    return perms.filter(p => form.permissions.includes(p)).length
}

// ─── Role reassignment ────────────────────────────────────────
const pendingReassign = ref({}) // staffId -> new role name

function setPendingReassign(staffId, roleName) {
    pendingReassign.value[staffId] = roleName
}

function confirmReassign(staffId, currentRole) {
    const newRole = pendingReassign.value[staffId]
    if (!newRole || newRole === currentRole) return
    const form = useForm({ role: newRole })
    form.post(route('manage.staff.assign-role', staffId), {
        preserveScroll: true,
        onSuccess: () => { delete pendingReassign.value[staffId] },
    })
}

// ─── Helpers ─────────────────────────────────────────────────
const protectedRoles = ['super-admin','manager','accountant','ceo','head-of-procurement','receptionist','staff']

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
    'super-admin':         { badge: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',       bar: 'bg-red-400' },
    'manager':             { badge: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',     bar: 'bg-blue-400' },
    'accountant':          { badge: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800', bar: 'bg-emerald-400' },
    'ceo':                 { badge: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800',   bar: 'bg-violet-400' },
    'head-of-procurement': { badge: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',     bar: 'bg-amber-400' },
    'receptionist':        { badge: 'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800',           bar: 'bg-sky-400' },
    'staff':               { badge: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',           bar: 'bg-gray-400' },
}

function getRoleColor(name) {
    return roleColors[name] ?? { badge: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700', bar: 'bg-gray-400' }
}

function getRoleLabel(name) {
    return roleLabels[name] ?? name.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function formatPermission(p) {
    return p.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

function initials(name) {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

// Permission coverage bar — % of total permissions the role has
function coveragePercent(role) {
    return props.allPermissions.length
        ? Math.round(role.permissions.length / props.allPermissions.length * 100)
        : 0
}
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="p-6 lg:p-8">
        <div class="max-w-4xl">

            <!-- ── Header ── -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Roles & Permissions</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ roles.length }} roles · {{ allPermissions.length }} total permissions
                    </p>
                </div>
            </div>

            <!-- ── Create custom role ── -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-4">
                <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                    Create Custom Role
                </h2>
                <form @submit.prevent="createRole" class="flex gap-2 items-start">
                    <div class="flex-1">
                        <input v-model="newRoleForm.name" type="text"
                               placeholder="e.g. Finance Officer, IT Support"
                               class="w-full pl-3 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                        <p v-if="newRoleForm.errors.name" class="mt-1 text-xs text-red-600">{{ newRoleForm.errors.name }}</p>
                        <p class="mt-1 text-xs text-gray-400">Will be lowercased and hyphenated automatically</p>
                    </div>
                    <button type="submit" :disabled="newRoleForm.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all shrink-0">
                        <Plus class="w-3.5 h-3.5" />
                        Create Role
                    </button>
                </form>
            </div>

            <!-- ── Roles list ── -->
            <div class="space-y-2">
                <div v-for="role in roles" :key="role.id"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden transition-all"
                     :class="expandedRole === role.id ? 'border-gray-300 dark:border-gray-700' : ''">

                    <!-- Role header -->
                    <div class="flex items-center gap-4 px-4 py-3.5 cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                         @click="toggleRole(role.id)">

                        <!-- Role badge -->
                        <span :class="[getRoleColor(role.name).badge, 'text-xs font-medium px-2 py-0.5 rounded-lg border inline-flex items-center gap-1 shrink-0']">
                            <ShieldCheck class="w-3 h-3" />
                            {{ getRoleLabel(role.name) }}
                        </span>

                        <!-- Permission coverage bar -->
                        <div class="flex-1 flex items-center gap-2 min-w-0">
                            <div class="flex-1 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                     :class="getRoleColor(role.name).bar"
                                     :style="{ width: coveragePercent(role) + '%' }" />
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0 w-20 text-right">
                                {{ role.permissions.length }}/{{ allPermissions.length }} perms
                            </span>
                        </div>

                        <!-- Members count -->
                        <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 shrink-0">
                            <Users class="w-3.5 h-3.5" />
                            {{ role.users_count }}
                        </span>

                        <!-- Custom badge -->
                        <span v-if="!protectedRoles.includes(role.name)"
                              class="text-xs text-gray-400 dark:text-gray-500 italic shrink-0">custom</span>

                        <!-- Delete (custom roles only) -->
                        <button v-if="!protectedRoles.includes(role.name)"
                                @click.stop="deleteRole(role)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>

                        <ChevronDown
                            class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                            :class="expandedRole === role.id ? 'rotate-180' : ''" />
                    </div>

                    <!-- Expanded content -->
                    <div v-if="expandedRole === role.id" class="border-t border-gray-100 dark:border-gray-800">

                        <!-- Tabs -->
                        <div class="flex border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/30">
                            <button
                                @click="setTab(role.id, 'permissions')"
                                :class="activeTab[role.id] === 'permissions'
                                    ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white bg-white dark:bg-gray-900'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-5 py-2.5 text-sm font-medium transition-all">
                                Permissions
                                <span class="ml-1.5 text-xs font-semibold">{{ role.permissions.length }}</span>
                            </button>
                            <button
                                @click="setTab(role.id, 'members')"
                                :class="activeTab[role.id] === 'members'
                                    ? 'border-b-2 border-gray-900 dark:border-white text-gray-900 dark:text-white bg-white dark:bg-gray-900'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-5 py-2.5 text-sm font-medium transition-all">
                                Members
                                <span class="ml-1.5 text-xs font-semibold">{{ role.users_count }}</span>
                            </button>
                        </div>

                        <!-- Permissions tab -->
                        <div v-if="activeTab[role.id] === 'permissions'" class="p-5">

                            <!-- Super admin notice -->
                            <div v-if="role.name === 'super-admin'"
                                 class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs text-gray-500 dark:text-gray-400 mb-4">
                                <ShieldCheck class="w-3.5 h-3.5 text-gray-400" />
                                Super Admin has all permissions and cannot be modified.
                            </div>

                            <div class="space-y-4">
                                <div v-for="(perms, domain) in permissionGroups" :key="domain"
                                     class="border border-gray-100 dark:border-gray-800 rounded-lg overflow-hidden">

                                    <!-- Domain header with toggle all -->
                                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800/50">
                                        <button
                                            v-if="role.name !== 'super-admin'"
                                            @click="toggleDomain(getPermissionForm(role), perms)"
                                            class="text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                            {{ domain }}
                                        </button>
                                        <span v-else class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ domain }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ domainCount(getPermissionForm(role), perms) }}/{{ perms.length }}
                                        </span>
                                    </div>

                                    <!-- Permission checkboxes -->
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-0 divide-x divide-y divide-gray-100 dark:divide-gray-800">
                                        <label v-for="perm in perms" :key="perm"
                                               class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                               :class="role.name === 'super-admin' ? 'cursor-not-allowed' : ''">
                                            <div :class="getPermissionForm(role).permissions.includes(perm)
                                                    ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white'
                                                    : 'border-gray-300 dark:border-gray-600'"
                                                 class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-all">
                                                <Check v-if="getPermissionForm(role).permissions.includes(perm)"
                                                       class="w-2.5 h-2.5 text-white dark:text-gray-900" />
                                            </div>
                                            <input type="checkbox"
                                                   :checked="getPermissionForm(role).permissions.includes(perm)"
                                                   :disabled="role.name === 'super-admin'"
                                                   @change="togglePermission(getPermissionForm(role), perm)"
                                                   class="sr-only" />
                                            <span class="text-xs text-gray-600 dark:text-gray-400 leading-tight">
                                                {{ formatPermission(perm) }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Save bar -->
                            <div v-if="role.name !== 'super-admin'"
                                 class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ getPermissionForm(role).permissions.length }} of {{ allPermissions.length }} permissions selected
                                </span>
                                <button @click="savePermissions(role)"
                                        :disabled="getPermissionForm(role).processing"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                    <Save class="w-3.5 h-3.5" />
                                    {{ getPermissionForm(role).processing ? 'Saving...' : 'Save Permissions' }}
                                </button>
                            </div>
                        </div>

                        <!-- Members tab -->
                        <div v-if="activeTab[role.id] === 'members'">
                            <div v-if="role.users.length === 0"
                                 class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                No staff members have this role yet.
                            </div>
                            <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                                <div v-for="member in role.users" :key="member.id"
                                     class="flex items-center justify-between px-5 py-3.5 gap-4">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                {{ initials(member.name) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ member.email }}</p>
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                <span v-for="building in member.buildings" :key="building.id"
                                                      class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded">
                                                    {{ building.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <!-- Reassign with confirm step -->
                                        <select
                                            :value="pendingReassign[member.id] ?? role.name"
                                            @change="(e) => setPendingReassign(member.id, e.target.value)"
                                            class="pl-3 pr-8 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all">
                                            <option v-for="r in roles" :key="r.id" :value="r.name">
                                                {{ getRoleLabel(r.name) }}
                                            </option>
                                        </select>
                                        <!-- Only show confirm when a change is pending -->
                                        <button
                                            v-if="pendingReassign[member.id] && pendingReassign[member.id] !== role.name"
                                            @click="confirmReassign(member.id, role.name)"
                                            class="px-3 py-1.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-xs font-medium hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                                            Confirm
                                        </button>
                                        <Link :href="route('manage.staff.edit', member.id)"
                                              class="px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
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
    </div>
</template>
