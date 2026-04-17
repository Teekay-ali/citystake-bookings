<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import {
    LayoutDashboard, CalendarDays, Building2, Ban, BarChart3,
    Users, Grid3x3, Clock, Menu, X, LogOut,
    ShoppingCart, AlertTriangle, Wrench, Package, BookOpen,
    DollarSign, CheckSquare, MessageSquare, Sun, Moon,
    ChevronLeft, ChevronRight, FileText, ShieldCheck
} from 'lucide-vue-next'
// In the <script setup> imports section, add:
import NotificationBell from '@/Components/NotificationBell.vue'

const toast = useToast()

const page = usePage()
const user = computed(() => page.props.auth.user)
const pendingCount = computed(() => page.props.lateCheckoutPendingCount ?? 0)

const sidebarOpen = ref(false)       // mobile drawer
const collapsed = ref(false)          // desktop collapsed state
const isDark = ref(document.documentElement.classList.contains('dark'))

onMounted(() => {
    const flash = page.props.flash
    if (flash?.success) toast.success(flash.success)
    if (flash?.error) toast.error(flash.error)
    if (flash?.info) toast.info(flash.info)
    if (flash?.warning) toast.warning(flash.warning)
})

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error) toast.error(flash.error)
    if (flash?.info) toast.info(flash.info)
    if (flash?.warning) toast.warning(flash.warning)
}, { deep: true })

function toggleDark() {
    isDark.value = !isDark.value
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
}

const dashboardRoute = computed(() => {
    const roles = user.value?.roles ?? []
    return roles.includes('super-admin') || roles.includes('ceo')
        ? 'manage.dashboard'
        : 'manage.home'
})

