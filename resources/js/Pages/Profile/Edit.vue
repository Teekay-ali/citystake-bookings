<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { User, Shield, Bell, Trash2 } from 'lucide-vue-next'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateEmailPreferencesForm from './Partials/UpdateEmailPreferencesForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})

const user = usePage().props.auth.user

const initials = computed(() =>
    (user.name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
)

const roleLabel = computed(() => {
    const r = user.roles?.[0]
    if (!r) return user.is_staff || user.is_admin ? 'Staff' : 'Guest'
    return r.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
})

const sections = [
    { id: 'profile',  label: 'Profile',       icon: User,  sub: 'Name, email & phone' },
    { id: 'security', label: 'Security',      icon: Shield, sub: 'Password' },
    { id: 'email',    label: 'Notifications', icon: Bell,  sub: 'Email preferences' },
    { id: 'danger',   label: 'Danger zone',   icon: Trash2, sub: 'Delete account' },
]
const active = ref('profile')
const current = computed(() => sections.find(s => s.id === active.value))
</script>

<template>
    <AppLayout :hide-footer="true">
        <Head title="Profile Settings" />

        <div class="bg-gray-50 dark:bg-gray-950 min-h-screen py-8 lg:py-12">
            <div class="max-w-5xl mx-auto px-4 lg:px-8">

                <!-- ── Identity header ── -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 sm:p-6 mb-5 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gray-900 dark:bg-white flex items-center justify-center shrink-0">
                        <span class="text-lg font-semibold text-white dark:text-gray-900">{{ initials }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white truncate">{{ user.name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                    </div>
                    <span class="hidden sm:inline-flex text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 shrink-0">
                        {{ roleLabel }}
                    </span>
                </div>

                <div class="lg:grid lg:grid-cols-[15rem_1fr] lg:gap-5 lg:items-start">

                    <!-- ── Section nav ── -->
                    <nav class="flex lg:flex-col gap-1 overflow-x-auto pb-2 lg:pb-0 mb-4 lg:mb-0 lg:sticky lg:top-6">
                        <button v-for="s in sections" :key="s.id" @click="active = s.id"
                                :class="active === s.id
                                    ? (s.id === 'danger'
                                        ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                                        : 'bg-white dark:bg-gray-800/60 text-gray-900 dark:text-white shadow-sm ring-1 ring-gray-900/5 dark:ring-0')
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-white/70 dark:hover:bg-gray-800/40 hover:text-gray-900 dark:hover:text-white'"
                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all shrink-0 lg:w-full text-left">
                            <component :is="s.icon" class="w-4 h-4 shrink-0"
                                       :class="s.id === 'danger' && active === s.id ? 'text-red-500' : ''" />
                            <span class="flex-1 min-w-0">
                                <span class="block">{{ s.label }}</span>
                                <span class="hidden lg:block text-xs font-normal text-gray-400 dark:text-gray-500 truncate">{{ s.sub }}</span>
                            </span>
                        </button>
                    </nav>

                    <!-- ── Active section card ── -->
                    <div class="bg-white dark:bg-gray-900 border rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden"
                         :class="active === 'danger' ? 'border-red-200/80 dark:border-red-900/50' : 'border-gray-200/80 dark:border-gray-800'">

                        <!-- Card header -->
                        <div class="px-5 sm:px-6 py-4 border-b"
                             :class="active === 'danger' ? 'border-red-100 dark:border-red-900/40' : 'border-gray-100 dark:border-gray-800'">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                     :class="active === 'danger' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-800'">
                                    <component :is="current.icon" class="w-4 h-4"
                                               :class="active === 'danger' ? 'text-red-500' : 'text-gray-600 dark:text-gray-300'" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ current.label }}</h2>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ current.sub }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card body -->
                        <div class="p-5 sm:p-6">
                            <UpdateProfileInformationForm v-if="active === 'profile'"
                                :must-verify-email="mustVerifyEmail" :status="status" />
                            <UpdatePasswordForm v-else-if="active === 'security'" />
                            <UpdateEmailPreferencesForm v-else-if="active === 'email'" />
                            <DeleteUserForm v-else-if="active === 'danger'" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
