<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Pencil, CheckCircle2, XCircle, Building2, ShieldCheck, Users } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    staff: Object,
})

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
            <Link :href="route('manage.staff.create')"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                <Plus class="w-3.5 h-3.5" />
                Add Staff
            </Link>
        </div>

        <!-- ── Staff list ── -->
        <div class="space-y-2">
            <div
                v-for="member in staff.data"
                :key="member.id"
                class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex items-center justify-between gap-4 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                :class="!member.is_active ? 'opacity-50' : ''">

                <div class="flex items-center gap-3 flex-1 min-w-0">

                    <!-- Avatar — initials, square -->
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
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <Link :href="route('manage.staff.edit', member.id)"
                          class="p-1.5 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all">
                        <Pencil class="w-3.5 h-3.5" />
                    </Link>
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
                <Link :href="route('manage.staff.create')"
                      class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all">
                    <Plus class="w-3.5 h-3.5" />
                    Add First Staff Member
                </Link>
            </div>
        </div>

    </div>
</template>