const navGroups = computed(() => [
    {
        label: 'Overview',
        items: [
            {
                label: 'Dashboard',
                icon: LayoutDashboard,
                route: dashboardRoute.value,
                match: 'manage.dashboard|manage.home',
            },
        ]
    },
    {
        label: 'Bookings',
        items: [
            { label: 'All Bookings',   icon: CalendarDays, route: 'manage.bookings.index',              match: 'manage.bookings.*',                   permission: 'view-bookings' },
            { label: 'Availability',   icon: Grid3x3,      route: 'manage.availability.index',          match: 'manage.availability.*',               permission: 'manage-availability' },
            { label: 'Late Checkouts', icon: Clock,        route: 'manage.bookings.late-checkout.index', match: 'manage.bookings.late-checkout.index', permission: 'view-bookings', badge: pendingCount },
            { label: 'Calendar',       icon: CalendarDays, route: 'manage.bookings.calendar',           match: 'manage.bookings.calendar',            permission: 'view-bookings' },
        ]
    },
    {
        label: 'Properties',
        items: [
            { label: 'Properties',    icon: Building2, route: 'manage.properties.index',    match: 'manage.properties.*',    permission: 'manage-properties' },
            { label: 'Blocked Dates', icon: Ban,       route: 'manage.blocked-dates.index', match: 'manage.blocked-dates.*', permission: 'manage-blocked-dates' },
        ]
    },
    {
        label: 'Operations',
        items: [
            { label: 'Procurement', icon: ShoppingCart, route: 'manage.procurement.index', match: 'manage.procurement.*', permission: 'submit-procurement' },
            { label: 'Complaints',  icon: AlertTriangle, route: 'manage.complaints.index', match: 'manage.complaints.*',  permission: 'submit-complaints' },
            { label: 'Maintenance', icon: Wrench,        route: 'manage.maintenance.index', match: 'manage.maintenance.*', permission: 'submit-maintenance' },
            { label: 'Stock',       icon: Package,       route: 'manage.stock.index',       match: 'manage.stock.*',       permission: 'view-stock' },
            { label: 'Vendors',     icon: BookOpen,      route: 'manage.vendors.index',     match: 'manage.vendors.*',     permission: 'view-vendors' },
        ]
    },
    {
        label: 'Finance & Analytics',
        items: [
            { label: 'Occupancy',  icon: BarChart3,   route: 'manage.analytics.occupancy', match: 'manage.analytics.*',  permission: 'view-analytics' },
            { label: 'Financials', icon: DollarSign,  route: 'manage.financials.index',    match: 'manage.financials.*', permission: 'view-financials' },
        ]
    },
    {
        label: 'Team',
        items: [
            { label: 'Staff',         icon: Users,       route: 'manage.staff.index',         match: 'manage.staff.*',         permission: 'manage-staff' },
            { label: 'Staff Queries', icon: FileText,    route: 'manage.staff-queries.index', match: 'manage.staff-queries.*', permission: 'manage-staff-queries' },
            { label: 'Roles',         icon: ShieldCheck, route: 'manage.roles.index',         match: 'manage.roles.*',         permission: 'manage-roles' },
            { label: 'Tasks',         icon: CheckSquare, route: 'manage.tasks.index',         match: 'manage.tasks.*',         permission: 'view-tasks' },
            { label: 'Messages',      icon: MessageSquare, route: 'manage.messages.index',    match: 'manage.messages.*',      soon: true },
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
    'super-admin': 'Super Admin',
    'manager': 'Manager',
    'accountant': 'Accountant',
    'ceo': 'CEO',
    'head-of-procurement': 'Head of Procurement',
    'receptionist': 'Receptionist',
    'staff': 'Staff',
}

const userPermissions = computed(() => page.props.auth.user?.permissions ?? [])

function canSeeItem(item) {
    if (!item.permission) return true
    return userPermissions.value.includes(item.permission)
}

</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex">

        <!-- Mobile backdrop -->
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

        <!-- ─── Sidebar ─────────────────────────────────────────────── -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'lg:w-16' : 'lg:w-64',
                'fixed top-0 left-0 h-screen w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-50 flex flex-col transition-all duration-300 lg:translate-x-0'
            ]">

            <!-- Logo row -->
            <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-800 shrink-0">
                <Link v-if="!collapsed" :href="route('home')" class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 bg-gray-900 dark:bg-white rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-white dark:text-gray-900 text-xs font-bold">CS</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white text-sm truncate">CityStake</span>
                </Link>
                <Link v-else :href="route('home')"
                      class="w-7 h-7 bg-gray-900 dark:bg-white rounded-lg flex items-center justify-center mx-auto">
                    <span class="text-white dark:text-gray-900 text-xs font-bold">CS</span>
                </Link>

                <!-- Close button mobile / collapse button desktop -->
                <button @click="sidebarOpen = false"
                        class="lg:hidden p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <X class="w-5 h-5" />
                </button>
                <button @click="collapsed = !collapsed"
                        class="hidden lg:flex p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <ChevronLeft v-if="!collapsed" class="w-4 h-4" />
                    <ChevronRight v-else class="w-4 h-4" />
                </button>
            </div>

            <!-- Nav items -->
            <nav class="flex-1 overflow-y-auto py-4 px-2">
                <template v-for="group in navGroups" :key="group.label">
                    <div v-if="group.items.some(item => canSeeItem(item) && !item.soon)" class="mb-4">

                    <!-- Group label — hidden when collapsed -->
                    <p v-if="!collapsed"
                       class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 mb-1">
                        {{ group.label }}
                    </p>
                    <!-- Divider when collapsed -->
                    <div v-else class="border-t border-gray-100 dark:border-gray-800 mx-2 mb-2" />

                    <div class="space-y-0.5">
                        <template v-for="item in group.items" :key="item.label">
                            <template v-if="canSeeItem(item)">
                                <!-- Soon (disabled) -->
                                <div v-if="item.soon"
                                     :title="collapsed ? item.label : ''"
                                     :class="collapsed ? 'justify-center px-0' : 'px-3'"
                                     class="flex items-center gap-3 py-2 rounded-lg text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed">
                                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                                    <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
                                    <span v-if="!collapsed"
                                          class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 px-1.5 py-0.5 rounded-full">
                                        Soon
                                    </span>
                                </div>

                                <!-- Active nav link -->
                                <Link v-else
                                      :href="route(item.route)"
                                      :title="collapsed ? item.label : ''"
                                      @click="sidebarOpen = false"
                                      :class="[
                                        isActive(item.match)
                                            ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white',
                                        collapsed ? 'justify-center px-0' : 'px-3'
                                    ]"
                                      class="flex items-center gap-3 py-2 rounded-lg text-sm font-medium transition-all">
                                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                                    <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
                                    <span v-if="!collapsed && item.badge && item.badge > 0"
                                          class="bg-amber-500 text-white text-xs font-medium w-5 h-5 rounded-full flex items-center justify-center">
                                        {{ item.badge }}
                                    </span>
                                    <!-- Collapsed badge dot -->
                                    <span v-if="collapsed && item.badge && item.badge > 0"
                                          class="absolute top-1 right-1 w-2 h-2 bg-amber-500 rounded-full" />
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
                </template>
            </nav>

            <!-- User footer -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-2 shrink-0 overflow-visible relative">                <div :class="collapsed ? 'flex-col items-center' : 'items-center gap-3 px-2'"
                     class="flex py-2">

                    <!-- Avatar -->
                    <div class="w-8 h-8 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center shrink-0">
                        <span class="text-white dark:text-gray-900 text-xs font-medium">
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </span>
                    </div>

                    <!-- Name + role — hidden when collapsed -->
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ roleLabels[user?.roles?.[0]] ?? 'Admin' }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div :class="collapsed ? 'flex-col mt-2' : ''" class="flex items-center gap-1">
                        <NotificationBell dropdown-direction="up" />
                        <button @click="toggleDark"
                                :title="isDark ? 'Light mode' : 'Dark mode'"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                            <Sun v-if="isDark" class="w-4 h-4" />
                            <Moon v-else class="w-4 h-4" />
                        </button>
                        <Link :href="route('logout')" method="post" as="button"
                              title="Sign out"
                              class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>

                </div>
            </div>
        </aside>

        <!-- ─── Main content ────────────────────────────────────────── -->
        <div
            :class="collapsed ? 'lg:ml-16' : 'lg:ml-64'"
            class="flex-1 flex flex-col min-w-0 transition-all duration-300">

            <!-- Mobile top bar -->
            <header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 lg:hidden shrink-0 sticky top-0 z-30">
                <button @click="sidebarOpen = true"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    <Menu class="w-5 h-5" />
                </button>
                <Link :href="route('home')" class="font-semibold text-gray-900 dark:text-white text-sm">
                    CityStake
                </Link>
                <NotificationBell />
            </header>

            <!-- Page slot -->
            <main class="flex-1 overflow-auto">
                <slot />
            </main>
        </div>

    </div>
</template>
