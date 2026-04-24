<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    TrendingUp, TrendingDown, DollarSign, Calendar,
    Building2, Users, CheckCircle, Clock, BarChart3,
    ArrowRight, AlertCircle, Eye, EyeOff, Plus
} from 'lucide-vue-next'
import { computed } from 'vue'
import { useFinancialVisibility } from '@/Composables/useFinancialVisibility'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    stats: Object,
    revenue: Object,
    monthlyRevenue: Array,
    revenueByProperty: Array,
    paymentBreakdown: Object,
    statusBreakdown: Object,
    recentBookings: Array,
    upcomingCheckIns: Array,
})

const { financialsVisible, toggle } = useFinancialVisibility()

const formatPrice = (price) => {
    if (!financialsVisible.value) return '₦ ••••••'
    const num = price || 0
    if (num >= 1_000_000) return `₦${(num / 1_000_000).toFixed(1)}M`
    if (num >= 1_000) return `₦${(num / 1_000).toFixed(0)}K`
    return new Intl.NumberFormat('en-NG', {
        style: 'currency', currency: 'NGN',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(num)
}

const formatPriceFull = (price) => {
    if (!financialsVisible.value) return '₦ ••••••'
    return new Intl.NumberFormat('en-NG', {
        style: 'currency', currency: 'NGN',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(price || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const formatDateShort = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short'
    })
}

const todayGreeting = computed(() => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
})

const todayDate = computed(() => {
    return new Date().toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    })
})

const maxRevenue = computed(() => {
    return Math.max(...(props.monthlyRevenue?.map(m => m.total) ?? [0]), 1)
})

const maxPropertyRevenue = computed(() => {
    return Math.max(...(props.revenueByProperty?.map(p => p.total) ?? [0]), 1)
})

const CHART_H = 96 // matches h-24 container

function revenueBarPx(value) {
    if (!value || maxRevenue.value === 0) return '2px'
    return Math.max((value / maxRevenue.value) * CHART_H, 3) + 'px'
}

const totalStatusCount = computed(() => {
    const b = props.statusBreakdown
    return (b?.confirmed ?? 0) + (b?.pending ?? 0) + (b?.completed ?? 0) + (b?.cancelled ?? 0)
})

const statusItems = computed(() => [
    { label: 'Confirmed', key: 'confirmed', color: '#16a34a' },
    { label: 'Pending',   key: 'pending',   color: '#d97706' },
    { label: 'Completed', key: 'completed', color: '#6b7280' },
    { label: 'Cancelled', key: 'cancelled', color: '#dc2626' },
])

// Merge check-ins and late checkouts into one attention feed
const attentionItems = computed(() => {
    const items = []

    // Today's check-ins
    const today = new Date().toDateString()
    ;(props.upcomingCheckIns ?? []).forEach(b => {
        const isToday = new Date(b.check_in).toDateString() === today
        items.push({
            id: b.id,
            name: b.guest_name,
            sub: `${b.building?.name ?? ''} · ${b.nights} night${b.nights !== 1 ? 's' : ''}`,
            type: isToday ? 'checkin' : 'upcoming',
            date: formatDateShort(b.check_in),
            href: route('manage.bookings.show', b.id),
        })
    })

    return items.slice(0, 8)
})

