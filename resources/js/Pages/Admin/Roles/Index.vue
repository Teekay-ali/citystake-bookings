<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import PermissionGroup from './Partials/PermissionGroup.vue'
import CreateRoleModal from './Partials/CreateRoleModal.vue'
import {
    Plus, Trash2, ShieldCheck, Users, Save, Search,
    CheckCheck, Square, Lock, RotateCcw, Pencil,
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    roles:          Array,
    allPermissions: Array,
})

// ─── Selection (master-detail) ────────────────────────────────
const selectedId = ref(props.roles[0]?.id ?? null)
const selectedRole = computed(() => props.roles.find(r => r.id === selectedId.value) ?? null)
const activeTab = ref('permissions')

// ─── Permission editing ───────────────────────────────────────
const editForm = useForm({ permissions: [] })
const originalPerms = ref([])
const permSearch = ref('')

watch(selectedRole, (role) => {
    const perms = role ? role.permissions.map(p => p.name) : []
    editForm.permissions = [...perms]
    editForm.clearErrors()
    originalPerms.value = [...perms]
    permSearch.value = ''
    activeTab.value = 'permissions'
}, { immediate: true })

const isSuper = computed(() => selectedRole.value?.name === 'super-admin')

const dirty = computed(() =>
    [...editForm.permissions].sort().join('|') !== [...originalPerms.value].sort().join('|')
)

function toggle(perm) {
    const i = editForm.permissions.indexOf(perm)
    if (i === -1) editForm.permissions.push(perm)
    else editForm.permissions.splice(i, 1)
}
function toggleDomain(perms) {
    const allOn = perms.every(p => editForm.permissions.includes(p))
    if (allOn) editForm.permissions = editForm.permissions.filter(p => !perms.includes(p))
    else perms.forEach(p => { if (!editForm.permissions.includes(p)) editForm.permissions.push(p) })
}
function selectAll() { editForm.permissions = [...props.allPermissions] }
function clearAll()  { editForm.permissions = [] }
function resetChanges() { editForm.permissions = [...originalPerms.value] }

function save() {
    editForm.put(route('manage.roles.update-permissions', selectedRole.value.id), {
        preserveScroll: true,
        onSuccess: () => { originalPerms.value = [...editForm.permissions] },
    })
}

// ─── Create / delete role ─────────────────────────────────────
const showCreate = ref(false)

function deleteRole(role) {
    if (!confirm(`Delete role "${getRoleLabel(role.name)}"? This cannot be undone.`)) return
    router.delete(route('manage.roles.destroy', role.id), {
        preserveScroll: true,
        onSuccess: () => { if (selectedId.value === role.id) selectedId.value = props.roles[0]?.id ?? null },
    })
}

// ─── Member reassignment ──────────────────────────────────────
const pendingReassign = ref({})
function confirmReassign(staffId, currentRole) {
    const newRole = pendingReassign.value[staffId]
    if (!newRole || newRole === currentRole) return
    useForm({ role: newRole }).post(route('manage.staff.assign-role', staffId), {
        preserveScroll: true,
        onSuccess: () => { delete pendingReassign.value[staffId] },
    })
}

// ─── Permission grouping ──────────────────────────────────────
const permissionGroups = computed(() => {
    const groups = {}
    props.allPermissions.forEach(p => {
        let domain = 'General'
        if (/(booking|checkin|checkout|availability|enquir|late-checkout)/.test(p)) domain = 'Bookings'
        else if (/(propert|blocked)/.test(p))        domain = 'Properties'
        else if (/vendor/.test(p))                   domain = 'Vendors'
        else if (/complaint/.test(p))                domain = 'Complaints'
        else if (/maintenance/.test(p))              domain = 'Maintenance'
        else if (/procurement/.test(p))              domain = 'Procurement'
        else if (/stock/.test(p))                    domain = 'Stock'
        else if (/task/.test(p))                     domain = 'Tasks'
        else if (/(financ|payment|caution|deposit|emergency)/.test(p)) domain = 'Finance'
        else if (/(staff|role|guest|audit|changelog)/.test(p))         domain = 'Team & Admin'
        else if (/analytic/.test(p))                 domain = 'Analytics'
        ;(groups[domain] ??= []).push(p)
    })
    return groups
})

