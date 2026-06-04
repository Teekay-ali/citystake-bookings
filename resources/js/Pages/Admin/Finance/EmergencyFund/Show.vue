<script setup>
import { ref, onUnmounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    ArrowLeft, ShieldAlert, Clock, CheckCircle, XCircle,
    Banknote, User, AlertTriangle, Upload, FileText, ExternalLink, Trash2
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    efRequest:        Object,
    fundBalance:      Object,
    canManagerDecide: Boolean,
    canCeoDecide:     Boolean,
    canMarkPaid:      Boolean,
    canDelete:        Boolean,
})

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
}) : '—'

const statusConfig = {
    pending:          { label: 'Pending Manager Review', color: 'amber',  icon: Clock },
    manager_approved: { label: 'Awaiting CEO Approval',  color: 'blue',   icon: CheckCircle },
    approved:         { label: 'CEO Approved',           color: 'indigo', icon: CheckCircle },
    declined:         { label: 'Declined',               color: 'red',    icon: XCircle },
    paid:             { label: 'Paid',                   color: 'green',  icon: Banknote },
}

const statusClass = (status) => ({
    amber:  'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    blue:   'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    indigo: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
    red:    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    green:  'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
}[statusConfig[status]?.color ?? 'amber'])

// Manager decision form
const managerForm = useForm({ decision: '', manager_comment: '' })
const managerDecide = (decision) => {
    managerForm.decision = decision
    managerForm.post(route('manage.emergency-fund.manager-decide', props.efRequest.id), {
        preserveScroll: true,
    })
}

// CEO decision form
const ceoForm = useForm({ decision: '', ceo_comment: '' })
const ceoDecide = (decision) => {
    ceoForm.decision = decision
    ceoForm.post(route('manage.emergency-fund.decide', props.efRequest.id), {
        preserveScroll: true,
    })
}

// Mark paid form
const paidForm        = useForm({ payment_reference: '', payment_evidence: null })
const paymentPreview  = ref(null)

function onPaymentEvidence(e) {
    const file = e.target.files[0]
    if (!file) return
    paidForm.payment_evidence = file
    if (paymentPreview.value) URL.revokeObjectURL(paymentPreview.value)
    paymentPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null
}

onUnmounted(() => {
    if (paymentPreview.value) URL.revokeObjectURL(paymentPreview.value)
})

function submitPaid() {
    paidForm.post(route('manage.emergency-fund.mark-paid', props.efRequest.id), {
        forceFormData: true,
        preserveScroll: true,
    })
}

// Delete
const showDeleteModal = ref(false)
const isDeleting      = ref(false)

function deleteRequest() {
    isDeleting.value = true
    router.delete(route('manage.emergency-fund.destroy', props.efRequest.id), {
        onFinish: () => { isDeleting.value = false; showDeleteModal.value = false },
    })
}

// Approval timeline steps
const timelineSteps = [
    {
        label:     'Submitted',
        sublabel:  props.efRequest.requested_by?.name,
        date:      props.efRequest.created_at,
        done:      true,
        active:    props.efRequest.status === 'pending',
    },
    {
        label:     'Manager Review',
        sublabel:  props.efRequest.manager_approved_by?.name ?? 'Pending',
        date:      props.efRequest.manager_approved_at,
        done:      ['manager_approved', 'approved', 'paid'].includes(props.efRequest.status),
        declined:  props.efRequest.status === 'declined' && !props.efRequest.manager_approved_by,
        active:    props.efRequest.status === 'pending',
    },
    {
        label:     'CEO Approval',
        sublabel:  props.efRequest.approved_by?.name ?? 'Pending',
        date:      props.efRequest.approved_at,
        done:      ['approved', 'paid'].includes(props.efRequest.status),
        declined:  props.efRequest.status === 'declined' && !!props.efRequest.manager_approved_by,
        active:    props.efRequest.status === 'manager_approved',
    },
    {
        label:     'Payment',
        sublabel:  props.efRequest.status === 'paid' ? 'Completed' : 'Pending',
        date:      props.efRequest.paid_at,
        done:      props.efRequest.status === 'paid',
        active:    props.efRequest.status === 'approved',
    },
]
</script>

