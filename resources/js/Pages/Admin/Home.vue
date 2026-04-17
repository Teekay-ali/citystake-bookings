<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    CheckCircle2, Clock, AlertTriangle, Building2,
    LogIn, LogOut, ShoppingCart, CreditCard,
    TrendingUp, TrendingDown, Wrench, ChevronRight
} from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    user:              Object,
    myTasks:           Array,

    // Receptionist
    todayCheckins:     Array,
    todayCheckouts:    Array,
    availability:      Object,

    // Manager
    openComplaints:    Number,
    pendingMaintenance:Number,
    openTasks:         Number,
    recentComplaints:  Array,

    // Accountant
    pendingPayments:   Object,
    monthRevenue:      Number,
    monthExpenses:     Number,

    // Head of Procurement
    pendingPurchases:  Array,
})

const page = usePage()
const permissions = computed(() => page.props.auth.user?.permissions ?? [])

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
    'head-of-procurement': 'Head of Procurement',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}

const priorityColors = {
    low:    'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    medium: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
    high:   'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
    urgent: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400',
}

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short'
    }) : null
}
</script>

<template>
    <ManageLayout>
        <Head title="Home" />

        <div class="p-6 lg:p-8 max-w-5xl">

            <!-- Greeting -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ greeting }}, {{ user.name.split(' ')[0] }} 👋
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ roleLabels[user.role] ?? user.role }}
                    <span v-if="user.building">· {{ user.building }}</span>
                    · {{ new Date().toLocaleDateString('en-NG', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
            </div>

            <!-- ── Receptionist: Availability + Today's activity ── -->
            <template v-if="availability">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Units</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ availability.total }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Available</p>
                        <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">
                            {{ availability.total - availability.occupied }}
                        </p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-5">
                        <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Occupied</p>
                        <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ availability.occupied }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <!-- Today's check-ins -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <LogIn class="w-4 h-4 text-emerald-500" />
                                Today's Check-ins ({{ todayCheckins?.length ?? 0 }})
                            </h2>
                            <Link :href="route('manage.availability.index')"
                                  class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                View board →
                            </Link>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="booking in todayCheckins" :key="booking.id"
                                 class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Unit {{ booking.unit?.unit_number }} · {{ booking.unit_type?.name }}
                                    </p>
                                </div>
                                <span :class="booking.status === 'checked_in'
                                    ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400'
                                    : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'"
                                      class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                                    {{ booking.status === 'checked_in' ? 'Checked In' : 'Pending' }}
                                </span>
                            </div>
                            <div v-if="!todayCheckins?.length"
                                 class="px-5 py-6 text-center text-sm text-gray-400">
                                No check-ins today
                            </div>
                        </div>
                    </div>

                    <!-- Today's check-outs -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <LogOut class="w-4 h-4 text-amber-500" />
                                Today's Check-outs ({{ todayCheckouts?.length ?? 0 }})
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="booking in todayCheckouts" :key="booking.id"
                                 class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Unit {{ booking.unit?.unit_number }}
                                    </p>
                                </div>
                                <Link :href="route('manage.bookings.show', booking.id)"
                                      class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                                    View →
                                </Link>
                            </div>
                            <div v-if="!todayCheckouts?.length"
                                 class="px-5 py-6 text-center text-sm text-gray-400">
                                No check-outs today
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ── Manager: Overview cards ── -->
            <template v-if="openComplaints !== undefined">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <Link :href="route('manage.complaints.index') + '?status=open'"
                          class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-1 flex items-center gap-1.5">
                            <AlertTriangle class="w-3.5 h-3.5" /> Open Complaints
                        </p>
                        <p class="text-3xl font-bold text-red-700 dark:text-red-400">{{ openComplaints }}</p>
                    </Link>
                    <Link :href="route('manage.maintenance.index') + '?status=pending'"
                          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1.5">
                            <Wrench class="w-3.5 h-3.5" /> Pending Maintenance
                        </p>
                        <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingMaintenance }}</p>
                    </Link>
                    <Link :href="route('manage.tasks.index') + '?view=all'"
                          class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-blue-600 dark:text-blue-400 mb-1 flex items-center gap-1.5">
                            <CheckCircle2 class="w-3.5 h-3.5" /> Open Tasks
                        </p>
                        <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ openTasks }}</p>
                    </Link>
                </div>

                <!-- Recent complaints -->
                <div v-if="recentComplaints?.length"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Open Complaints</h2>
                        <Link :href="route('manage.complaints.index')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            View all →
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="c in recentComplaints" :key="c.id"
                              :href="route('manage.complaints.show', c.id)"
                              class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ c.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ c.submitted_by?.name }} · {{ c.building?.name }}
                                </p>
                            </div>
                            <ChevronRight class="w-4 h-4 text-gray-400 shrink-0" />
                        </Link>
                    </div>
                </div>
            </template>

            <!-- ── Accountant: Financial snapshot ── -->
            <template v-if="pendingPayments !== undefined">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1 flex items-center gap-1">
                            <TrendingUp class="w-3.5 h-3.5" /> This Month Income
                        </p>
                        <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ formatAmount(monthRevenue) }}</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-5">
                        <p class="text-xs text-red-600 dark:text-red-400 mb-1 flex items-center gap-1">
                            <TrendingDown class="w-3.5 h-3.5" /> This Month Expenses
                        </p>
                        <p class="text-lg font-bold text-red-700 dark:text-red-400">{{ formatAmount(monthExpenses) }}</p>
                    </div>
                    <Link :href="route('manage.financials.index')"
                          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                            <CreditCard class="w-3.5 h-3.5" /> Pending Maintenance
                        </p>
                        <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.maintenance }}</p>
                    </Link>
                    <Link :href="route('manage.financials.index')"
                          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                            <ShoppingCart class="w-3.5 h-3.5" /> Pending Procurement
                        </p>
                        <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.procurement }}</p>
                    </Link>
                </div>
            </template>

            <!-- ── Head of Procurement: Pending purchases ── -->
            <template v-if="pendingPurchases?.length">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <ShoppingCart class="w-4 h-4" />
                            Approved — Ready to Purchase ({{ pendingPurchases.length }})
                        </h2>
                        <Link :href="route('manage.procurement.index')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            View all →
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="p in pendingPurchases" :key="p.id"
                              :href="route('manage.procurement.show', p.id)"
                              class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ p.reference }} · {{ p.building?.name }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white shrink-0 ml-4">
                                {{ formatAmount(p.total_amount) }}
                            </span>
                        </Link>
                    </div>
                </div>
            </template>

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