const filteredGroups = computed(() => {
    const q = permSearch.value.trim().toLowerCase()
    if (!q) return permissionGroups.value
    const out = {}
    for (const [domain, perms] of Object.entries(permissionGroups.value)) {
        const fp = perms.filter(p => p.replace(/-/g, ' ').toLowerCase().includes(q))
        if (fp.length) out[domain] = fp
    }
    return out
})

// ─── Labels / colours ─────────────────────────────────────────
const protectedRoles = ['super-admin', 'manager', 'accountant', 'ceo', 'head-of-procurement', 'receptionist', 'staff']
const roleLabels = {
    'super-admin': 'Super Admin', 'manager': 'Manager', 'accountant': 'Accountant',
    'ceo': 'CEO', 'head-of-procurement': 'Head of Procurement', 'receptionist': 'Receptionist', 'staff': 'Staff',
}
const roleDot = {
    'super-admin': 'bg-red-500', 'manager': 'bg-blue-500', 'accountant': 'bg-emerald-500',
    'ceo': 'bg-violet-500', 'head-of-procurement': 'bg-amber-500', 'receptionist': 'bg-sky-500', 'staff': 'bg-gray-400',
}
const getRoleLabel = (name) => roleLabels[name] ?? name.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
const getRoleDot   = (name) => roleDot[name] ?? 'bg-gray-400'
const initials = (name) => (name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
const coverage = (role) => props.allPermissions.length ? Math.round(role.permissions.length / props.allPermissions.length * 100) : 0
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="p-4 lg:p-6">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Roles &amp; Permissions</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ roles.length }} roles · {{ allPermissions.length }} permissions
                </p>
            </div>
            <button @click="showCreate = true"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all">
                <Plus class="w-3.5 h-3.5" /> New Role
            </button>
        </div>

        <div class="lg:grid lg:grid-cols-[19rem_1fr] lg:gap-4 lg:items-start">

            <!-- ── Master: role list ── -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden mb-4 lg:mb-0">
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <button v-for="role in roles" :key="role.id"
                            @click="selectedId = role.id"
                            :class="selectedId === role.id ? 'bg-gray-50 dark:bg-gray-800/60' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/40'"
                            class="w-full text-left px-4 py-3 flex items-center gap-3 transition-colors">
                        <span :class="getRoleDot(role.name)" class="w-2 h-2 rounded-full shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ getRoleLabel(role.name) }}</span>
                                <Lock v-if="protectedRoles.includes(role.name)" class="w-3 h-3 text-gray-300 dark:text-gray-600 shrink-0" />
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ role.users_count }} member{{ role.users_count !== 1 ? 's' : '' }} · {{ role.permissions.length }} perms
                            </p>
                        </div>
                        <span class="text-[11px] tabular-nums text-gray-400 dark:text-gray-500 shrink-0">{{ coverage(role) }}%</span>
                    </button>
                </div>
            </div>

            <!-- ── Detail ── -->
            <div v-if="selectedRole"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">

                <!-- Detail header -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <span :class="getRoleDot(selectedRole.name)" class="w-2.5 h-2.5 rounded-full shrink-0" />
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            {{ getRoleLabel(selectedRole.name) }}
                            <span v-if="!protectedRoles.includes(selectedRole.name)"
                                  class="text-[10px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 rounded px-1.5 py-0.5">Custom</span>
                        </h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ selectedRole.permissions.length }}/{{ allPermissions.length }} permissions · {{ selectedRole.users_count }} members
                        </p>
                    </div>
                    <button v-if="!protectedRoles.includes(selectedRole.name)" @click="deleteRole(selectedRole)"
                            class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 px-4 pt-3">
                    <button @click="activeTab = 'permissions'"
                            :class="activeTab === 'permissions' ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                        Permissions <span class="text-xs text-gray-400">{{ selectedRole.permissions.length }}</span>
                    </button>
                    <button @click="activeTab = 'members'"
                            :class="activeTab === 'members' ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
                        Members <span class="text-xs text-gray-400">{{ selectedRole.users_count }}</span>
                    </button>
                </div>

                <!-- ── Permissions tab ── -->
                <div v-if="activeTab === 'permissions'" class="p-4">

                    <!-- Super admin notice -->
                    <div v-if="isSuper" class="flex items-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs text-gray-500 dark:text-gray-400 mb-3">
                        <ShieldCheck class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                        Super Admin always has every permission and can't be edited.
                    </div>

                    <!-- Toolbar: search + bulk -->
                    <div class="flex flex-col sm:flex-row gap-2 mb-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                            <input v-model="permSearch" type="text" placeholder="Search permissions..."
                                   class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                        </div>
                        <div v-if="!isSuper" class="flex gap-2">
                            <button @click="selectAll" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                <CheckCheck class="w-3.5 h-3.5" /> All
                            </button>
                            <button @click="clearAll" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                <Square class="w-3.5 h-3.5" /> None
                            </button>
                        </div>
                    </div>

                    <!-- Domain groups -->
                    <div class="space-y-3">
                        <PermissionGroup
                            v-for="(perms, domain) in filteredGroups" :key="domain"
                            :domain="domain" :perms="perms"
                            :selected="isSuper ? allPermissions : editForm.permissions"
                            :disabled="isSuper"
                            @toggle="toggle" @toggle-all="toggleDomain(perms)" />
                        <p v-if="Object.keys(filteredGroups).length === 0" class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">
                            No permissions match "{{ permSearch }}".
                        </p>
                    </div>
                </div>

                <!-- ── Members tab ── -->
                <div v-else>
                    <div v-if="selectedRole.users.length === 0" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        No staff members have this role yet.
                    </div>
                    <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div v-for="member in selectedRole.users" :key="member.id"
                             class="flex items-center justify-between px-5 py-3.5 gap-4 flex-wrap">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ initials(member.name) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ member.email }}</p>
                                    <div v-if="member.buildings?.length" class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="b in member.buildings" :key="b.id"
                                              class="text-[10px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded">{{ b.name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <select :value="pendingReassign[member.id] ?? selectedRole.name"
                                        @change="(e) => pendingReassign[member.id] = e.target.value"
                                        class="pl-3 pr-8 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all">
                                    <option v-for="r in roles" :key="r.id" :value="r.name">{{ getRoleLabel(r.name) }}</option>
                                </select>
                                <button v-if="pendingReassign[member.id] && pendingReassign[member.id] !== selectedRole.name"
                                        @click="confirmReassign(member.id, selectedRole.name)"
                                        class="px-3 py-1.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-xs font-medium hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                                    Move
                                </button>
                                <Link :href="route('manage.staff.edit', member.id)"
                                      class="p-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                    <Pencil class="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Floating save bar (pins to viewport while there are unsaved changes) ── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2">
            <div v-if="selectedRole && activeTab === 'permissions' && !isSuper && dirty"
                 class="fixed bottom-4 left-1/2 -translate-x-1/2 z-30">
                <div class="flex items-center gap-3 pl-4 pr-2 py-2 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-lg shadow-gray-300/40 dark:shadow-black/40">
                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ editForm.permissions.length }} selected · unsaved
                    </span>
                    <button @click="resetChanges"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg transition-colors">
                        <RotateCcw class="w-3.5 h-3.5" /> Reset
                    </button>
                    <button @click="save" :disabled="editForm.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:opacity-50 transition-all">
                        <Save class="w-3.5 h-3.5" /> {{ editForm.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>

    <CreateRoleModal :show="showCreate" @close="showCreate = false" />
</template>
