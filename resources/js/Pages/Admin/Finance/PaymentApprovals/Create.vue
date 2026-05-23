<script setup>
import { ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Send } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings: Array,
})

const form = useForm({
    building_id:    props.buildings.length === 1 ? props.buildings[0].id : '',
    type:           '',
    custom_type:    '',
    recipient_name: '',
    amount:         '',
    description:    '',
})

const paymentTypes = [
    { value: 'salary',         label: 'Salary' },
    { value: 'bonus',          label: 'Bonus' },
    { value: 'vendor_payment', label: 'Vendor Payment' },
    { value: 'utility',        label: 'Utility Bill' },
    { value: 'maintenance',    label: 'Maintenance' },
    { value: 'miscellaneous',  label: 'Miscellaneous / Other' },
]

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

function submit() {
    form.post(route('manage.payment-approvals.store'))
}
</script>

<template>

    <Head title="New Payment Request" />

    <div class="p-6 lg:p-8 max-w-2xl">

            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link
                    :href="route('manage.payment-approvals.index')"
                    class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-light text-gray-900 dark:text-white">New Payment Request</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Submit a payment for CEO approval</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Building -->
                <div v-if="buildings.length > 1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Building</label>
                    <select
                        v-model="form.building_id"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    >
                        <option value="">Select building</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <p v-if="form.errors.building_id" class="mt-1 text-sm text-red-600">{{ form.errors.building_id }}</p>
                </div>

                <!-- Payment Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Type</label>
                    <select
                        v-model="form.type"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    >
                        <option value="">Select type</option>
                        <option v-for="t in paymentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Custom Type -->
                <div v-if="form.type === 'miscellaneous'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Specify Payment Type</label>
                    <input
                        v-model="form.custom_type"
                        type="text"
                        placeholder="e.g. Staff Welfare, Office Supplies..."
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    />
                    <p v-if="form.errors.custom_type" class="mt-1 text-sm text-red-600">{{ form.errors.custom_type }}</p>
                </div>

                <!-- Recipient -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient Name</label>
                    <input
                        v-model="form.recipient_name"
                        type="text"
                        placeholder="Full name of person or organization"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    />
                    <p v-if="form.errors.recipient_name" class="mt-1 text-sm text-red-600">{{ form.errors.recipient_name }}</p>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount (₦)</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        min="1"
                        step="1"
                        placeholder="0"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    />
                    <p v-if="form.amount" class="mt-1 text-xs text-gray-500">{{ formatPrice(form.amount) }}</p>
                    <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description
                        <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Provide any additional context or justification..."
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4">
                    <Link
                        :href="route('manage.payment-approvals.index')"
                        class="px-6 py-3 border-2 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-full hover:border-gray-300 transition-all"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2 px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full hover:bg-gray-700 dark:hover:bg-gray-100 transition-all disabled:opacity-50"
                    >
                        <Send class="w-4 h-4" />
                        {{ form.processing ? 'Submitting...' : 'Submit for Approval' }}
                    </button>
                </div>
            </form>
        </div>

</template>
