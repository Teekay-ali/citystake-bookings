<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import {
    LayoutDashboard, CalendarDays, Building2, Ban, BarChart3,
    Users, Grid3x3, Clock, Menu, X, LogOut, User, ClipboardList,
    ShoppingCart, AlertTriangle, Wrench, Package, BookOpen,
    DollarSign, CheckSquare, MessageSquare, Sun, Moon,
    ChevronLeft, ChevronRight, FileText, ShieldCheck, ChevronUp, ChevronDown,
    Search, Plus, Banknote, BadgeCheck, UserRound
} from 'lucide-vue-next'
import NotificationBell from '@/Components/NotificationBell.vue'
import { useDarkMode } from '@/Composables/useDarkMode'
import { useFloating, offset, shift, flip } from '@floating-ui/vue'

const toast = useToast()
const { isDark, toggle: toggleDark } = useDarkMode()

const page = usePage()
const user = computed(() => page.props.auth.user)
const pendingCount = computed(() => page.props.lateCheckoutPendingCount ?? 0)
const unreadMessages = computed(() => page.props.unreadMessages ?? 0)

const sidebarOpen = ref(false)
const collapsed = ref(
    typeof window !== 'undefined'
        ? localStorage.getItem('sidebar-collapsed') === 'true'
        : false
)

function toggleCollapsed() {
    collapsed.value = !collapsed.value
    localStorage.setItem('sidebar-collapsed', collapsed.value)
}

// ── Sidebar scroll persistence ────────────────────────────────
const navRef = ref(null)

watch(navRef, (el) => {
    if (el) el.scrollTop = parseInt(localStorage.getItem('sidebar-scroll') ?? '0')
})

function saveScroll() {
    if (navRef.value) localStorage.setItem('sidebar-scroll', navRef.value.scrollTop)
}

// ── Tooltip (nav item hover, collapsed mode only) ─────────────
const hoveredItem = ref(null)
const tooltipAnchor = ref(null)
const tooltipEl = ref(null)

const { floatingStyles } = useFloating(tooltipAnchor, tooltipEl, {
    placement: 'right',
    strategy: 'fixed',
    middleware: [offset(8), shift({ padding: 8 }), flip()],
})

function onMouseEnter(item, el) {
    if (!collapsed.value) return
    hoveredItem.value = item.label
    tooltipAnchor.value = el
}

function onMouseLeave() {
    hoveredItem.value = null
    tooltipAnchor.value = null
}

// ── User menu ─────────────────────────────────────────────────
const showUserMenu = ref(false)

function toggleUserMenu() {
    showUserMenu.value = !showUserMenu.value
}

function closeUserMenu() {
    showUserMenu.value = false
}

function handleClickOutside(e) {
    if (showUserMenu.value && !e.target.closest('.user-footer-container')) {
        showUserMenu.value = false
    }
    if (quickActionsOpen.value && !e.target.closest('.quick-actions-container')) {
        quickActionsOpen.value = false
    }
}

// ── Global search ─────────────────────────────────────────────
const searchQuery   = ref('')
const searchResults = ref([])
const searchOpen    = ref(false)
const searchLoading = ref(false)
const searchRef     = ref(null)
let   searchTimeout = null

async function runSearch(q) {
    if (q.length < 2) { searchResults.value = []; searchOpen.value = false; return }
    searchLoading.value = true
    try {
        const res  = await fetch(route('manage.search') + '?q=' + encodeURIComponent(q))
        const data = await res.json()
        searchResults.value = data
        searchOpen.value    = data.length > 0
    } catch {
        searchResults.value = []
    } finally {
        searchLoading.value = false
    }
}

watch(searchQuery, (q) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => runSearch(q), 300)
})

function closeSearch() {
    searchOpen.value  = false
    searchQuery.value = ''
}

function handleSearchClickOutside(e) {
    if (searchRef.value && !searchRef.value.contains(e.target)) closeSearch()
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('click', handleSearchClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('click', handleSearchClickOutside)
})

