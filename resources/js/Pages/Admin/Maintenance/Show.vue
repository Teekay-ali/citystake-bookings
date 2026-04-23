<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Phone, CheckCircle2, Circle, XCircle } from 'lucide-vue-next'
import { computed } from 'vue'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    report:     Object,
    issueTypes: Object,
})

const user = computed(() => usePage().props.auth.user)

const approveForm = useForm({
    action:      'approve',
    notes:       '',
    actual_cost: props.report.estimated_cost ?? '',
})

function submitApproval(action) {
    approveForm.action = action
    approveForm.post(route('manage.maintenance.approve', props.report.id), {
        preserveScroll: true,
    })
}

const statusConfig = {
    pending:             { label: 'Awaiting Manager',    cls: 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700' },
    manager_approved:    { label: 'Awaiting Accountant', cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
    accountant_approved: { label: 'Awaiting CEO',        cls: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border border-violet-200 dark:border-violet-800' },
    ceo_approved:        { label: 'Awaiting Payment',    cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' },
    completed:           { label: 'Completed',           cls: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' },
    rejected:            { label: 'Rejected',            cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' },
}

const actionLabel = computed(() => {
    const roles = user.value?.roles ?? []
    if (props.report.can_manager_approve && (roles.includes('manager') || roles.includes('super-admin')))
        return 'Manager Approval'
    if (props.report.can_accountant_approve && (roles.includes('accountant') || roles.includes('super-admin')))
        return 'Accountant Approval'
    if (props.report.can_ceo_approve && (roles.includes('ceo') || roles.includes('super-admin')))
        return 'CEO Approval'
    if (props.report.can_make_payment && (roles.includes('accountant') || roles.includes('super-admin')))
        return 'Confirm Payment'
    return null
})

const canAct = computed(() => !!actionLabel.value)

const timelineSteps = computed(() => [
    { label: 'Submitted',           by: props.report.submitted_by?.name,          at: props.report.created_at,           done: true,                                   current: false },
    { label: 'Manager Approved',    by: props.report.manager_approved_by?.name,    at: props.report.manager_approved_at,    done: !!props.report.manager_approved_at,    current: props.report.status === 'pending' },
    { label: 'Accountant Approved', by: props.report.accountant_approved_by?.name, at: props.report.accountant_approved_at, done: !!props.report.accountant_approved_at, current: props.report.status === 'manager_approved' },
    { label: 'CEO Approved',        by: props.report.ceo_approved_by?.name,        at: props.report.ceo_approved_at,        done: !!props.report.ceo_approved_at,        current: props.report.status === 'accountant_approved' },
    { label: 'Payment Made',        by: props.report.payment_made_by?.name,        at: props.report.payment_made_at,        done: !!props.report.payment_made_at,        current: props.report.status === 'ceo_approved' },
])

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
}

function formatDateTime(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : '—'
}

function formatAmount(n) {
    return n ? '₦' + Number(n).toLocaleString('en-NG') : '—'
}

const inputClass = "w-full pl-3 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head :title="report.title" />

    <div class="p-6 lg:p-8">

        <!-- Back link -->
        <Link :href="route('manage.maintenance.index')"
              class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors">
            <ArrowLeft class="w-3.5 h-3.5" />
            Back to Maintenance
        </Link>

        <!-- ── Hero header ── -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span :class="[statusConfig[report.status]?.cls, 'text-xs font-medium px-2 py-0.5 rounded-lg']">
                            {{ statusConfig[report.status]?.label }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ issueTypes[report.issue_type] }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ report.building?.name }}</span>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">{{ report.title }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Submitted by {{ report.submitted_by?.name }} · {{ formatDate(report.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Cost strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Estimated Cost</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ formatAmount(report.estimated_cost) }}</p>
                </div>
                <div v-if="report.actual_cost">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Actual Cost</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ formatAmount(report.actual_cost) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Repair Timeline</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ formatDate(report.repair_timeline) }}</p>
                </div>
                <div v-if="report.location">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Location</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ report.location }}</p>
                </div>
            </div>
        </div>

        <!-- ── Main grid ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Left: description + vendor + photos -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Description -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Description</h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ report.description }}</p>
                    <div v-if="report.notes" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Additional Notes</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ report.notes }}</p>
                    </div>
                </div>

                <!-- Vendor -->
                <div v-if="report.vendor"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Vendor</h2>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ report.vendor.name }}</p>
                            <p v-if="report.vendor.company" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ report.vendor.company }}</p>
                        </div>
                        <a :href="'tel:' + report.vendor.phone"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all flex-shrink-0">
                            <Phone class="w-3 h-3" />
                            {{ report.vendor.phone }}
                        </a>
                    </div>
                </div>

                <!-- Photos -->
                <div v-if="report.photo_urls?.length"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Photos</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <a v-for="(url, i) in report.photo_urls" :key="i" :href="url" target="_blank">
                            <img :src="url" class="w-full aspect-square object-cover rounded-lg hover:opacity-90 transition-all" />
                        </a>
                    </div>
                </div>

                <!-- Approval timeline -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Approval Timeline</h2>
                    <div class="relative">
                        <!-- Vertical line -->
                        <div class="absolute left-[7px] top-2 bottom-2 w-px bg-gray-200 dark:bg-gray-800"></div>

                        <div class="space-y-4">
                            <div v-for="step in timelineSteps" :key="step.label" class="flex items-start gap-3 relative">
                                <!-- Step icon -->
                                <div class="flex-shrink-0 mt-0.5 z-10">
                                    <CheckCircle2 v-if="step.done"
                                                  class="w-4 h-4 text-emerald-500" />
                                    <div v-else-if="step.current"
                                         class="w-4 h-4 rounded-full border-2 border-gray-900 dark:border-white bg-white dark:bg-gray-950 flex items-center justify-center">
                                        <div class="w-1.5 h-1.5 rounded-full bg-gray-900 dark:bg-white"></div>
                                    </div>
                                    <Circle v-else class="w-4 h-4 text-gray-200 dark:text-gray-700" />
                                </div>
                                <div class="flex-1 min-w-0 pb-1">
                                    <p :class="step.done ? 'text-gray-900 dark:text-white' : step.current ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-400 dark:text-gray-600'"
                                       class="text-sm font-medium">
                                        {{ step.label }}
                                        <span v-if="step.current" class="ml-2 text-xs font-normal text-amber-600 dark:text-amber-400">← Awaiting</span>
                                    </p>
                                    <p v-if="step.done && step.by" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        {{ step.by }} · {{ formatDateTime(step.at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rejected -->
                            <div v-if="report.status === 'rejected'" class="flex items-start gap-3">
                                <XCircle class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5 z-10" />
                                <div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Rejected</p>
                                    <p v-if="report.rejection_reason" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        {{ report.rejection_reason }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: action panel -->
            <div class="space-y-3">

                <!-- Action panel -->
                <div v-if="canAct && report.status !== 'completed' && report.status !== 'rejected'"
                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                    <h3 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">
                        {{ actionLabel }}
                    </h3>
                    <div class="space-y-3">
                        <div v-if="report.can_accountant_approve">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Actual Cost (₦) *</label>
                            <input v-model="approveForm.actual_cost" type="number" min="0" :class="inputClass" />
                            <p v-if="approveForm.errors.actual_cost" class="text-xs text-red-600 mt-1">{{ approveForm.errors.actual_cost }}</p>
                        </div>
                        <textarea
                            v-model="approveForm.notes"
                            rows="3"
                            placeholder="Notes (required if rejecting)"
                            :class="inputClass + ' resize-none'" />
                        <p v-if="approveForm.errors.notes" class="text-xs text-red-600">{{ approveForm.errors.notes }}</p>
                        <button
                            @click="submitApproval('approve')"
                            :disabled="approveForm.processing"
                            class="w-full px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                            {{ report.can_make_payment ? 'Confirm Payment Made' : 'Approve' }}
                        </button>
                        <button
                            v-if="report.status !== 'ceo_approved'"
                            @click="submitApproval('reject')"
                            :disabled="approveForm.processing"
                            class="w-full px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium disabled:opacity-50 transition-all">
                            Reject
                        </button>
                    </div>
                </div>

                <!-- Completed state -->
                <div v-if="report.status === 'completed'"
                     class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-1">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Completed</p>
                    </div>
                    <p class="text-xs text-emerald-600 dark:text-emerald-500">
                        Payment confirmed by {{ report.payment_made_by?.name }}
                    </p>
                </div>

                <!-- Rejected state -->
                <div v-if="report.status === 'rejected'"
                     class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-1">
                        <XCircle class="w-4 h-4 text-red-600 dark:text-red-400" />
                        <p class="text-sm font-semibold text-red-700 dark:text-red-400">Rejected</p>
                    </div>
                    <p v-if="report.rejection_reason" class="text-xs text-red-600 dark:text-red-500">
                        {{ report.rejection_reason }}
                    </p>
                </div>

            </div>
        </div>
    </div>
</template>
