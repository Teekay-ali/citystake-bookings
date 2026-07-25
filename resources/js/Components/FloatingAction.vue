<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Plus, CalendarPlus, UserPlus, ShoppingCart } from 'lucide-vue-next'

const page = usePage()

// Per-page primary action. Keyed by the current (Ziggy) route name.
//  - `href`  → navigate to a create route
//  - `event` → dispatch a window event the page listens for (opens a modal)
// Add a page's main action here; pages with no obvious single action are
// intentionally omitted so the button simply doesn't appear there.
const actions = {
    'manage.bookings.index':    { label: 'New booking', icon: CalendarPlus, permission: 'create-bookings',    href: 'manage.bookings.create' },
    'manage.procurement.index': { label: 'New request', icon: ShoppingCart, permission: 'submit-procurement', href: 'manage.procurement.create' },
    'manage.staff.index':       { label: 'Add staff',   icon: UserPlus,     permission: 'manage-staff',       event: 'fab:create' },
}

const permissions = computed(() => page.props.auth?.user?.permissions ?? [])

const current = computed(() => {
    for (const [name, action] of Object.entries(actions)) {
        if (route().current(name)) return action
    }
    return null
})

const visible = computed(() =>
    !!current.value && (!current.value.permission || permissions.value.includes(current.value.permission))
)

function trigger() {
    const action = current.value
    if (!action) return
    if (action.href)  router.visit(route(action.href))
    else if (action.event) window.dispatchEvent(new CustomEvent(action.event))
}
</script>

<template>
    <!-- Mobile / tablet only: on desktop the page's inline top-right button is in reach. -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-90"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-90"
    >
        <button
            v-if="visible"
            @click="trigger"
            :aria-label="current.label"
            :title="current.label"
            class="lg:hidden fixed bottom-6 right-5 z-40 w-14 h-14 rounded-full flex items-center justify-center
                   bg-gray-900 dark:bg-white text-white dark:text-gray-900
                   shadow-lg shadow-gray-900/25 dark:shadow-black/40
                   ring-1 ring-black/5 dark:ring-white/10
                   active:scale-90 hover:shadow-xl transition-all duration-150"
        >
            <component :is="current.icon" class="w-6 h-6" />
        </button>
    </Transition>
</template>
