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
    pending:             { label: 'Awaiting Accountant', class: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
    accountant_approved: { label: 'Awaiting CEO',        class: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' },
    ceo_approved:        { label: 'Awaiting Purchase',   class: 'bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400' },
    purchased:           { label: 'Awaiting Receipt',    class: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' },
    completed:           { label: 'Completed',           class: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' },
    rejected:            { label: 'Rejected',            class: 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' },
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

const timelineSteps = computed(() => [
    { label: 'Submitted',           by: props.procurement.submitted_by?.name,           at: props.procurement.created_at,            done: true },
    { label: 'Accountant Approved', by: props.procurement.accountant_approved_by?.name, at: props.procurement.accountant_approved_at, done: !!props.procurement.accountant_approved_at },
    { label: 'CEO Approved',        by: props.procurement.ceo_approved_by?.name,        at: props.procurement.ceo_approved_at,        done: !!props.procurement.ceo_approved_at },
    { label: 'Items Purchased',     by: props.procurement.purchased_by?.name,           at: props.procurement.purchased_at,           done: !!props.procurement.purchased_at },
    { label: 'Receipt Confirmed',   by: props.procurement.receipt_confirmed_by?.name,   at: props.procurement.receipt_confirmed_at,   done: !!props.procurement.receipt_confirmed_at },
])
</script>

<template>
    <ManageLayout>
        <Head :title="procurement.title" />

        <div class="max-w-5xl px-6 py-8 lg:px-10">

            <!-- Back -->
            <Link :href="route('manage.procurement.index')"
                  class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-widest text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors mb-8">
                <ArrowLeft class="w-3.5 h-3.5" />
                Procurement
            </Link>

            <!-- ── Page header ── -->
            <div class="mb-8 pb-7 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-start justify-between gap-6 flex-wrap">
                    <div class="min-w-0 flex-1">
                        <!-- Reference + status -->
                        <div class="flex items-center gap-2.5 mb-3 flex-wrap">
                            <span class="font-mono text-[11px] tracking-widest text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-md">
                                {{ procurement.reference }}
                            </span>
                            <span :class="['text-[11px] font-semibold px-2.5 py-1 rounded-full', statusConfig[procurement.status]?.class]">
                                {{ statusConfig[procurement.status]?.label }}
                            </span>
                        </div>
                        <!-- Title -->
                        <h1 class="text-[28px] font-bold tracking-tight leading-snug text-gray-900 dark:text-white mb-2.5">
                            {{ procurement.title }}
                        </h1>
                        <!-- Subtitle -->
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ procurement.building?.name }}</span>
                            <span class="text-gray-200 dark:text-gray-700">·</span>
                            <span>Submitted by <span class="font-medium text-gray-700 dark:text-gray-300">{{ procurement.submitted_by?.name }}</span></span>
                        </p>
                    </div>
                    <!-- Total — right-aligned in the header -->
                    <div class="text-right shrink-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1">Total Amount</p>
                        <p class="text-[32px] font-extrabold tracking-tight text-gray-900 dark:text-white tabular-nums leading-none">
                            {{ formatAmount(procurement.total_amount) }}
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1.5">
                            {{ procurement.items?.length }} line item{{ procurement.items?.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Body grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                <!-- ── Main column ── -->
                <div class="lg:col-span-2 flex flex-col gap-4">

                    <!-- Items table -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Line Items</h2>
                        </div>
                        <table class="w-full">
                            <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="text-left px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Item</th>
                                <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Qty</th>
                                <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Unit Price</th>
                                <th class="text-right px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-gray-400">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in procurement.items" :key="item.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3.5 align-top">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                    <p v-if="item.description" class="text-[11px] text-gray-400 mt-0.5 leading-snug">{{ item.description }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-right text-sm text-gray-500 dark:text-gray-400 tabular-nums align-top">{{ item.quantity }}</td>
                                <td class="px-5 py-3.5 text-right text-sm text-gray-500 dark:text-gray-400 tabular-nums align-top whitespace-nowrap">{{ formatAmount(item.unit_price) }}</td>
                                <td class="px-5 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white tabular-nums align-top whitespace-nowrap">{{ formatAmount(item.total_price) }}</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/40">
                                <td colspan="3" class="px-5 py-3.5 text-right text-[10px] font-bold uppercase tracking-[0.1em] text-gray-400">Grand Total</td>
                                <td class="px-5 py-3.5 text-right text-lg font-extrabold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">
                                    {{ formatAmount(procurement.total_amount) }}
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Justification / Notes -->
                    <div v-if="procurement.justification || procurement.notes"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 space-y-4">
                        <div v-if="procurement.justification">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-2">Justification</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ procurement.justification }}</p>
                        </div>
                        <div v-if="procurement.notes" :class="procurement.justification ? 'pt-4 border-t border-gray-100 dark:border-gray-800' : ''">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-2">Notes</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ procurement.notes }}</p>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div v-if="procurement.vendor || procurement.supplier_name"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-4">Supplier</p>
                        <div class="flex items-center gap-3.5">
                            <!-- Avatar — same style as sidebar user avatar -->
                            <div class="w-9 h-9 rounded-lg bg-gray-900 dark:bg-white flex items-center justify-center shrink-0 select-none">
                                <span class="text-white dark:text-gray-900 text-sm font-semibold">
                                    {{ (procurement.vendor?.name ?? procurement.supplier_name ?? '?').charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-none mb-1.5">
                                    {{ procurement.vendor?.name ?? procurement.supplier_name }}
                                </p>
                                <div class="flex flex-wrap gap-x-4 gap-y-1">
                                    <a v-if="procurement.vendor?.phone || procurement.supplier_phone"
                                       :href="'tel:' + (procurement.vendor?.phone ?? procurement.supplier_phone)"
                                       class="inline-flex items-center gap-1.5 text-[12px] text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        <Phone class="w-3 h-3" />
                                        {{ procurement.vendor?.phone ?? procurement.supplier_phone }}
                                    </a>
                                    <span v-if="procurement.supplier_email"
                                          class="inline-flex items-center gap-1.5 text-[12px] text-gray-400">
                                        <Mail class="w-3 h-3" />
                                        {{ procurement.supplier_email }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-5">Approval Timeline</p>
                        <div>
                            <div v-for="(step, i) in timelineSteps" :key="step.label" class="flex gap-3.5">
                                <!-- Track -->
                                <div class="flex flex-col items-center w-5 shrink-0">
                                    <div :class="[
                                        'w-2.5 h-2.5 rounded-full shrink-0 z-10 mt-0.5 transition-all',
                                        step.done
                                            ? 'bg-gray-900 dark:bg-white ring-4 ring-gray-900/10 dark:ring-white/10'
                                            : 'bg-gray-200 dark:bg-gray-700'
                                    ]" />
                                    <div v-if="i < timelineSteps.length - 1"
                                         class="w-px flex-1 my-1.5 min-h-3 bg-gray-100 dark:bg-gray-800" />
                                </div>
                                <!-- Content -->
                                <div :class="['flex-1', i < timelineSteps.length - 1 ? 'pb-5' : 'pb-0']">
                                    <p :class="[
                                        'text-sm leading-none',
                                        step.done
                                            ? 'font-semibold text-gray-900 dark:text-white'
                                            : 'font-medium text-gray-300 dark:text-gray-600'
                                    ]">{{ step.label }}</p>
                                    <p v-if="step.done && step.by" class="text-[11px] text-gray-400 mt-1.5 leading-none">
                                        {{ step.by }}
                                        <span class="mx-1 text-gray-200 dark:text-gray-700">·</span>
                                        {{ formatDateTime(step.at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rejected -->
                            <div v-if="procurement.status === 'rejected'" class="flex gap-3.5 mt-1">
                                <div class="w-5 shrink-0 flex justify-center">
                                    <div class="w-2.5 h-2.5 rounded-full bg-red-500 ring-4 ring-red-500/15 mt-0.5" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-red-500 leading-none">Rejected</p>
                                    <p v-if="procurement.rejection_reason" class="text-[11px] text-gray-400 mt-1.5">{{ procurement.rejection_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Sidebar ── -->
                <div class="flex flex-col gap-4">

                    <!-- Action panel — inverted, matches the sidebar logo/avatar accent -->
                    <div v-if="canAct && !['completed','rejected'].includes(procurement.status)"
                         class="rounded-2xl bg-gray-900 dark:bg-white border border-gray-900 dark:border-gray-100 p-5">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="relative flex h-2 w-2 shrink-0 mt-[5px]">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white dark:text-gray-900 leading-none mb-1">{{ actionLabel }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Your decision is required</p>
                            </div>
                        </div>

                        <textarea v-model="approveForm.notes" rows="3"
                                  placeholder="Add notes (required when rejecting)"
                                  class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 resize-none transition-all mb-3 font-sans" />

                        <div class="flex flex-col gap-2">
                            <button @click="submitApproval('approve')"
                                    :disabled="approveForm.processing"
                                    class="w-full py-2.5 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-[0.98] transition-all disabled:opacity-50 cursor-pointer">
                                {{ procurement.can_mark_purchased ? 'Confirm Purchased' : procurement.can_confirm_receipt ? 'Confirm Receipt' : 'Approve' }}
                            </button>
                            <button v-if="!procurement.can_mark_purchased && !procurement.can_confirm_receipt"
                                    @click="submitApproval('reject')"
                                    :disabled="approveForm.processing"
                                    class="w-full py-2.5 rounded-xl border border-white/10 dark:border-black/10 text-gray-400 dark:text-gray-500 hover:border-red-500/40 hover:text-red-400 dark:hover:text-red-500 text-sm font-medium transition-all disabled:opacity-50 cursor-pointer">
                                Reject
                            </button>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div v-if="procurement.status === 'completed'"
                         class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 p-5">
                        <div class="flex items-center gap-2.5 mb-1">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px] font-bold shrink-0">✓</div>
                            <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">Completed</p>
                        </div>
                        <p class="text-[12px] text-emerald-600 dark:text-emerald-500 pl-7">
                            Receipt confirmed by {{ procurement.receipt_confirmed_by?.name }}
                        </p>
                    </div>

                    <!-- Rejected -->
                    <div v-if="procurement.status === 'rejected'"
                         class="rounded-2xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 p-5">
                        <p class="text-sm font-bold text-red-700 dark:text-red-400 mb-1">Rejected</p>
                        <p v-if="procurement.rejection_reason" class="text-[12px] text-red-600 dark:text-red-500">{{ procurement.rejection_reason }}</p>
                    </div>

                    <!-- Summary card -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1.5">Total Amount</p>
                            <p class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white tabular-nums">
                                {{ formatAmount(procurement.total_amount) }}
                            </p>
                        </div>
                        <div class="px-5 py-3.5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Reference</span>
                                <span class="font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ procurement.reference }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Building</span>
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ procurement.building?.name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Items</span>
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ procurement.items?.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Status</span>
                                <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full', statusConfig[procurement.status]?.class]">
                                    {{ statusConfig[procurement.status]?.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </ManageLayout>
</template>
