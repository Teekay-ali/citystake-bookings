<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
    vendor: Object,
    categories: Object,
})

const isEdit = !!props.vendor

const form = useForm({
    name:                props.vendor?.name ?? '',
    company:             props.vendor?.company ?? '',
    category:            props.vendor?.category ?? '',
    phone:               props.vendor?.phone ?? '',
    email:               props.vendor?.email ?? '',
    address:             props.vendor?.address ?? '',
    bank_name:           props.vendor?.bank_name ?? '',
    bank_account_number: props.vendor?.bank_account_number ?? '',
    bank_account_name:   props.vendor?.bank_account_name ?? '',
    rating:              props.vendor?.rating ?? '',
    notes:               props.vendor?.notes ?? '',
    is_active:           props.vendor?.is_active ?? true,
})

function submit() {
    if (isEdit) {
        form.put(route('manage.vendors.update', props.vendor.id))
    } else {
        form.post(route('manage.vendors.store'))
    }
}
</script>

<template>
    <ManageLayout>
        <Head :title="isEdit ? 'Edit Vendor' : 'Add Vendor'" />

        <div class="p-6 lg:p-8 max-w-2xl">

            <Link :href="route('manage.vendors.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" />
                Back to Vendors
            </Link>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">
                {{ isEdit ? 'Edit Vendor' : 'Add Vendor' }}
            </h1>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Basic Information</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full Name *</label>
                            <input v-model="form.name" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Company</label>
                            <input v-model="form.company" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category *</label>
                        <select v-model="form.category"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select category</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Phone *</label>
                            <input v-model="form.phone" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                            <input v-model="form.email" type="email"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Address</label>
                        <textarea v-model="form.address" rows="2"
                                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Rating (1–5)</label>
                        <input v-model="form.rating" type="number" min="1" max="5" step="0.1" placeholder="e.g. 4.5"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Bank Details</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bank Name</label>
                        <input v-model="form.bank_name" type="text"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Number</label>
                            <input v-model="form.bank_account_number" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Name</label>
                            <input v-model="form.bank_account_name" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>
                </div>

                <!-- Notes & Status -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Notes & Status</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Any additional details..."
                                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                    </div>

                    <div v-if="isEdit" class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Active</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Inactive vendors won't appear in maintenance reports</p>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active"
                                :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        {{ form.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Vendor') }}
                    </button>
                    <Link :href="route('manage.vendors.index')"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </ManageLayout>
</template>
