<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    ArrowLeft, Wrench, MapPin, Building2, User, Calendar,
    DollarSign, Clock, CheckCircle, XCircle, AlertTriangle,
    ExternalLink, Trash2, Tag, CheckCircle2
} from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    report:     Object,
    issueTypes: Object,
})

const page       = usePage()
const user       = computed(() => page.props.auth.user)
const canApprove = computed(() =>
    (props.report.can_manager_approve    && user.value?.permissions?.includes('approve-maintenance-manager')) ||
    (props.report.can_accountant_approve && user.value?.permissions?.includes('approve-maintenance-accountant')) ||
    (props.report.can_ceo_approve        && user.value?.permissions?.includes('approve-maintenance-ceo')) ||
    (props.report.can_make_payment       && user.value?.permissions?.includes('pay-maintenance'))
)

const formatPrice = (v) => v != null
    ? new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0 }).format(v)
    : '—'

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric'
}) : '—'

const formatDateTime = (d) => d ? new Date(d).toLocaleDateString('en-NG', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
}) : '—'

// Status config
const statusConfig = {
    pending:             { label: 'Pending Review',         color: 'amber' },
    manager_approved:    { label: 'Awaiting Accountant',    color: 'blue' },
    accountant_approved: { label: 'Awaiting CEO Approval',  color: 'indigo' },
    ceo_approved:        { label: 'Awaiting Payment',       color: 'purple' },
    completed:           { label: 'Completed',              color: 'green' },
    rejected:            { label: 'Rejected',               color: 'red' },
}

const statusClass = (status) => ({
    amber:  'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    blue:   'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    indigo: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
    purple: 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800',
    green:  'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
    red:    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
}[statusConfig[status]?.color ?? 'amber'])

// Approval timeline
const timelineSteps = computed(() => [
    {
        label:    'Submitted',
        by:       props.report.submitted_by?.name,
        at:       props.report.created_at,
        done:     true,
        active:   props.report.status === 'pending',
        declined: false,
    },
    {
        label:    'Manager Review',
        by:       props.report.manager_approved_by?.name,
        at:       props.report.manager_approved_at,
        done:     ['manager_approved','accountant_approved','ceo_approved','completed'].includes(props.report.status),
        active:   props.report.status === 'pending',
        declined: props.report.status === 'rejected' && !props.report.manager_approved_by,
    },
    {
        label:    'Cost Approval',
        by:       props.report.accountant_approved_by?.name,
        at:       props.report.accountant_approved_at,
        done:     ['accountant_approved','ceo_approved','completed'].includes(props.report.status),
        active:   props.report.status === 'manager_approved',
        declined: props.report.status === 'rejected' && !!props.report.manager_approved_by && !props.report.accountant_approved_by,
    },
    {
        label:    'CEO Approval',
        by:       props.report.ceo_approved_by?.name,
        at:       props.report.ceo_approved_at,
        done:     ['ceo_approved','completed'].includes(props.report.status),
        active:   props.report.status === 'accountant_approved',
        declined: props.report.status === 'rejected' && !!props.report.accountant_approved_by && !props.report.ceo_approved_by,
    },
    {
        label:    'Payment',
        by:       props.report.payment_made_by?.name,
        at:       props.report.payment_made_at,
        done:     props.report.status === 'completed',
        active:   props.report.status === 'ceo_approved',
        declined: false,
    },
])

// Lightbox
const lightboxOpen  = ref(false)
const lightboxIndex = ref(0)
const openLightbox  = (i) => { lightboxIndex.value = i; lightboxOpen.value = true }

// Approve/reject form
const approveForm = useForm({ action: '', notes: '', actual_cost: '' })
function submitApproval(action) {
    approveForm.action = action
    approveForm.post(route('manage.maintenance.approve', props.report.id), {
        preserveScroll: true,
    })
}

// Delete
const showDeleteModal = ref(false)
const isDeleting      = ref(false)
function deleteReport() {
    isDeleting.value = true
    router.delete(route('manage.maintenance.destroy', props.report.id), {
        onFinish: () => { isDeleting.value = false; showDeleteModal.value = false },
    })
}
</script>

