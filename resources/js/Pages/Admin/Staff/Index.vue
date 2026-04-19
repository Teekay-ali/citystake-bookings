<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Plus, Pencil, CheckCircle2, XCircle, Building2, ShieldCheck } from 'lucide-vue-next'

const props = defineProps({
    staff: Object,
})

const roleLabels = {
    'super-admin':        'Super Admin',
    'manager':            'Manager',
    'accountant':         'Accountant',
    'ceo':                'CEO',
    'head-of-procurement':'Head of Procurement',
    'receptionist':       'Receptionist',
    'staff':              'Staff',
}

const roleColors = {
    'super-admin':        'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    'manager':            'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    'accountant':         'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
    'ceo':                'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800',
    'head-of-procurement':'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    'receptionist':       'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800',
    'staff':              'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-800',
}

function toggleActive(member) {
    router.post(route('manage.staff.toggle-active', member.id), {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <ManageLayout>
        <Head title="Staff Management" />

        <div class="p-6 lg:p-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-1">
                        Staff Management
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        Manage staff accounts, roles and building access
                    </p>
                </div>
                <Link :href="route('manage.staff.create')"
                      class="flex items-center gap-2 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full text-sm font-medium hover:opacity-90 transition-all">
                    <Plus class="w-4 h-4" />
                    Add Staff
                </Link>
            </div>

            <!-- Staff list -->
            <div class="space-y-3">
                <div v-for="member in staff.data" :key="member.id"
                     class="border border-gray-200 dark:border-gray-800 rounded-2xl p-5 flex items-center justify-between gap-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                     :class="!member.is_active ? 'opacity-60' : ''">

                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center shrink-0">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ member.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                                <span v-if="!member.is_active"
                                      class="text-xs px-2 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-full border border-red-200 dark:border-red-800">
                                    Inactive
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</p>

                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <!-- Role badge -->
                                <span v-if="member.roles[0]"
                                      :class="['text-xs px-2.5 py-1 rounded-full border font-medium', roleColors[member.roles[0].name] ?? roleColors['staff']]">
                                    <ShieldCheck class="w-3 h-3 inline mr-1" />
                                    {{ roleLabels[member.roles[0].name] ?? member.roles[0].name }}
                                </span>

                                <!-- Buildings -->
                                <span v-for="building in member.buildings" :key="building.id"
                                      class="text-xs px-2.5 py-1 rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                    <Building2 class="w-3 h-3" />
                                    {{ building.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <Link :href="route('manage.staff.edit', member.id)"
                              class="p-2 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-500 transition-all">
                            <Pencil class="w-4 h-4" />
                        </Link>
                        <button @click="toggleActive(member)"
                                :class="member.is_active
                                ? 'border-red-200 dark:border-red-800 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20'
                                : 'border-emerald-200 dark:border-emerald-800 text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'"
                                class="p-2 border rounded-xl transition-all">
                            <XCircle v-if="member.is_active" class="w-4 h-4" />
                            <CheckCircle2 v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="staff.data.length === 0" class="text-center py-16">
                    <p class="text-gray-500 dark:text-gray-400">No staff members yet.</p>
                    <Link :href="route('manage.staff.create')"
                          class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full text-sm font-medium hover:opacity-90 transition-all">
                        <Plus class="w-4 h-4" />
                        Add First Staff Member
                    </Link>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
