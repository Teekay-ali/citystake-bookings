<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    vendor:     Object,
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

const inputClass = "w-full pl-3 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
const labelClass = "block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5"
const sectionHeadingClass = "text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4"
</script>

<template>
    <Head :title="isEdit ? 'Edit Vendor' : 'Add Vendor'" />

    <div class="p-6 lg:p-8">
        <div class="max-w-2xl">

            <!-- Back link -->
            <Link :href="route('manage.vendors.index')"
                  class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to Vendors
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">
                    {{ isEdit ? 'Edit Vendor' : 'Add Vendor' }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ isEdit ? 'Update vendor details and bank information' : 'Add a new vendor or contractor to your directory' }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">

                <!-- ── Basic Information ── -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 space-y-4">
                    <h2 :class="sectionHeadingClass">Basic Information</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label :class="labelClass">Full Name *</label>
                            <input v-model="form.name" type="text" :class="inputClass" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Company</label>
                            <input v-model="form.company" type="text" :class="inputClass" />
                        </div>
                    </div>

                    <div>
                        <label :class="labelClass">Category *</label>
                        <select v-model="form.category" :class="inputClass">
                            <option value="">Select category</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label :class="labelClass">Phone *</label>
                            <input v-model="form.phone" type="text" :class="inputClass" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">Email</label>
                            <input v-model="form.email" type="email" :class="inputClass" />
                        </div>
                    </div>

                    <div>
                        <label :class="labelClass">Address</label>
                        <textarea v-model="form.address" rows="2" :class="inputClass + ' resize-none'" />
                    </div>

                    <div>
                        <label :class="labelClass">Rating (1–5)</label>
                        <input v-model="form.rating" type="number" min="1" max="5" step="0.1" placeholder="e.g. 4.5" :class="inputClass" />
                    </div>
                </div>

                <!-- ── Bank Details ── -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 space-y-4">
                    <h2 :class="sectionHeadingClass">Bank Details</h2>

                    <div>
                        <label :class="labelClass">Bank Name</label>
                        <input v-model="form.bank_name" type="text" :class="inputClass" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label :class="labelClass">Account Number</label>
                            <input v-model="form.bank_account_number" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label :class="labelClass">Account Name</label>
                            <input v-model="form.bank_account_name" type="text" :class="inputClass" />
                        </div>
                    </div>
                </div>

                <!-- ── Notes & Status ── -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 space-y-4">
                    <h2 :class="sectionHeadingClass">Notes & Status</h2>

                    <div>
                        <label :class="labelClass">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Any additional details..." :class="inputClass + ' resize-none'" />
                    </div>

                    <div v-if="isEdit" class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Active</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Inactive vendors won't appear in maintenance reports</p>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active"
                                :class="form.is_active ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0">
                            <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-gray-900 transition-transform shadow-sm" />
                        </button>
                    </div>
                </div>

                <!-- ── Actions ── -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                        {{ form.processing ? 'Saving...' : (isEdit ? 'Save Changes' : 'Add Vendor') }}
                    </button>
                    <Link :href="route('manage.vendors.index')"
                          class="px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                        Cancel
                    </Link>
                </div>

            </form>
        </div>
    </div>
</template>
