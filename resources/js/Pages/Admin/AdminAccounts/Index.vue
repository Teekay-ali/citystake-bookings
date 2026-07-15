<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { ShieldCheck, UserX, UserCheck, KeyRound } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    admins: Array,
})

const toggleModal   = ref(null)
const resetModal    = ref(null)
const isProcessing  = ref(false)

const currentUserId = window.__page?.props?.auth?.user?.id

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric',
}) : '-'

const roleLabels = {
    'super-admin': 'Super Admin',
    'manager':     'Manager',
    'ceo':         'CEO',
    'accountant':  'Accountant',
}

function toggleActive() {
    isProcessing.value = true
    router.post(route('manage.admin-accounts.toggle-active', toggleModal.value.id), {}, {
        onFinish: () => { isProcessing.value = false; toggleModal.value = null },
    })
}

function resetPassword() {
    isProcessing.value = true
    router.post(route('manage.admin-accounts.reset-password', resetModal.value.id), {}, {
        onFinish: () => { isProcessing.value = false; resetModal.value = null },
    })
}
</script>

<template>
    <Head title="Admin Accounts" />

    <div class="p-6 lg:p-8 space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Admin Accounts</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage administrator and privileged accounts</p>
        </div>

        <!-- List -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
            <table class="hidden md:table w-full text-sm">
                <thead class="border-b border-gray-100 dark:border-gray-800">
                <tr class="text-left">
                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Name</th>
                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Role</th>
                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Joined</th>
                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="admin in admins" :key="admin.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            {{ admin.name }}
                            <span v-if="admin.id === $page.props.auth.user.id"
                                  class="text-xs text-gray-400">(you)</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ admin.email }}</p>
                    </td>
                    <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-full">
                                <ShieldCheck class="w-3 h-3" />
                                {{ roleLabels[admin.roles?.[0]?.name] ?? admin.roles?.[0]?.name ?? 'Admin' }}
                            </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-xs">{{ formatDate(admin.created_at) }}</td>
                    <td class="px-6 py-4">
                            <span :class="admin.is_active
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                                : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'"
                                  class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border">
                                {{ admin.is_active ? 'Active' : 'Inactive' }}
                            </span>
                    </td>
                    <td class="px-6 py-4">
                        <div v-if="admin.id !== $page.props.auth.user.id" class="flex items-center gap-2">
                            <button
                                @click="toggleModal = admin"
                                :class="admin.is_active
                                        ? 'text-red-500 hover:text-red-700'
                                        : 'text-green-500 hover:text-green-700'"
                                class="p-1.5 transition-colors"
                                :title="admin.is_active ? 'Deactivate' : 'Activate'"
                            >
                                <component :is="admin.is_active ? UserX : UserCheck" class="w-4 h-4" />
                            </button>
                            <button
                                @click="resetModal = admin"
                                class="p-1.5 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                title="Reset Password"
                            >
                                <KeyRound class="w-4 h-4" />
                            </button>
                        </div>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Mobile -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="admin in admins" :key="admin.id" class="p-4 space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ admin.name }}</p>
                            <p class="text-xs text-gray-500">{{ admin.email }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(admin.created_at) }}</p>
                        </div>
                        <span :class="admin.is_active
                            ? 'bg-green-50 text-green-700 border-green-200'
                            : 'bg-red-50 text-red-700 border-red-200'"
                              class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border">
                            {{ admin.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div v-if="admin.id !== $page.props.auth.user.id" class="flex gap-2">
                        <button @click="toggleModal = admin"
                                :class="admin.is_active ? 'text-red-500' : 'text-green-500'"
                                class="text-xs font-medium">
                            {{ admin.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <span class="text-gray-300">·</span>
                        <button @click="resetModal = admin" class="text-xs font-medium text-gray-500">
                            Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Modal -->
    <ConfirmationModal
        v-if="toggleModal"
        :show="!!toggleModal"
        :title="toggleModal.is_active ? 'Deactivate Account' : 'Activate Account'"
        :message="toggleModal.is_active
            ? `This will prevent ${toggleModal.name} from accessing the admin panel.`
            : `This will restore ${toggleModal.name}'s access to the admin panel.`"
        :confirm-text="toggleModal.is_active ? 'Deactivate' : 'Activate'"
        :variant="toggleModal.is_active ? 'danger' : 'default'"
        :processing="isProcessing"
        @confirm="toggleActive"
        @close="toggleModal = null"
    />

    <!-- Reset Password Modal -->
    <ConfirmationModal
        v-if="resetModal"
        :show="!!resetModal"
        title="Reset Password"
        :message="`This will generate a new random password for ${resetModal.name}. The new password will be shown once - make sure to copy it.`"
        confirm-text="Reset Password"
        variant="danger"
        :processing="isProcessing"
        @confirm="resetPassword"
        @close="resetModal = null"
    />
</template>