<template>
    <Head :title="`Emergency Fund — ${efRequest.reason}`" />

    <div class="p-6 lg:p-8 max-w-3xl space-y-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link
                :href="route('manage.emergency-fund.index')"
                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-light text-gray-900 dark:text-white flex items-center gap-2">
                        <ShieldAlert class="w-5 h-5 text-red-500" />
                        Emergency Fund Request
                    </h1>
                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border', statusClass(efRequest.status)]">
                        <component :is="statusConfig[efRequest.status]?.icon" class="w-3 h-3" />
                        {{ statusConfig[efRequest.status]?.label }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ efRequest.building?.name }} · Submitted by {{ efRequest.requested_by?.name }} · {{ formatDate(efRequest.created_at) }}
                </p>
            </div>

            <!-- Delete button -->
            <button
                v-if="canDelete"
                @click="showDeleteModal = true"
                class="flex items-center gap-2 px-4 py-2 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-medium rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex-shrink-0"
            >
                <Trash2 class="w-4 h-4" />
                Delete
            </button>
        </div>

        <!-- Approval Timeline -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-6">Approval Progress</h2>
            <div class="flex items-start gap-0">
                <template v-for="(step, index) in timelineSteps" :key="step.label">
                    <div class="flex flex-col items-center flex-1">
                        <!-- Circle -->
                        <div :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all',
                            step.declined ? 'bg-red-100 dark:bg-red-900/30 border-red-400 dark:border-red-600' :
                            step.done     ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white' :
                            step.active   ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-600 animate-pulse' :
                            'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                        ]">
                            <XCircle  v-if="step.declined" class="w-4 h-4 text-red-500" />
                            <CheckCircle v-else-if="step.done" class="w-4 h-4 text-white dark:text-gray-900" />
                            <Clock v-else-if="step.active" class="w-3.5 h-3.5 text-amber-500" />
                            <div v-else class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600" />
                        </div>

                        <!-- Label -->
                        <p :class="['text-xs font-medium mt-2 text-center', step.done || step.active ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600']">
                            {{ step.label }}
                        </p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-600 text-center mt-0.5">{{ step.sublabel }}</p>
                        <p v-if="step.date" class="text-[10px] text-gray-400 text-center mt-0.5">
                            {{ new Date(step.date).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' }) }}
                        </p>
                    </div>

                    <!-- Connector line -->
                    <div v-if="index < timelineSteps.length - 1"
                         :class="[
                             'flex-1 h-0.5 mt-4 transition-all',
                             timelineSteps[index + 1].done || timelineSteps[index].done
                                 ? 'bg-gray-900 dark:bg-white'
                                 : 'bg-gray-200 dark:bg-gray-800'
                         ]" />
                </template>
            </div>
        </div>

        <!-- Request Details -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Request Details</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Amount</p>
                    <p class="text-2xl font-light text-gray-900 dark:text-white">{{ formatPrice(efRequest.amount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Building</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ efRequest.building?.name }}</p>
                </div>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Reason</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ efRequest.reason }}</p>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Why It's Urgent</p>
                <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ efRequest.urgency_description }}</p>
            </div>

            <!-- Evidence -->
            <div v-if="efRequest.evidence_url">
                <p class="text-xs text-gray-500 mb-2">Supporting Evidence</p>
                <a
                    :href="efRequest.evidence_url"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                    <ExternalLink class="w-4 h-4" />
                    View Evidence
                </a>
            </div>
        </div>

        <!-- Fund Balance context -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Fund Balance This Month</h2>
            <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-3">
                <div
                    :style="{ width: fundBalance.percent + '%' }"
                    :class="[
                        'h-full rounded-full transition-all',
                        fundBalance.percent >= 90 ? 'bg-red-500' :
                        fundBalance.percent >= 60 ? 'bg-amber-500' : 'bg-green-500'
                    ]"
                />
            </div>
            <div class="grid grid-cols-3 gap-3 text-center text-sm">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Monthly Limit</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ formatPrice(fundBalance.limit) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Used</p>
                    <p class="font-medium text-red-600 dark:text-red-400">{{ formatPrice(fundBalance.used) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Remaining</p>
                    <p class="font-medium text-green-600 dark:text-green-400">{{ formatPrice(fundBalance.remaining) }}</p>
                </div>
            </div>
        </div>

        <!-- Manager comments (if acted) -->
        <div
            v-if="efRequest.manager_approved_by"
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
        >
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Manager's Decision</h2>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                    <User class="w-4 h-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ efRequest.manager_approved_by?.name }}</p>
                    <p class="text-xs text-gray-500 mb-2">{{ formatDate(efRequest.manager_approved_at) }}</p>
                    <p v-if="efRequest.manager_comment" class="text-sm text-gray-700 dark:text-gray-300">{{ efRequest.manager_comment }}</p>
                    <p v-else class="text-sm text-gray-400 italic">No comment provided.</p>
                </div>
            </div>
        </div>

        <!-- CEO comments (if acted) -->
        <div
            v-if="efRequest.approved_by"
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6"
        >
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">CEO's Decision</h2>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                    <User class="w-4 h-4 text-gray-500" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ efRequest.approved_by?.name }}</p>
                    <p class="text-xs text-gray-500 mb-2">{{ formatDate(efRequest.approved_at) }}</p>
                    <p v-if="efRequest.ceo_comment" class="text-sm text-gray-700 dark:text-gray-300">{{ efRequest.ceo_comment }}</p>
                    <p v-else class="text-sm text-gray-400 italic">No comment provided.</p>
                </div>
            </div>
        </div>

        <!-- Payment evidence (if paid) -->
        <div v-if="efRequest.status === 'paid'" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Payment Record</h2>
            <div class="space-y-3">
                <div v-if="efRequest.payment_reference">
                    <p class="text-xs text-gray-500 mb-1">Payment Reference</p>
                    <p class="font-medium text-gray-900 dark:text-white font-mono">{{ efRequest.payment_reference }}</p>
                </div>
                <div v-if="efRequest.evidence_url">
                    <a
                        :href="efRequest.evidence_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                        <ExternalLink class="w-4 h-4" />
                        View Payment Evidence
                    </a>
                </div>
                <p class="text-xs text-gray-500">Paid {{ formatDate(efRequest.paid_at) }}</p>
            </div>
        </div>

        <!-- Manager: Approve / Decline -->
        <div v-if="canManagerDecide" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Your Review (Manager)</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Comment <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                    v-model="managerForm.manager_comment"
                    rows="3"
                    placeholder="Add a note for the CEO or accountant..."
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                />
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="managerDecide('manager_approved')"
                    :disabled="managerForm.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                >
                    <CheckCircle class="w-4 h-4" />
                    Approve & Forward to CEO
                </button>
                <button
                    type="button"
                    @click="managerDecide('declined')"
                    :disabled="managerForm.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                >
                    <XCircle class="w-4 h-4" />
                    Decline
                </button>
            </div>
        </div>

        <!-- CEO: Final Approval -->
        <div v-if="canCeoDecide" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Final Approval (CEO)</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Comment <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                    v-model="ceoForm.ceo_comment"
                    rows="3"
                    placeholder="Add a note for the accountant..."
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                />
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="ceoDecide('approved')"
                    :disabled="ceoForm.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                >
                    <CheckCircle class="w-4 h-4" />
                    Final Approval
                </button>
                <button
                    type="button"
                    @click="ceoDecide('declined')"
                    :disabled="ceoForm.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-full transition-all disabled:opacity-50"
                >
                    <XCircle class="w-4 h-4" />
                    Decline
                </button>
            </div>
        </div>

        <!-- Accountant: Mark as Paid -->
        <div v-if="canMarkPaid" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mark as Paid</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Payment Reference <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                    v-model="paidForm.payment_reference"
                    type="text"
                    placeholder="Receipt number, transfer ref..."
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Payment Evidence <span class="text-red-500">*</span>
                </label>
                <div class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    <input
                        type="file"
                        accept="image/jpeg,image/jpg,image/png,application/pdf"
                        @change="onPaymentEvidence"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    <div v-if="paymentPreview">
                        <img :src="paymentPreview" class="max-h-32 mx-auto rounded-lg object-contain mb-2" />
                        <p class="text-xs text-gray-500">Click to change</p>
                    </div>
                    <div v-else-if="paidForm.payment_evidence">
                        <FileText class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ paidForm.payment_evidence.name }}</p>
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

    <!-- Delete Modal -->
    <ConfirmationModal
        :show="showDeleteModal"
        title="Delete Emergency Fund Request"
        message="Are you sure you want to delete this request? This cannot be undone."
        confirm-text="Delete"
        variant="danger"
        :processing="isDeleting"
        @confirm="deleteRequest"
        @close="showDeleteModal = false"
    />
</template>