// ── Quick actions ─────────────────────────────────────────────
const quickActionsOpen = ref(false)

const quickActions = [
    { label: 'New Booking',            icon: CalendarDays,  route: 'manage.bookings.create',      permission: 'manage-bookings' },
    { label: 'New Complaint',          icon: AlertTriangle, route: 'manage.complaints.create',    permission: 'view-complaints' },
    { label: 'New Maintenance Report', icon: Wrench,        route: 'manage.maintenance.create',   permission: 'view-maintenance' },
    { label: 'New Procurement',        icon: ShoppingCart,  route: 'manage.procurement.create',   permission: 'view-procurement' },
]

const visibleQuickActions = computed(() =>
    quickActions.filter(a => !a.permission || userPermissions.value.includes(a.permission))
)

// ── Flash toasts ──────────────────────────────────────────────
watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
    if (flash?.info)    toast.info(flash.info)
    if (flash?.warning) toast.warning(flash.warning)
}, { deep: true, immediate: true })

// ── Dashboard route (role-aware) ──────────────────────────────
const dashboardRoute = computed(() => {
    const roles = user.value?.roles ?? []
    return roles.includes('super-admin') || roles.includes('ceo')
        ? 'manage.dashboard'
        : 'manage.home'
})

// ── Nav groups ────────────────────────────────────────────────
const navGroups = computed(() => [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', icon: LayoutDashboard, route: dashboardRoute.value, match: 'manage.dashboard|manage.home' },
        ]
    },
    {
        label: 'Bookings',
        items: [
            { label: 'All Bookings',   icon: ClipboardList,   route: 'manage.bookings.index',               match: 'manage.bookings.index|manage.bookings.create|manage.bookings.show|manage.bookings.check-in', permission: 'view-bookings' },
            { label: 'Availability',   icon: Grid3x3,        route: 'manage.availability.index',           match: 'manage.availability.*',                permission: 'manage-availability' },
            { label: 'Calendar',       icon: CalendarDays,   route: 'manage.bookings.calendar',            match: 'manage.bookings.calendar',             permission: 'manage-availability' },
            { label: 'Messages',       icon: MessageSquare,  route: 'manage.messages.index',               match: 'manage.messages.*',                    permission: 'manage-bookings', badge: unreadMessages },
            { label: 'Late Checkouts', icon: Clock,          route: 'manage.bookings.late-checkout.index', match: 'manage.bookings.late-checkout.index',  permission: 'approve-late-checkout', badge: pendingCount },
        ]
    },
    {
        label: 'Properties',
        items: [
            { label: 'Properties',    icon: Building2, route: 'manage.properties.index',    match: 'manage.properties.*',    permission: 'view-properties' },
            { label: 'Blocked Dates', icon: Ban,       route: 'manage.blocked-dates.index', match: 'manage.blocked-dates.*', permission: 'manage-blocked-dates' },
        ]
    },
    {
        label: 'Operations',
        items: [
            { label: 'Procurement', icon: ShoppingCart,  route: 'manage.procurement.index', match: 'manage.procurement.*', permission: 'view-procurement' },
            { label: 'Complaints',  icon: AlertTriangle, route: 'manage.complaints.index',  match: 'manage.complaints.*',  permission: 'view-complaints' },
            { label: 'Maintenance', icon: Wrench,        route: 'manage.maintenance.index', match: 'manage.maintenance.*', permission: 'view-maintenance' },
            { label: 'Stock',       icon: Package,       route: 'manage.stock.index',       match: 'manage.stock.*',       permission: 'view-stock' },
            { label: 'Vendors',     icon: BookOpen,      route: 'manage.vendors.index',     match: 'manage.vendors.*',     permission: 'view-vendors' },
        ]
    },
    {
        label: 'Finance & Analytics',
        items: [
            { label: 'Analytics',       icon: BarChart3,  route: 'manage.analytics.index',  match: 'manage.analytics.*',  permission: 'view-analytics' },
            { label: 'Financials',      icon: DollarSign, route: 'manage.financials.index', match: 'manage.financials.index|manage.financials.manual|manage.financials.pay|manage.financials.export', permission: 'view-financials' },
            { label: 'Caution Fees',    icon: Banknote,   route: 'manage.financials.deposits', match: 'manage.financials.deposits', permission: 'view-financials' },
            { label: 'Approvals',       icon: BadgeCheck, route: 'manage.payment-approvals.index', match: 'manage.payment-approvals.*', permission: 'manage-payment-approvals' },
        ]
    },
    {
        label: 'Team',
        items: [
            { label: 'Guests',        icon: UserRound,   route: 'manage.guests.index',         match: 'manage.guests.*',         permission: 'manage-guests' },
            { label: 'Staff',         icon: Users,       route: 'manage.staff.index',         match: 'manage.staff.*',         permission: 'manage-staff' },
            { label: 'Staff Queries', icon: FileText,    route: 'manage.staff-queries.index', match: 'manage.staff-queries.*', permission: 'manage-staff-queries' },
            { label: 'Tasks',         icon: CheckSquare, route: 'manage.tasks.index',         match: 'manage.tasks.*',         permission: 'view-tasks' },
            { label: 'Roles',         icon: ShieldCheck, route: 'manage.roles.index',         match: 'manage.roles.*',         permission: 'manage-roles' },
            { label: 'Admin Accounts',  icon: ShieldCheck, route: 'manage.admin-accounts.index', match: 'manage.admin-accounts.*', permission: 'manage-roles' },
            { label: 'Audit Logs',    icon: FileText,    route: 'manage.audit-logs.index',    match: 'manage.audit-logs.*',    permission: 'view-audit-logs' },
        ]
    },
])

