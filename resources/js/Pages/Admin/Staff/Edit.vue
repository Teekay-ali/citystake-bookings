<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
    staff: Object,
    roles: Array,
    buildings: Array,
})

const form = useForm({
    name:                  props.staff.name,
    email:                 props.staff.email,
    phone:                 props.staff.phone ?? '',
    password:              '',
    password_confirmation: '',
    role:                  props.staff.role ?? '',
    building_ids:          props.staff.building_ids ?? [],
    is_active:             props.staff.is_active,
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

function submit() {
    form.put(route('manage.staff.update', props.staff.id))
}

function toggleBuilding(id) {
    const idx = form.building_ids.indexOf(id)
    if (idx === -1) {
        form.building_ids.push(id)
    } else {
        form.building_ids.splice(idx, 1)
    }
}
</script>

<template>
    <ManageLayout>
        <Head title="Edit Staff Member" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-2xl mx-auto px-6 lg:px-8">

                <Link :href="route('manage.staff.index')"
                      class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-8 transition-all">
                    <ArrowLeft class="w-4 h-4" /> Back to Staff
                </Link>

                <h1 class="text-3xl font-light text-gray-900 dark:text-white mb-8">Edit Staff Member</h1>

                <form @submit.prevent="submit" class="space-y-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name *</label>
                        <input v-model="form.name" type="text"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email *</label>
                        <input v-model="form.email" type="email"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                        <input v-model="form.phone" type="text"
                               class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>

                    <!-- Password optional on edit -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                New Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span>
                            </label>
                            <input v-model="form.password" type="password"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
                            <input v-model="form.password_confirmation" type="password"
                                   class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role *</label>
                        <select v-model="form.role"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select a role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">
                                {{ roleLabels[role.name] ?? role.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Building Access * <span class="text-gray-400 font-normal">(select one or more)</span>
                        </label>
                        <div class="grid grid-cols-1 gap-2">
                            <label v-for="building in buildings" :key="building.id"
                                   :class="form.building_ids.includes(building.id)
                                    ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                                    : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                                   class="flex items-center gap-3 px-4 py-3 border rounded-xl cursor-pointer transition-all">
                                <input type="checkbox" :value="building.id"
                                       :checked="form.building_ids.includes(building.id)"
                                       @change="toggleBuilding(building.id)"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ building.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.building_ids" class="mt-1 text-sm text-red-600">{{ form.errors.building_ids }}</p>
                    </div>

                    <!-- Active toggle -->
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Account Status</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Inactive staff cannot log in</p>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active"
                                :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                        </button>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                                class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <Link :href="route('manage.staff.index')"
                              class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </ManageLayout>
</template>
