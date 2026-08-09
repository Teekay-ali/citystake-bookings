<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Send, Info } from 'lucide-vue-next'
import BankSelect from '@/Components/BankSelect.vue'

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
    bank_name:            '',
    account_number:       '',
    account_name:         '',
    supporting_document:  null,
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

// Shared styles, matched to the rest of the /manage forms (e.g. procurement).
const cardClass    = 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4'
const sectionClass = 'text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider'
const labelClass   = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5'
const inputClass   = 'w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white'
const errClass     = 'mt-1 text-xs text-red-600'

const documentPreview = ref(null)

function onDocumentChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.supporting_document = file
    if (documentPreview.value) URL.revokeObjectURL(documentPreview.value)
    documentPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null
}

onUnmounted(() => {
    if (documentPreview.value) URL.revokeObjectURL(documentPreview.value)
})

function submit() {
    form.post(route('manage.payment-approvals.store'))
}

</script>

<template>

    <Head title="New Payment Request" />

    <div class="p-6 lg:p-8 max-w-2xl">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-8">
                <Link
                    :href="route('manage.payment-approvals.index')"
                    class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors shrink-0"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight">New Payment Request</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Submit a payment for CEO approval</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Payment details -->
                <div :class="cardClass">
                    <h2 :class="sectionClass">Payment Details</h2>

                    <div v-if="buildings.length > 1">
                        <label :class="labelClass">Building</label>
                        <select v-model="form.building_id" :class="inputClass">
                            <option value="">Select building</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.building_id" :class="errClass">{{ form.errors.building_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label :class="labelClass">Payment Type</label>
                            <select v-model="form.type" :class="inputClass">
                                <option value="">Select type</option>
                                <option v-for="t in paymentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <p v-if="form.errors.type" :class="errClass">{{ form.errors.type }}</p>
                        </div>
                        <div v-if="form.type === 'miscellaneous'">
                            <label :class="labelClass">Specify Type</label>
                            <input v-model="form.custom_type" type="text" placeholder="e.g. Staff Welfare, Supplies…" :class="inputClass" />
                            <p v-if="form.errors.custom_type" :class="errClass">{{ form.errors.custom_type }}</p>
                        </div>
                    </div>

                    <div>
                        <label :class="labelClass">Recipient Name</label>
                        <input v-model="form.recipient_name" type="text" placeholder="Full name of person or organization" :class="inputClass" />
                        <p v-if="form.errors.recipient_name" :class="errClass">{{ form.errors.recipient_name }}</p>
                    </div>

                    <div>
                        <label :class="labelClass">Amount (₦)</label>
                        <input v-model="form.amount" type="number" min="1" step="1" placeholder="0" :class="inputClass" />
                        <p v-if="form.amount" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ formatPrice(form.amount) }}</p>
                        <p v-if="form.errors.amount" :class="errClass">{{ form.errors.amount }}</p>
                    </div>

                    <div>
                        <label :class="labelClass">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <textarea v-model="form.description" rows="3" placeholder="Provide any additional context or justification…" :class="[inputClass, 'resize-none']" />
                        <p v-if="form.errors.description" :class="errClass">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Account details -->
                <div :class="cardClass">
                    <h2 :class="sectionClass">Account Details <span class="text-gray-400 font-normal normal-case tracking-normal">(optional)</span></h2>
                    <div>
                        <label :class="labelClass">Bank Name</label>
                        <BankSelect v-model="form.bank_name" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label :class="labelClass">Account Number</label>
                            <input v-model="form.account_number" type="text" placeholder="0123456789" maxlength="10" :class="inputClass" />
                        </div>
                        <div>
                            <label :class="labelClass">Account Name</label>
                            <input v-model="form.account_name" type="text" placeholder="Full account name" :class="inputClass" />
                        </div>
                    </div>
                </div>

                <!-- Supporting documents -->
                <div :class="cardClass">
                    <h2 :class="sectionClass">Supporting Documents</h2>
                    <div class="flex items-start gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 rounded-xl text-sm text-gray-500 dark:text-gray-400">
                        <Info class="w-4 h-4 mt-0.5 flex-shrink-0" />
                        Documents can be attached after submitting the request.
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        <Send class="w-4 h-4" />
                        {{ form.processing ? 'Submitting…' : 'Submit for Approval' }}
                    </button>
                    <Link :href="route('manage.payment-approvals.index')"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>

</template>
