<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Phone, Mail } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    procurement: Object,
})

const user = computed(() => usePage().props.auth.user)

const approveForm = useForm({
    action: 'approve',
    notes:  '',
})

function submitApproval(action) {
    approveForm.action = action
    approveForm.post(route('manage.procurement.approve', props.procurement.id), {
        preserveScroll: true,
    })
}

const statusConfig = {
    pending:             { label: 'Awaiting Accountant', class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
    accountant_approved: { label: 'Awaiting CEO',        class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' },
    ceo_approved:        { label: 'Awaiting Purchase',   class: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400' },
    purchased:           { label: 'Awaiting Receipt',    class: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' },
    completed:           { label: 'Completed',           class: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' },
    rejected:            { label: 'Rejected',            class: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' },
}

const actionLabel = computed(() => {
    const roles = user.value?.roles ?? []
    if (props.procurement.can_accountant_approve && (roles.includes('accountant') || roles.includes('super-admin')))
        return 'Accountant Approval'
    if (props.procurement.can_ceo_approve && (roles.includes('ceo') || roles.includes('super-admin')))
        return 'CEO Approval'
    if (props.procurement.can_mark_purchased && (roles.includes('head-of-procurement') || roles.includes('super-admin')))
        return 'Confirm Items Purchased'
    if (props.procurement.can_confirm_receipt && (roles.includes('manager') || roles.includes('super-admin')))
        return 'Confirm Receipt'
    return null
})

const canAct = computed(() => !!actionLabel.value)

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG')
}

function formatDateTime(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : '—'
}
</script>

<template>
    <ManageLayout>
        <Head :title="procurement.title" />

        <div class="p-6 lg:p-8 max-w-4xl">

            <Link :href="route('manage.procurement.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Procurement
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="text-xs font-mono text-gray-400">{{ procurement.reference }}</span>
                    <span :class="[statusConfig[procurement.status]?.class, 'text-xs font-medium px-2.5 py-1 rounded-full']">
                        {{ statusConfig[procurement.status]?.label }}
                    </span>
                </div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ procurement.title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ procurement.building?.name }} · Submitted by {{ procurement.submitted_by?.name }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Items table -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Items</h2>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs text-gray-500 font-medium">Item</th>
                                <th class="text-right px-6 py-3 text-xs text-gray-500 font-medium">Qty</th>
                                <th class="text-right px-6 py-3 text-xs text-gray-500 font-medium">Unit Price</th>
                                <th class="text-right px-6 py-3 text-xs text-gray-500 font-medium">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in procurement.items" :key="item.id">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                    <p v-if="item.description" class="text-xs text-gray-500 dark:text-gray-400">{{ item.description }}</p>
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-400">{{ item.quantity }}</td>
                                <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-400">{{ formatAmount(item.unit_price) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900 dark:text-white">{{ formatAmount(item.total_price) }}</td>
                            </tr>
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200 dark:border-gray-700">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">Total</td>
                                <td class="px-6 py-3 text-right text-base font-bold text-gray-900 dark:text-white">
                                    {{ formatAmount(procurement.total_amount) }}
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Justification / notes -->
                    <div v-if="procurement.justification || procurement.notes"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-3">
                        <div v-if="procurement.justification">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Justification</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ procurement.justification }}</p>
                        </div>
                        <div v-if="procurement.notes">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Notes</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ procurement.notes }}</p>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div v-if="procurement.vendor || procurement.supplier_name"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Supplier</h2>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ procurement.vendor?.name ?? procurement.supplier_name }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1 text-sm text-gray-500 dark:text-gray-400">
                                <a v-if="procurement.vendor?.phone || procurement.supplier_phone"
                                   :href="'tel:' + (procurement.vendor?.phone ?? procurement.supplier_phone)"
                                   class="flex items-center gap-1.5 hover:text-gray-900 dark:hover:text-white transition-all">
                                    <Phone class="w-3.5 h-3.5" />
                                    {{ procurement.vendor?.phone ?? procurement.supplier_phone }}
                                </a>
                                <span v-if="procurement.supplier_email" class="flex items-center gap-1.5">
                                    <Mail class="w-3.5 h-3.5" />
                                    {{ procurement.supplier_email }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Approval timeline -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Approval Timeline</h2>
                        <div class="space-y-3">
                            <div v-for="step in [
                                { label: 'Submitted', by: procurement.submitted_by?.name, at: procurement.created_at, done: true },
                                { label: 'Accountant Approved', by: procurement.accountant_approved_by?.name, at: procurement.accountant_approved_at, done: !!procurement.accountant_approved_at },
                                { label: 'CEO Approved', by: procurement.ceo_approved_by?.name, at: procurement.ceo_approved_at, done: !!procurement.ceo_approved_at },
                                { label: 'Items Purchased', by: procurement.purchased_by?.name, at: procurement.purchased_at, done: !!procurement.purchased_at },
                                { label: 'Receipt Confirmed', by: procurement.receipt_confirmed_by?.name, at: procurement.receipt_confirmed_at, done: !!procurement.receipt_confirmed_at },
                            ]" :key="step.label" class="flex items-start gap-3">
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

                            <div v-if="procurement.status === 'rejected'" class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-red-500" />
                                <div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Rejected</p>
                                    <p v-if="procurement.rejection_reason" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ procurement.rejection_reason }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">

                    <!-- Action panel -->
                    <div v-if="canAct && !['completed','rejected'].includes(procurement.status)"
                         class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ actionLabel }}</h3>
                        <div class="space-y-3">
                            <textarea v-model="approveForm.notes" rows="2"
                                      placeholder="Notes (required if rejecting)"
                                      class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                            <button @click="submitApproval('approve')" :disabled="approveForm.processing"
                                    class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium disabled:opacity-50 transition-all">
                                {{ procurement.can_mark_purchased ? 'Confirm Purchased' : procurement.can_confirm_receipt ? 'Confirm Receipt' : 'Approve' }}
                            </button>
                            <button v-if="!procurement.can_mark_purchased && !procurement.can_confirm_receipt"
                                    @click="submitApproval('reject')" :disabled="approveForm.processing"
                                    class="w-full px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-medium disabled:opacity-50 transition-all">
                                Reject
                            </button>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div v-if="procurement.status === 'completed'"
                         class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mb-1">✓ Completed</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500">
                            Receipt confirmed by {{ procurement.receipt_confirmed_by?.name }}
                        </p>
                    </div>

                    <!-- Total summary -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Amount</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatAmount(procurement.total_amount) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ procurement.items?.length }} item{{ procurement.items?.length !== 1 ? 's' : '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