const initials = (name) => {
    return (name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="p-6 lg:p-8 space-y-6">

        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">
                    {{ todayGreeting }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ todayDate }} · Here's what's happening
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="toggle"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <EyeOff v-if="financialsVisible" class="w-3.5 h-3.5" />
                    <Eye v-else class="w-3.5 h-3.5" />
                    {{ financialsVisible ? 'Hide figures' : 'Show figures' }}
                </button>
                <Link
                    :href="route('manage.bookings.calendar')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                    <Calendar class="w-3.5 h-3.5" />
                    Calendar
                </Link>
                <Link
                    :href="route('manage.bookings.create')"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg transition-all">
                    <Plus class="w-3.5 h-3.5" />
                    New Booking
                </Link>
            </div>
        </div>

        <!-- ── KPI Strip ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

            <!-- Hero: This month revenue -->
            <div class="bg-gray-900 dark:bg-white rounded-xl p-5 col-span-1">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">This month</p>
                <p class="text-2xl font-semibold text-white dark:text-gray-900 tracking-tight mb-1">
                    {{ formatPrice(revenue?.this_month) }}
                </p>
                <div v-if="revenue?.growth_percentage !== null" class="flex items-center gap-1 text-xs">
                    <component
                        :is="revenue?.growth_percentage >= 0 ? TrendingUp : TrendingDown"
                        class="w-3 h-3"
                        :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'" />
                    <span :class="revenue?.growth_percentage >= 0 ? 'text-green-400 dark:text-green-600' : 'text-red-400 dark:text-red-600'">
                        {{ Math.abs(revenue?.growth_percentage) }}%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">vs last month</span>
                </div>
            </div>

            <!-- Total bookings -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Total bookings</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ stats?.total_bookings ?? 0 }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ stats?.active_bookings ?? 0 }} active now
                </p>
            </div>

            <!-- Properties -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Properties</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ stats?.total_properties ?? 0 }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total units</p>
            </div>

            <!-- Users -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Users</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ stats?.total_users ?? 0 }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Registered guests</p>
            </div>
        </div>

        <!-- ── Main grid ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            <!-- Revenue trend -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Revenue trend</p>
                    <Link :href="route('manage.analytics.index')"
                          class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        Analytics <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <!-- Bar chart -->
                <div class="flex items-end gap-1.5" style="height: 96px;">
                    <div v-for="month in monthlyRevenue" :key="month.month"
                         class="flex-1 flex flex-col justify-end group relative">
                        <!-- Tooltip -->
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-10
                    bg-gray-900 dark:bg-white text-white dark:text-gray-900
                    text-[10px] rounded-lg px-2.5 py-2 whitespace-nowrap shadow-lg
                    opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <p class="font-semibold">{{ month.month }}</p>
                            <p>{{ financialsVisible ? formatPrice(month.total) : '₦ ••••••' }}</p>
                        </div>
                        <div class="w-full rounded-t-sm transition-all duration-300"
                             :class="month === monthlyRevenue[monthlyRevenue.length - 1]
                 ? 'bg-gray-900 dark:bg-white'
                 : 'bg-gray-200 dark:bg-gray-700'"
                             :style="{ height: revenueBarPx(month.total) }"/>
                    </div>
                </div>
                <!-- Baseline + month labels -->
                <div class="border-t border-gray-200 dark:border-gray-700 mt-0" />
                <div class="flex gap-1.5 mt-1.5 mb-3">
                    <div v-for="month in monthlyRevenue" :key="month.month" class="flex-1 text-center">
                        <span class="text-[9px] text-gray-400 leading-none">{{ month.month.split(' ')[0] }}</span>
                    </div>
                </div>
            </div>

            <!-- Booking + payment status -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Booking status</p>
                <div class="space-y-3 mb-5">
                    <div v-for="s in statusItems" :key="s.key" class="flex items-center gap-3">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: s.color }"></div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20">{{ s.label }}</span>
                        <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 :style="{
                                     width: totalStatusCount > 0 ? ((statusBreakdown?.[s.key] ?? 0) / totalStatusCount * 100) + '%' : '0%',
                                     background: s.color
                                 }">
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-gray-900 dark:text-white w-6 text-right">
                            {{ statusBreakdown?.[s.key] ?? 0 }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Payment</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                            <p class="text-[10px] text-green-600 dark:text-green-400 uppercase tracking-wider mb-1">Paid</p>
                            <p class="text-lg font-semibold text-green-700 dark:text-green-300">{{ paymentBreakdown?.paid ?? 0 }}</p>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                            <p class="text-[10px] text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1">Pending</p>
                            <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ paymentBreakdown?.pending ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Needs attention -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Needs attention</p>
                    <Link :href="route('manage.bookings.index')"
                          class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        All bookings <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <!-- Late checkouts alert -->
                <Link v-if="stats?.late_checkouts > 0"
                      :href="route('manage.bookings.late-checkout.index')"
                      class="flex items-center gap-2.5 p-2.5 bg-red-50 dark:bg-red-900/20 rounded-lg mb-3 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    <AlertCircle class="w-4 h-4 text-red-500 flex-shrink-0" />
                    <span class="text-xs font-medium text-red-700 dark:text-red-400">
                        {{ stats?.late_checkouts }} late checkout{{ stats?.late_checkouts !== 1 ? 's' : '' }} pending
                    </span>
                    <ArrowRight class="w-3 h-3 text-red-400 ml-auto flex-shrink-0" />
                </Link>

                <!-- Today's check-ins -->
                <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    Upcoming check-ins
                </p>
                <div v-if="upcomingCheckIns?.length > 0" class="space-y-1">
                    <Link v-for="booking in upcomingCheckIns.slice(0, 5)" :key="booking.id"
                          :href="route('manage.bookings.show', booking.id)"
                          class="flex items-center gap-2.5 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:opacity-70 transition-opacity">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 text-[10px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ initials(booking.guest_name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">
                                {{ booking.building?.name }} · {{ booking.unit_type?.name }}
                            </p>
                        </div>
                        <span class="text-[10px] font-medium text-blue-600 dark:text-blue-400 flex-shrink-0">
                            {{ formatDateShort(booking.check_in) }}
                        </span>
                    </Link>
                </div>
                <p v-else class="text-xs text-gray-400 dark:text-gray-500 py-3">
                    No check-ins in the next 7 days
                </p>
            </div>
        </div>

        <!-- ── Bottom grid ── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

            <!-- Top properties -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Top properties</p>
                    <Link :href="route('manage.properties.index')"
                          class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        View all <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
                <div class="space-y-0">
                    <div v-for="(property, index) in revenueByProperty" :key="index"
                         class="flex items-center gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-xs text-gray-400 dark:text-gray-500 w-4 flex-shrink-0">{{ index + 1 }}</span>
                        <span class="text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">{{ property.property }}</span>
                        <div class="w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex-shrink-0">
                            <div class="h-full bg-gray-400 dark:bg-gray-500 rounded-full"
                                 :style="{ width: ((property.total / maxPropertyRevenue) * 100) + '%' }">
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-gray-900 dark:text-white flex-shrink-0">
                            {{ formatPrice(property.total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Revenue overview + recent bookings -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Recent bookings</p>
                    <Link :href="route('manage.bookings.index')"
                          class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors flex items-center gap-1">
                        View all <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>

                <!-- YTD + All time pills -->
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Year to date</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white tracking-tight">
                            {{ formatPrice(revenue?.this_year) }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">All time</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white tracking-tight">
                            {{ formatPrice(revenue?.total) }}
                        </p>
                    </div>
                </div>

                <!-- Recent bookings list -->
                <div class="space-y-0">
                    <Link v-for="booking in recentBookings?.slice(0, 4)" :key="booking.id"
                          :href="route('manage.bookings.show', booking.id)"
                          class="flex items-center gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:opacity-70 transition-opacity">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 text-[10px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ initials(booking.guest_name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ booking.guest_name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">
                                {{ formatDateShort(booking.check_in) }} – {{ formatDateShort(booking.check_out) }}
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-gray-900 dark:text-white flex-shrink-0">
                            {{ formatPriceFull(booking.total_amount) }}
                        </span>
                    </Link>
                </div>
            </div>
        </div>

    </div>
</template>
