<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import DocumentManager from '@/Components/DocumentManager.vue'
import {
    ArrowLeft, Clock, CheckCircle, XCircle, Banknote,
    User, Building2, Calendar, MessageSquare, Upload,
    FileText, ExternalLink
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    approval:            Object,
    canDecide:           Boolean,
    canMarkPaid:         Boolean,
    canManageDocuments:  Boolean,
})

const approvalDocuments = ref(props.approval.documents ?? [])

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
}) : '—'

const statusConfig = {
    pending:  { label: 'Pending',  color: 'amber', icon: Clock },
    approved: { label: 'Approved', color: 'blue',  icon: CheckCircle },
    declined: { label: 'Declined', color: 'red',   icon: XCircle },
    paid:     { label: 'Paid',     color: 'green', icon: Banknote },
}

const statusClass = (status) => ({
    amber: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    blue:  'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    red:   'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    green: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
}[statusConfig[status]?.color ?? 'amber'])

// CEO decision form
const decideForm = useForm({
    decision:    '',
    ceo_comment: '',
})

function decide(decision) {
    decideForm.decision = decision
    decideForm.post(route('manage.payment-approvals.decide', props.approval.id), {
        preserveScroll: true,
    })
}

// Mark paid form
const paidForm  = useForm({
    payment_reference: '',
    payment_evidence:  null,
})
const evidencePreview = ref(null)

function onEvidenceChange(e) {
    const file = e.target.files[0]
    if (!file) return
    paidForm.payment_evidence = file
    if (file.type.startsWith('image/')) {
        evidencePreview.value = URL.createObjectURL(file)
    } else {
        evidencePreview.value = null
    }
}