<template>
    <Head :title="`Maintenance — ${report.title}`" />

    <div class="p-6 lg:p-8">

        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
            <Link
                :href="route('manage.maintenance.index')"
                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-light text-gray-900 dark:text-white truncate">{{ report.title }}</h1>
                    <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border', statusClass(report.status)]">
                        {{ statusConfig[report.status]?.label }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ report.building?.name }} · Submitted by {{ report.submitted_by?.name }} · {{ formatDate(report.created_at) }}
                </p>
            </div>
            <button
                v-if="report.status === 'pending' && report.submitted_by_id === user?.id"
                @click="showDeleteModal = true"
                class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all"
            >
                <Trash2 class="w-5 h-5" />
            </button>
        </div>

        <!-- Two column layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── LEFT COLUMN (2/3) ── -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Report Details -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-5">Report Details</h2>

                    <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1 flex items-center gap-1.5">
                                <Tag class="w-3 h-3" /> Issue Type
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ issueTypes[report.issue_type] ?? report.issue_type }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1 flex items-center gap-1.5">
                                <MapPin class="w-3 h-3" /> Location
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ report.location ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1 flex items-center gap-1.5">
                                <Calendar class="w-3 h-3" /> Target Date
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(report.repair_timeline) }}</p>
                        </div>
                        <div v-if="report.vendor">
                            <p class="text-xs text-gray-400 mb-1 flex items-center gap-1.5">
                                <Wrench class="w-3 h-3" /> Vendor
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ report.vendor?.name }}</p>
                        </div>
                    </div>

                    <div v-if="report.description" class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-400 mb-2">Description</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ report.description }}</p>
                    </div>

                    <div v-if="report.notes" class="mt-4">
                        <p class="text-xs text-gray-400 mb-2">Additional Notes</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ report.notes }}</p>
                    </div>
                </div>

                <!-- Cost Breakdown -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-5">Cost</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                            <p class="text-xs text-gray-400 mb-1">Estimated Cost</p>
                            <p class="text-xl font-light text-gray-900 dark:text-white">{{ formatPrice(report.estimated_cost) }}</p>
                        </div>
                        <div :class="report.actual_cost ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800/50'"
                             class="rounded-xl p-4">
                            <p class="text-xs text-gray-400 mb-1">Actual Cost</p>
                            <p :class="report.actual_cost ? 'text-green-700 dark:text-green-400' : 'text-gray-400'"
                               class="text-xl font-light">
                                {{ report.actual_cost ? formatPrice(report.actual_cost) : 'Pending' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Photos -->
                <div v-if="report.photo_urls?.length" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Photos</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div
                            v-for="(url, i) in report.photo_urls"
                            :key="i"
                            @click="openLightbox(i)"
                            class="relative aspect-[4/3] rounded-xl overflow-hidden cursor-pointer group bg-gray-100 dark:bg-gray-800"
                        >
                            <img :src="url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                <ExternalLink class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejection reason -->
                <div v-if="report.status === 'rejected' && report.rejection_reason"
                     class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-6">
                    <div class="flex items-center gap-2 mb-2">
                        <XCircle class="w-4 h-4 text-red-500" />
                        <h2 class="text-sm font-medium text-red-700 dark:text-red-400">Rejection Reason</h2>
                    </div>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ report.rejection_reason }}</p>
                    <p class="text-xs text-red-400 mt-2">Rejected by {{ report.rejected_by_role }}</p>
                </div>

            </div>

            <!-- ── RIGHT COLUMN (1/3) ── -->
            <div class="space-y-5">

                <!-- Approval Timeline -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                    <h2 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-5">Approval Progress</h2>

                    <div class="space-y-0">
                        <div v-for="(step, index) in timelineSteps" :key="step.label" class="flex gap-3">
                            <!-- Line + dot -->
                            <div class="flex flex-col items-center">
                                <div :class="[
                                    'w-7 h-7 rounded-full flex items-center justify-center border-2 flex-shrink-0 transition-all',
                                    step.declined ? 'bg-red-100 dark:bg-red-900/30 border-red-400' :
                                    step.done     ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white' :
                                    step.active   ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-400 animate-pulse' :
                                    'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                                ]">
                                    <XCircle      v-if="step.declined" class="w-3.5 h-3.5 text-red-500" />
                                    <CheckCircle2 v-else-if="step.done" class="w-3.5 h-3.5 text-white dark:text-gray-900" />
                                    <Clock        v-else-if="step.active" class="w-3 h-3 text-amber-500" />
                                    <div v-else class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600" />
                                </div>
                                <div v-if="index < timelineSteps.length - 1"
                                     :class="step.done ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-gray-800'"
                                     class="w-0.5 flex-1 min-h-[20px] my-1 transition-all" />
                            </div>

                            <!-- Content -->
                            <div class="pb-4 flex-1 min-w-0">
                                <p :class="[
                                    'text-sm font-medium',
                                    step.done || step.active ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'
                                ]">{{ step.label }}</p>
                                <p v-if="step.by" class="text-xs text-gray-500 mt-0.5">{{ step.by }}</p>
                                <p v-if="step.at" class="text-xs text-gray-400 mt-0.5">
                                    {{ new Date(step.at).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                                <p v-if="step.active && !step.done && !step.declined" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                    Awaiting action
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-3">
                    <h2 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Details</h2>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex items-center gap-2.5">
                            <Building2 class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-gray-900 dark:text-white">{{ report.building?.name }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <User class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-gray-900 dark:text-white">{{ report.submitted_by?.name }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <Calendar class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-gray-600 dark:text-gray-400">{{ formatDate(report.created_at) }}</span>
                        </div>
                        <div v-if="report.repair_timeline" class="flex items-center gap-2.5">
                            <Clock class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <span class="text-gray-600 dark:text-gray-400">Due {{ formatDate(report.repair_timeline) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Card -->
                <div v-if="canApprove"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-4">
                    <h2 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ report.can_make_payment ? 'Record Payment' : 'Your Action' }}
                    </h2>

                    <!-- Accountant: actual cost input -->
                    <div v-if="report.can_accountant_approve">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Actual Cost (₦) <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="approveForm.actual_cost"
                            type="number"
                            min="0"
                            placeholder="Enter actual cost"
                            class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                        />
                        <p v-if="approveForm.errors.actual_cost" class="text-xs text-red-600 mt-1">{{ approveForm.errors.actual_cost }}</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Notes
                            <span v-if="!report.can_make_payment" class="text-gray-400 font-normal">(required if rejecting)</span>
                        </label>
                        <textarea
                            v-model="approveForm.notes"
                            rows="3"
                            :placeholder="report.can_make_payment ? 'Payment reference or notes...' : 'Add notes or rejection reason...'"
                            class="w-full px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                        />
                        <p v-if="approveForm.errors.notes" class="text-xs text-red-600 mt-1">{{ approveForm.errors.notes }}</p>
                    </div>

                    <!-- Action buttons -->
                    <div class="space-y-2">
                        <button
                            @click="submitApproval('approve')"
                            :disabled="approveForm.processing"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-xl hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all"
                        >
                            <CheckCircle class="w-4 h-4" />
                            {{ report.can_make_payment ? 'Confirm Payment Made' : 'Approve' }}
                        </button>
                        <button
                            v-if="!report.can_make_payment"
                            @click="submitApproval('reject')"
                            :disabled="approveForm.processing"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium rounded-xl disabled:opacity-50 transition-all"
                        >
                            <XCircle class="w-4 h-4" />
                            Reject
                        </button>
                    </div>
                </div>

                <!-- Completed state -->
                <div v-if="report.status === 'completed'"
                     class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-5">
                    <div class="flex items-center gap-2 mb-1">
                        <CheckCircle2 class="w-4 h-4 text-green-600 dark:text-green-400" />
                        <p class="text-sm font-semibold text-green-700 dark:text-green-400">Completed</p>
                    </div>
                    <p class="text-xs text-green-600 dark:text-green-500">
                        Payment confirmed by {{ report.payment_made_by?.name }} on {{ formatDate(report.payment_made_at) }}
                    </p>
                    <p v-if="report.actual_cost" class="text-sm font-semibold text-green-700 dark:text-green-400 mt-2">
                        Final cost: {{ formatPrice(report.actual_cost) }}
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
        <div v-if="lightboxOpen"
             @click="lightboxOpen = false"
             class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center cursor-pointer">
            <img
                :src="report.photo_urls[lightboxIndex]"
                class="max-w-[90vw] max-h-[90vh] object-contain rounded-xl"
                @click.stop
            />
            <button
                v-if="lightboxIndex > 0"
                @click.stop="lightboxIndex--"
                class="absolute left-4 p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <ArrowLeft class="w-5 h-5" />
            </button>
            <button
                v-if="lightboxIndex < report.photo_urls.length - 1"
                @click.stop="lightboxIndex++"
                class="absolute right-4 p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <ArrowLeft class="w-5 h-5 rotate-180" />
            </button>
            <button
                @click="lightboxOpen = false"
                class="absolute top-4 right-4 p-2 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all">
                <XCircle class="w-5 h-5" />
            </button>
        </div>
    </Teleport>

    <!-- Delete Modal -->
    <ConfirmationModal
        :show="showDeleteModal"
        title="Delete Maintenance Report"
        message="Are you sure you want to delete this report? This cannot be undone."
        confirm-text="Delete"
        variant="danger"
        :processing="isDeleting"
        @confirm="deleteReport"
        @close="showDeleteModal = false"
    />
</template>
