<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, MapPin, User, Calendar, Wrench, Phone } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    report: Object,
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
    pending:              { label: 'Awaiting Manager',    class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    manager_approved:     { label: 'Awaiting Accountant', class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    accountant_approved:  { label: 'Awaiting CEO',        class: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400' },
    ceo_approved:         { label: 'Awaiting Payment',    class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    completed:            { label: 'Completed',           class: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' },
    rejected:             { label: 'Rejected',            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

// What action label to show based on current status + user role
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
</script>

<template>
    <ManageLayout>
        <Head :title="report.title" />

        <div class="p-6 lg:p-8 max-w-4xl">

            <Link :href="route('manage.maintenance.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Maintenance
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span :class="[statusConfig[report.status]?.class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                        {{ statusConfig[report.status]?.label }}
                    </span>
                    <span class="text-xs text-gray-400">{{ issueTypes[report.issue_type] }}</span>
                </div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ report.title }}</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Report Details</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-4">{{ report.description }}</p>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Building</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ report.building?.name }}</p>
                            </div>
                            <div v-if="report.location">
                                <p class="text-xs text-gray-400 mb-0.5">Location</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ report.location }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Estimated Cost</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatAmount(report.estimated_cost) }}</p>
                            </div>
                            <div v-if="report.actual_cost">
                                <p class="text-xs text-gray-400 mb-0.5">Actual Cost</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatAmount(report.actual_cost) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Repair Timeline</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(report.repair_timeline) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Submitted By</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ report.submitted_by?.name }}</p>
                            </div>
                        </div>

                        <div v-if="report.notes" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-xs text-gray-400 mb-1">Notes</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ report.notes }}</p>
                        </div>
                    </div>

                    <!-- Vendor -->
                    <div v-if="report.vendor"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Vendor</h2>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ report.vendor.name }}</p>
                                <p v-if="report.vendor.company" class="text-sm text-gray-500 dark:text-gray-400">{{ report.vendor.company }}</p>
                            </div>
                            <a :href="'tel:' + report.vendor.phone"
                               class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                <Phone class="w-3.5 h-3.5" />
                                {{ report.vendor.phone }}
                            </a>
                        </div>
                    </div>

                    <!-- Photos -->
                    <div v-if="report.photo_urls?.length"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Photos</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <a v-for="(url, i) in report.photo_urls" :key="i" :href="url" target="_blank">
                                <img :src="url" class="w-full aspect-square object-cover rounded-xl hover:opacity-90 transition-all" />
                            </a>
                        </div>
                    </div>

                    <!-- Approval timeline -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Approval Timeline</h2>
                        <div class="space-y-3">
                            <div v-for="step in [
                                { label: 'Submitted', by: report.submitted_by?.name, at: report.created_at, done: true },
                                { label: 'Manager Approved', by: report.manager_approved_by?.name, at: report.manager_approved_at, done: !!report.manager_approved_at },
                                { label: 'Accountant Approved', by: report.accountant_approved_by?.name, at: report.accountant_approved_at, done: !!report.accountant_approved_at },
                                { label: 'CEO Approved', by: report.ceo_approved_by?.name, at: report.ceo_approved_at, done: !!report.ceo_approved_at },
                                { label: 'Payment Made', by: report.payment_made_by?.name, at: report.payment_made_at, done: !!report.payment_made_at },
                            ]" :key="step.label"
                                 class="flex items-start gap-3">
                                <div :class="step.done ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"
                                     class="w-2 h-2 rounded-full mt-1.5 shrink-0" />
                                <div>
                                    <p :class="step.done ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'"
                                       class="text-sm font-medium">{{ step.label }}</p>
                                    <p v-if="step.done && step.by" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ step.by }} · {{ formatDateTime(step.at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rejected -->
                            <div v-if="report.status === 'rejected'"
                                 class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-red-500" />
                                <div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Rejected</p>
                                    <p v-if="report.rejection_reason" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ report.rejection_reason }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">

                    <!-- Action panel -->
                    <div v-if="canAct && report.status !== 'completed' && report.status !== 'rejected'"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ actionLabel }}</h3>

                        <div class="space-y-3">
                            <!-- Actual cost input for accountant -->
                            <div v-if="report.can_accountant_approve">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Actual Cost (₦) *</label>
                                <input v-model="approveForm.actual_cost" type="number" min="0"
                                       class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            </div>

                            <textarea v-model="approveForm.notes" rows="2"
                                      placeholder="Notes (required if rejecting)"
                                      class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />

                            <button @click="submitApproval('approve')" :disabled="approveForm.processing"
                                    class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium disabled:opacity-50 transition-all">
                                {{ report.can_make_payment ? 'Confirm Payment Made' : 'Approve' }}
                            </button>
                            <button v-if="report.status !== 'ceo_approved'"
                                    @click="submitApproval('reject')" :disabled="approveForm.processing"
                                    class="w-full px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium disabled:opacity-50 transition-all">
                                Reject
                            </button>
                        </div>
                    </div>

                    <!-- Completed state -->
                    <div v-if="report.status === 'completed'"
                         class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-1">✓ Completed</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500">
                            Payment confirmed by {{ report.payment_made_by?.name }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
