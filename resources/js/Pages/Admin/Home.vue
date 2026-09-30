<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { CheckCircle2, AlertTriangle, ChevronRight } from 'lucide-vue-next'
import { computed } from 'vue'
import { formatDate } from './Home/helpers'
import ReceptionistDashboard from './Home/ReceptionistDashboard.vue'
import ManagerDashboard from './Home/ManagerDashboard.vue'
import AccountantDashboard from './Home/AccountantDashboard.vue'
import QcDashboard from './Home/QcDashboard.vue'
import HousekeepingDashboard from './Home/HousekeepingDashboard.vue'
import ProcurementDashboard from './Home/ProcurementDashboard.vue'

const props = defineProps({
    user:              Object,
    myTasks:           Array,

    // Receptionist
    todayCheckins:      Array,
    todayCheckouts:     Array,
    currentlyOccupied:  Array,
    availability:       Object,

    // Manager
    openComplaints:    Number,
    pendingMaintenance:Number,
    openTasks:         Number,
    recentComplaints:  Array,
    charts:            Object,

    // Accountant
    pendingPayments:   Object,
    monthRevenue:      Number,
    monthExpenses:     Number,

    // Procurement Officer
    procurement:       Object,

    // Quality Control
    qc:                Object,

    // Housekeeping
    housekeeping:      Object,
})

const greeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})

const roleLabels = {
    'manager':             'Manager',
    'accountant':          'Accountant',
    'ceo':                 'CEO',
    'head-of-procurement': 'Procurement Officer',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}

const priorityColors = {
    low:    'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    medium: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
    high:   'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
    urgent: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
}
</script>

<template>
    <ManageLayout>
        <Head title="Home" />

        <div class="p-6 lg:p-8">

            <!-- Greeting -->
            <div class="mb-8">
                <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                    {{ greeting }}, {{ user.name.split(' ')[0] }} 👋
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">
                    {{ roleLabels[user.role] ?? user.role }}
                    <span v-if="user.building">· {{ user.building }}</span>
                    · {{ new Date().toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
            </div>

            <!-- Role dashboards -->
            <ReceptionistDashboard v-if="availability"
                :availability="availability" :today-checkins="todayCheckins"
                :today-checkouts="todayCheckouts" :currently-occupied="currentlyOccupied" />

            <ManagerDashboard v-if="openComplaints !== undefined"
                :open-complaints="openComplaints" :pending-maintenance="pendingMaintenance"
                :open-tasks="openTasks" :recent-complaints="recentComplaints" :charts="charts" />

            <AccountantDashboard v-if="pendingPayments !== undefined"
                :pending-payments="pendingPayments" :month-revenue="monthRevenue" :month-expenses="monthExpenses" />

            <QcDashboard v-if="qc" :qc="qc" />

            <HousekeepingDashboard v-if="housekeeping" :housekeeping="housekeeping" />

            <ProcurementDashboard v-if="procurement" :procurement="procurement" />

            <!-- ── My Tasks (all roles) ── -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4" />
                        My Tasks
                    </h2>
                    <Link :href="route('manage.tasks.index') + '?view=mine'"
                          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        View all →
                    </Link>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <Link v-for="task in myTasks" :key="task.id"
                          :href="route('manage.tasks.show', task.id)"
                          class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</p>
                                <span v-if="task.is_overdue"
                                      class="text-xs text-red-500 flex items-center gap-0.5 shrink-0">
                                    <AlertTriangle class="w-3 h-3" /> Overdue
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span :class="[priorityColors[task.priority], 'px-1.5 py-0.5 rounded-full text-xs font-medium']">
                                    {{ task.priority }}
                                </span>
                                <span v-if="task.due_date">Due {{ formatDate(task.due_date) }}</span>
                                <span v-if="task.progress > 0">· {{ task.progress }}% done</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-gray-400 shrink-0" />
                    </Link>
                    <div v-if="!myTasks?.length"
                         class="px-5 py-8 text-center text-sm text-gray-400">
                        No tasks assigned to you.
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