function isActive(match) {
    try {
        return match.split('|').some(m => route().current(m.trim()))
    } catch {
        return false
    }
}

const roleLabels = {
    'super-admin':         'Super Admin',
    'manager':             'Manager',
    'accountant':          'Accountant',
    'ceo':                 'CEO',
    'head-of-procurement': 'Head of Procurement',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}

const userPermissions = computed(() => page.props.auth.user?.permissions ?? [])

function canSeeItem(item) {
    if (!item.permission) return true
    return userPermissions.value.includes(item.permission)
}
</script>

<template>
    <div class="min-h-[100dvh] bg-white dark:bg-gray-950 flex flex-col">

        <!-- ── Mobile backdrop ───────────────────────────── -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="sidebarOpen"
                 @click="sidebarOpen = false"
                 class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden" />
        </Transition>

        <!-- ── Floating tooltip (collapsed nav hover) ────── -->
        <Transition
            enter-active-class="transition-opacity duration-100"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-75"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="hoveredItem"
                 ref="tooltipEl"
                 :style="floatingStyles"
                 class="fixed z-[9999] px-2.5 py-1.5 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs font-medium rounded-lg whitespace-nowrap pointer-events-none">
                {{ hoveredItem }}
            </div>
        </Transition>

        <!-- ── Sidebar ───────────────────────────────────── -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'lg:w-16' : 'lg:w-64',
                'fixed top-0 left-0 h-[100dvh] w-64 bg-white dark:bg-gray-950 z-50 flex flex-col transition-all duration-300 lg:translate-x-0'
            ]">

            <!-- Logo row -->
            <div class="h-16 flex items-center justify-between px-4 shrink-0">
                <Link :href="route('home')"
                      :class="collapsed ? 'mx-auto' : ''"
                      class="flex items-center gap-2 min-w-0">
                    <img src="/citystake-120.png" alt="CityStake Bookings" class="h-8 w-auto dark:invert" />
                    <span v-if="!collapsed" class="text-xl font-light tracking-tight text-gray-900 dark:text-white">CityStake</span>
                </Link>

                <!-- Mobile close -->
                <button @click="sidebarOpen = false"
                        class="lg:hidden p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <X class="w-5 h-5" />
                </button>

                <!-- Desktop collapse -->
                <button v-if="!collapsed"
                        @click="toggleCollapsed()"
                        :aria-expanded="!collapsed"
                        aria-label="Toggle sidebar"
                        class="hidden lg:flex p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                </button>
            </div>

            <!-- Expand button when collapsed -->
            <div v-if="collapsed" class="hidden lg:flex justify-center pb-2 shrink-0">
                <button @click="toggleCollapsed()"
                        aria-label="Expand sidebar"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>

            <!-- Nav items -->
            <nav ref="navRef"
                 @scroll="saveScroll"
                 class="flex-1 overflow-y-auto overscroll-contain py-4 px-2"
                 style="scrollbar-width: none; -ms-overflow-style: none;">
                <template v-for="group in navGroups" :key="group.label">
                    <div v-if="group.items.some(item => canSeeItem(item) && !item.soon)" class="mb-4">

                        <p v-if="!collapsed"
                           class="text-xs font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider px-3 mb-1">
                            {{ group.label }}
                        </p>
                        <div v-else class="border-t border-gray-100 dark:border-gray-800 mx-2 mb-2" />

                        <div class="space-y-0.5">
                            <template v-for="item in group.items" :key="item.label">
                                <template v-if="canSeeItem(item)">

                                    <!-- Soon (disabled) -->
                                    <div v-if="item.soon"
                                         :class="collapsed ? 'justify-center px-0' : 'px-3'"
                                         class="flex items-center gap-2.5 py-2 rounded-lg text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                         @mouseenter="(e) => onMouseEnter(item, e.currentTarget)"
                                         @mouseleave="onMouseLeave">
                                        <span class="w-6 h-6 flex items-center justify-center shrink-0">
                                            <component :is="item.icon" class="w-4 h-4" />
                                        </span>
                                        <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
                                        <span v-if="!collapsed"
                                              class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 px-1.5 py-0.5 rounded-full">
                                            Soon
                                        </span>
                                    </div>

                                    <!-- Nav link -->
                                    <Link v-else
                                          :href="route(item.route)"
                                          @click="sidebarOpen = false"
                                          @mouseenter="(e) => onMouseEnter(item, e.currentTarget)"
                                          @mouseleave="onMouseLeave"
                                          :class="[
                                              isActive(item.match)
                                                  ? 'bg-white dark:bg-gray-800/60 text-gray-900 dark:text-white font-medium border border-gray-200 dark:border-gray-800 rounded-xl'
                                                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
                                              collapsed ? 'justify-center px-0' : 'px-3'
                                          ]"
                                          class="relative flex items-center gap-2.5 py-2 rounded-lg text-sm transition-all">
                                        <span :class="isActive(item.match)
                                            ? 'w-6 h-6 bg-gray-900 dark:bg-white rounded-md flex items-center justify-center shrink-0'
                                            : 'w-6 h-6 flex items-center justify-center shrink-0'">
                                            <component :is="item.icon"
                                                       :class="isActive(item.match) ? 'w-3.5 h-3.5 text-white dark:text-gray-900' : 'w-4 h-4'" />
                                        </span>
                                        <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
                                        <span v-if="!collapsed && item.badge && item.badge > 0"
                                              class="bg-amber-500 text-white text-xs font-medium w-5 h-5 rounded-full flex items-center justify-center">
                                            {{ item.badge }}
                                        </span>
                                        <span v-if="collapsed && item.badge && item.badge > 0"
                                              class="absolute top-1 right-1 w-2 h-2 bg-amber-500 rounded-full" />
                                    </Link>

                                </template>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>


        </aside>

        <!-- ── Main content ── -->
        <div :class="collapsed ? 'lg:ml-16' : 'lg:ml-64'"
             class="flex-1 flex flex-col min-w-0 transition-all duration-300">

            <!-- ── Topbar ── -->
            <header class="h-16 bg-white dark:bg-gray-950 flex items-center gap-3 px-4 shrink-0 sticky top-0 z-30">
                <!-- Mobile hamburger -->
                <button @click="sidebarOpen = true"
                        class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all lg:hidden">
                    <Menu class="w-5 h-5" />
                </button>

                <!-- Global search -->
                <div ref="searchRef" class="relative flex-1 max-w-sm">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search bookings, guests, units..."
                            class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
                        />
                        <div v-if="searchLoading"
                             class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                    </div>

                    <!-- Results dropdown -->
                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1">
                        <div v-if="searchOpen"
                             class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl overflow-hidden z-50">
                            <Link
                                v-for="result in searchResults" :key="`${result.type}-${result.id}`"
                                :href="result.url"
                                @click="closeSearch"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <div :class="[
                                'w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0',
                                result.type === 'booking' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' :
                                result.type === 'unit'    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' :
                                'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                            ]">
                                    {{ result.type === 'booking' ? 'B' : result.type === 'unit' ? 'U' : 'P' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ result.label }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ result.sublabel }}</p>
                                </div>
                                <span :class="[
                                'text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0',
                                result.status === 'confirmed'  ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' :
                                result.status === 'checked_in' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' :
                                result.status === 'cancelled'  ? 'bg-red-50 dark:bg-red-900/20 text-red-500' :
                                result.status === 'staff'      ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' :
                                'bg-gray-100 dark:bg-gray-800 text-gray-500'
                            ]">{{ result.status }}</span>
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- Spacer -->
                <div class="flex-1" />

                <!-- Quick actions -->
                <div v-if="visibleQuickActions.length" class="relative quick-actions-container">
                    <button @click="quickActionsOpen = !quickActionsOpen"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-all">
                        <Plus class="w-4 h-4" />
                        <span class="hidden sm:inline">New</span>
                        <ChevronDown class="w-3.5 h-3.5" />
                    </button>

                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1">
                        <div v-if="quickActionsOpen"
                             class="absolute right-0 top-full mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl overflow-hidden z-50">
                            <Link v-for="action in visibleQuickActions" :key="action.label"
                                  :href="route(action.route)"
                                  @click="quickActionsOpen = false"
                                  class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-sm text-gray-700 dark:text-gray-300 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <component :is="action.icon" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                {{ action.label }}
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- Dark mode -->
                <button @click="toggleDark"
                        class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                    <Sun v-if="isDark" class="w-4 h-4" />
                    <Moon v-else class="w-4 h-4" />
                </button>

                <!-- Notification bell -->
                <NotificationBell />

                <!-- User avatar -->
                <div class="relative user-footer-container">
                    <button @click.stop="toggleUserMenu"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-all">
                        <div class="w-7 h-7 rounded-lg bg-gray-900 dark:bg-white flex items-center justify-center">
                        <span class="text-white dark:text-gray-900 text-xs font-semibold">
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </span>
                        </div>
                        <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300 max-w-24 truncate">
                        {{ user?.name?.split(' ')[0] }}
                    </span>
                        <ChevronDown class="hidden sm:block w-3.5 h-3.5 text-gray-400" />
                    </button>

                    <!-- User dropdown -->
                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1">
                        <div v-if="showUserMenu"
                             class="absolute right-0 top-full mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ roleLabels[user?.roles?.[0]] ?? 'Admin' }}</p>
                            </div>
                            <Link :href="route('profile.edit')"
                                  @click="closeUserMenu"
                                  class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <User class="w-4 h-4 text-gray-400" />
                                Profile
                            </Link>
                            <Link :href="route('home')"
                                  @click="closeUserMenu"
                                  class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-t border-gray-100 dark:border-gray-800">
                                <Building2 class="w-4 h-4 text-gray-400" />
                                Guest Site
                            </Link>
                            <Link :href="route('logout')" method="post" as="button"
                                  class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-t border-gray-100 dark:border-gray-800">
                                <LogOut class="w-4 h-4" />
                                Sign out
                            </Link>
                        </div>
                    </Transition>
                </div>
            </header>

            <!-- Page slot -->
            <main class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-950">
                <slot />
            </main>
        </div>

    </div>
</template>
