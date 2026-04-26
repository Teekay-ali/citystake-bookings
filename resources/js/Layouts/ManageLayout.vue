<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import {
    LayoutDashboard, CalendarDays, Building2, Ban, BarChart3,
    Users, Grid3x3, Clock, Menu, X, LogOut, User,
    ShoppingCart, AlertTriangle, Wrench, Package, BookOpen,
    DollarSign, CheckSquare, MessageSquare, Sun, Moon,
    ChevronLeft, ChevronRight, FileText, ShieldCheck
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

// Sidebar scroll persistence
const navRef = ref(null)

watch(navRef, (el) => {
    if (el) el.scrollTop = parseInt(localStorage.getItem('sidebar-scroll') ?? '0')
})

function saveScroll() {
    if (navRef.value) localStorage.setItem('sidebar-scroll', navRef.value.scrollTop)
}

// ── Tooltip (nav item hover) ──────────────────────────────────
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
const userMenuAnchor = ref(null)
const userMenuEl = ref(null)

const { floatingStyles: userMenuStyles } = useFloating(userMenuAnchor, userMenuEl, {
    placement: 'right',
    strategy: 'fixed',
    middleware: [offset(8), shift({ padding: 8 }), flip()],
})

function toggleUserMenu() {
    showUserMenu.value = !showUserMenu.value
}

function closeUserMenu() {
    showUserMenu.value = false
}

function handleClickOutside(e) {
    if (
        userMenuEl.value && !userMenuEl.value.contains(e.target) &&
        userMenuAnchor.value && !userMenuAnchor.value.contains(e.target)
    ) {
        showUserMenu.value = false
    }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

// ── Flash toasts ──────────────────────────────────────────────
watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error)   toast.error(flash.error)
    if (flash?.info)    toast.info(flash.info)
    if (flash?.warning) toast.warning(flash.warning)
}, { deep: true, immediate: true })

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
            { label: 'Dashboard', icon: LayoutDashboard, route: dashboardRoute.value, match: 'manage.dashboard|manage.home' },
        ]
    },
    {
        label: 'Bookings',
        items: [
            { label: 'All Bookings',  icon: CalendarDays, route: 'manage.bookings.index',              match: 'manage.bookings.index|manage.bookings.create|manage.bookings.show|manage.bookings.check-in', permission: 'view-bookings' },
            { label: 'Availability',  icon: Grid3x3,      route: 'manage.availability.index',          match: 'manage.availability.*',               permission: 'manage-availability' },
            { label: 'Late Checkouts',icon: Clock,        route: 'manage.bookings.late-checkout.index', match: 'manage.bookings.late-checkout.index', permission: 'view-bookings', badge: pendingCount },
            { label: 'Calendar',      icon: CalendarDays, route: 'manage.bookings.calendar',           match: 'manage.bookings.calendar',            permission: 'view-bookings' },
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
            { label: 'Analytics',  icon: BarChart3,  route: 'manage.analytics.index',  match: 'manage.analytics.*',  permission: 'view-analytics' },
            { label: 'Financials', icon: DollarSign, route: 'manage.financials.index', match: 'manage.financials.*', permission: 'view-financials' },
        ]
    },
    {
        label: 'Team',
        items: [
            { label: 'Staff',         icon: Users,          route: 'manage.staff.index',         match: 'manage.staff.*',         permission: 'manage-staff' },
            { label: 'Staff Queries', icon: FileText,       route: 'manage.staff-queries.index', match: 'manage.staff-queries.*', permission: 'manage-staff-queries' },
            { label: 'Roles',         icon: ShieldCheck,    route: 'manage.roles.index',         match: 'manage.roles.*',         permission: 'manage-roles' },
            { label: 'Tasks',         icon: CheckSquare,    route: 'manage.tasks.index',         match: 'manage.tasks.*',         permission: 'view-tasks' },
            { label: 'Messages',      icon: MessageSquare,  route: 'manage.messages.index',      match: 'manage.messages.*',      badge: unreadMessages },
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
    'super-admin':        'Super Admin',
    'manager':            'Manager',
    'accountant':         'Accountant',
    'ceo':                'CEO',
    'head-of-procurement':'Head of Procurement',
    'receptionist':       'Receptionist',
    'staff':              'Staff',
}

const userPermissions = computed(() => page.props.auth.user?.permissions ?? [])

function canSeeItem(item) {
    if (!item.permission) return true
    return userPermissions.value.includes(item.permission)
}
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-gray-950 flex">

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

        <!-- Floating tooltip -->
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

        <!-- User menu — floating, sibling to tooltip, escapes sidebar -->
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="showUserMenu"
                 ref="userMenuEl"
                 :style="userMenuStyles"
                 class="fixed z-[9999] w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-xl overflow-hidden">

                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ user?.email }}</p>
                </div>

                <!-- Items -->
                <div class="py-1">
                    <button @click="toggleDark(); closeUserMenu()"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <Sun v-if="isDark" class="w-4 h-4" />
                        <Moon v-else class="w-4 h-4" />
                        {{ isDark ? 'Light mode' : 'Dark mode' }}
                    </button>
                    <Link :href="route('profile.edit')"
                          @click="closeUserMenu"
                          class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <User class="w-4 h-4" />
                        Profile Settings
                    </Link>
                </div>

                <!-- Sign out -->
                <div class="border-t border-gray-100 dark:border-gray-800 py-1">
                    <Link :href="route('logout')" method="post" as="button"
                          @click="closeUserMenu"
                          class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <LogOut class="w-4 h-4" />
                        Sign out
                    </Link>
                </div>
            </div>
        </Transition>

        <!-- ─── Sidebar ─── -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed ? 'lg:w-16' : 'lg:w-64',
                'fixed top-0 left-0 h-screen w-64 bg-white dark:bg-gray-950 z-50 flex flex-col transition-all duration-300 lg:translate-x-0'
            ]">

            <!-- Logo row — always h-16, logo always centered when collapsed -->
            <div class="h-16 flex items-center justify-between px-4 shrink-0">
                <Link :href="route('home')"
                      :class="collapsed ? 'mx-auto' : ''"
                      class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 bg-gray-900 dark:bg-white rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-white dark:text-gray-900 text-xs font-bold tracking-tight">CS</span>
                    </div>
                    <span v-if="!collapsed" class="font-semibold text-gray-900 dark:text-white text-sm truncate">CityStake</span>
                </Link>

                <!-- Mobile close button -->
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <X class="w-5 h-5" />
                </button>

                <!-- Desktop collapse button — only shown when expanded -->
                <button v-if="!collapsed"
                        @click="toggleCollapsed()"
                        :aria-expanded="!collapsed"
                        aria-label="Toggle sidebar"
                        class="hidden lg:flex p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <ChevronLeft class="w-4 h-4" />
                </button>
            </div>

            <!-- Expand button — only shown when collapsed, sits below logo in its own row -->
            <div v-if="collapsed"
                 class="hidden lg:flex justify-center pb-2 shrink-0">
                <button
                    @click="toggleCollapsed()"
                    aria-label="Expand sidebar"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>

            <!-- Nav items -->
            <nav ref="navRef" @scroll="saveScroll" class="flex-1 overflow-y-auto py-4 px-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                <template v-for="group in navGroups" :key="group.label">
                    <div v-if="group.items.some(item => canSeeItem(item) && !item.soon)" class="mb-4">

                        <!-- Group label — reduced weight so nav items lead visually -->
                        <p v-if="!collapsed"
                           class="text-xs font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider px-3 mb-1">
                            {{ group.label }}
                        </p>
                        <!-- Divider when collapsed -->
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

                                    <!-- Active nav link — filled icon box replaces left border -->
                                    <Link v-else
                                          :href="route(item.route)"
                                          @click="sidebarOpen = false"
                                          @mouseenter="(e) => onMouseEnter(item, e.currentTarget)"
                                          @mouseleave="onMouseLeave"
                                          :class="[
                                              isActive(item.match)
                                                  ? 'bg-gray-100 dark:bg-gray-800/60 text-gray-900 dark:text-white font-medium'
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
            <div class="p-2 shrink-0">
                <div :class="collapsed ? 'flex-col items-center' : 'items-center gap-3 px-2'"
                     class="flex py-2 bg-gray-100 dark:bg-gray-800/50 rounded-xl">

                    <!-- Avatar — click trigger for floating menu -->
                    <button ref="userMenuAnchor"
                            @click.stop="toggleUserMenu"
                            class="w-8 h-8 rounded-lg bg-gray-900 dark:bg-white flex items-center justify-center shrink-0 hover:opacity-80 transition-opacity cursor-pointer">
            <span class="text-white dark:text-gray-900 text-xs font-medium">
                {{ user?.name?.charAt(0)?.toUpperCase() }}
            </span>
                    </button>

                    <!-- Name + role — hidden when collapsed -->
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ roleLabels[user?.roles?.[0]] ?? 'Admin' }}
                        </p>
                    </div>

                    <!-- Notification bell — only when expanded -->
                    <NotificationBell v-if="!collapsed" dropdown-direction="up" />
                </div>
            </div>

        </aside>

        <!-- ─── Main content ─── -->
        <div
            :class="collapsed ? 'lg:ml-16' : 'lg:ml-64'"
            class="flex-1 flex flex-col min-w-0 transition-all duration-300">

            <!-- Mobile top bar -->
            <header class="h-16 bg-white dark:bg-gray-900 flex items-center justify-between px-4 lg:hidden shrink-0 sticky top-0 z-30">
                <button
                    @click="sidebarOpen = true"
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