function submitPaid() {
    paidForm.post(route('manage.payment-approvals.mark-paid', props.approval.id), {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<template>

    <Head :title="`Payment Request - ${approval.type_label}`" />

    <div class="p-6 lg:p-8 max-w-3xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('manage.payment-approvals.index')"
                    class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-light text-gray-900 dark:text-white">
                            {{ approval.type_label }} Payment
                        </h1>
                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border', statusClass(approval.status)]">
                            <component :is="statusConfig[approval.status]?.icon" class="w-3 h-3" />
                            {{ statusConfig[approval.status]?.label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Requested by {{ approval.requested_by?.name }} · {{ formatDate(approval.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Details Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Request Details</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Recipient</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ approval.recipient_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Amount</p>
                        <p class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(approval.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Building</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ approval.building?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Payment Type</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ approval.type_label }}</p>
                    </div>
                </div>

                <template v-if="approval.bank_name || approval.account_number">
                    <div class="border-t border-gray-100 dark:border-gray-900 pt-4">
                        <p class="text-xs text-gray-500 mb-3 uppercase tracking-wide font-medium">Account Details</p>
                        <div class="grid grid-cols-3 gap-4">
                            <div v-if="approval.bank_name">
                                <p class="text-xs text-gray-500 mb-1">Bank</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ approval.bank_name }}</p>
                            </div>
                            <div v-if="approval.account_number">
                                <p class="text-xs text-gray-500 mb-1">Account Number</p>
                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ approval.account_number }}</p>
                            </div>
                            <div v-if="approval.account_name">
                                <p class="text-xs text-gray-500 mb-1">Account Name</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ approval.account_name }}</p>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="approval.description" class="border-t border-gray-100 dark:border-gray-900 pt-4">
                    <p class="text-xs text-gray-500 mb-1">Description</p>
                    <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ approval.description }}</p>
                </div>
            </div>

            <!-- Supporting Documents — separate card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                    Supporting Documents
                </h2>
                <DocumentManager
                    model-type="payment-approval"
                    :model-id="approval.id"
                    :initial="approvalDocuments"
                    :readonly="!canManageDocuments"
                    @updated="val => approvalDocuments = val"
                />
            </div>

            <!-- CEO Decision Card -->
            <div
                v-if="approval.ceo_comment || approval.approved_by"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
            >
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">CEO Decision</h2>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                        <User class="w-4 h-4 text-gray-500" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ approval.approved_by?.name }}</p>
                        <p class="text-xs text-gray-500 mb-2">{{ formatDate(approval.approved_at) }}</p>
                        <p v-if="approval.ceo_comment" class="text-sm text-gray-700 dark:text-gray-300">
                            {{ approval.ceo_comment }}
                        </p>
                        <p v-else class="text-sm text-gray-400 italic">No comment provided.</p>
                    </div>
                </div>
            </div>

            <!-- Payment Evidence -->
            <div
                v-if="approval.payment_evidence_url"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
            >
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Payment Evidence</h2>
                <div class="space-y-3">
                    <div v-if="approval.payment_reference">
                        <p class="text-xs text-gray-500 mb-1">Payment Reference</p>
                        <p class="font-medium text-gray-900 dark:text-white font-mono">{{ approval.payment_reference }}</p>
                    </div>
                    <a
                        :href="approval.payment_evidence_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                        <ExternalLink class="w-4 h-4" />
                        View Evidence
                    </a>
                    <p class="text-xs text-gray-500">Paid {{ formatDate(approval.paid_at) }}</p>
                </div>
            </div>

            <!-- CEO: Approve / Decline -->
            <div
                v-if="canDecide"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4"
            >
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Your Decision</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Comment <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea
                        v-model="decideForm.ceo_comment"
                        rows="3"
                        placeholder="Add a note for the accountant..."
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none text-sm"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="decide('approved')"
                        :disabled="decideForm.processing"
                        class="flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                    >
                        <CheckCircle class="w-4 h-4" />
                        Approve
                    </button>
                    <button
                        type="button"
                        @click="decide('declined')"
                        :disabled="decideForm.processing"
                        class="flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                    >
                        <XCircle class="w-4 h-4" />
                        Decline
                    </button>
                </div>
            </div>

            <!-- Accountant: Mark as Paid -->
            <div
                v-if="canMarkPaid"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4"
            >
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mark as Paid</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Reference <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="paidForm.payment_reference"
                        type="text"
                        placeholder="Bank transfer ref, receipt number..."
                        class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Evidence <span class="text-red-500">*</span>
                    </label>
                    <div
                        class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
                    >
                        <input
                            type="file"
                            accept="image/jpeg,image/jpg,image/png,application/pdf"
                            @change="onEvidenceChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                        <div v-if="evidencePreview">
                            <img :src="evidencePreview" class="max-h-32 mx-auto rounded-lg object-contain mb-2" />
                            <p class="text-xs text-gray-500">Click to change</p>
                        </div>
                        <div v-else-if="paidForm.payment_evidence">
                            <FileText class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ paidForm.payment_evidence.name }}</p>
                            <p class="text-xs text-gray-400 mt-1">Click to change</p>
                        </div>
                        <div v-else>
                            <Upload class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                            <p class="text-sm text-gray-600 dark:text-gray-400">Upload receipt or screenshot</p>
                            <p class="text-xs text-gray-400 mt-1">JPEG, PNG or PDF · Max 5MB</p>
                        </div>
                    </div>
                    <p v-if="paidForm.errors.payment_evidence" class="mt-1 text-sm text-red-600">{{ paidForm.errors.payment_evidence }}</p>
                </div>

                <button
                    type="button"
                    @click="submitPaid"
                    :disabled="paidForm.processing || !paidForm.payment_evidence"
                    class="flex items-center gap-2 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-full hover:bg-gray-700 dark:hover:bg-gray-100 transition-all disabled:opacity-50"
                >
                    <Banknote class="w-4 h-4" />
                    {{ paidForm.processing ? 'Saving...' : 'Confirm Payment' }}
                </button>
            </div>

        </div>

</template>
