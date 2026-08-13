<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import Modal from '@/Components/Modal.vue'
import BankSelect from '@/Components/BankSelect.vue'
import DocumentManager from '@/Components/DocumentManager.vue'
import { ArrowLeft, ArrowRight, Phone, Mail, CheckCircle2, Clock, XCircle, Pencil, Plus, Trash2, X, AlertTriangle } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    procurement: Object,
    vendors:     { type: Array, default: () => [] },
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

// Documents split by kind: general attachments vs the purchase receipt.
const attachments = computed(() => (props.procurement.documents ?? []).filter(d => d.category !== 'receipt'))
const receipts    = computed(() => (props.procurement.documents ?? []).filter(d => d.category === 'receipt'))
const isReceiptStage = computed(() => ['purchased', 'completed'].includes(props.procurement.status))

// Track receipt presence live, so uploading one (an async call, no page reload)
// immediately unblocks the Confirm Receipt button.
const receiptCount = ref(receipts.value.length)
const hasReceipt   = computed(() => receiptCount.value > 0)

// Approval is blocked at the officer stage until every item is priced, and at
// the receipt stage until a purchase receipt is attached.
const approveBlocked = computed(() =>
    (props.procurement.can_officer_approve && props.procurement.has_unpriced_items) ||
    (props.procurement.can_confirm_receipt && !hasReceipt.value)
)
const approveBlockReason = computed(() => {
    if (props.procurement.can_officer_approve && props.procurement.has_unpriced_items)
        return 'Set a price for every item before approving'
    if (props.procurement.can_confirm_receipt && !hasReceipt.value)
        return 'Upload the purchase receipt before confirming'
    return ''
})

// ── Officer modify (only while the request is still pending) ──
const showModify = ref(false)
const modifyForm = useForm({
    title: '', justification: '', notes: '',
    vendor_id: '', supplier_name: '', supplier_phone: '', supplier_email: '',
    supplier_bank_name: '', supplier_account_number: '', supplier_account_name: '',
    items: [],
    modification_note: '',
})

function openModify() {
    const p = props.procurement
    modifyForm.clearErrors()
    modifyForm.title = p.title ?? ''
    modifyForm.justification = p.justification ?? ''
    modifyForm.notes = p.notes ?? ''
    modifyForm.vendor_id = p.vendor_id ?? ''
    modifyForm.supplier_name = p.supplier_name ?? ''
    modifyForm.supplier_phone = p.supplier_phone ?? ''
    modifyForm.supplier_email = p.supplier_email ?? ''
    modifyForm.supplier_bank_name = p.supplier_bank_name ?? ''
    modifyForm.supplier_account_number = p.supplier_account_number ?? ''
    modifyForm.supplier_account_name = p.supplier_account_name ?? ''
    modifyForm.items = (p.items ?? []).map(i => ({
        name: i.name, description: i.description ?? '',
        quantity: i.quantity, unit_price: i.unit_price ?? '', track_stock: !!i.track_stock,
    }))
    modifyForm.modification_note = ''
    showModify.value = true
}
function addModifyItem() {
    modifyForm.items.push({ name: '', description: '', quantity: 1, unit_price: '', track_stock: true })
}
function removeModifyItem(i) {
    if (modifyForm.items.length > 1) modifyForm.items.splice(i, 1)
}
const modifyTotal = computed(() =>
    modifyForm.items.reduce((s, it) => s + (Number(it.quantity) * Number(it.unit_price) || 0), 0)
)
function onVendorPick(id) {
    const v = props.vendors.find(x => x.id == id)
    if (!v) return
    modifyForm.supplier_name = v.name ?? ''
    modifyForm.supplier_phone = v.phone ?? ''
    modifyForm.supplier_email = v.email ?? ''
    modifyForm.supplier_bank_name = v.bank_name ?? ''
    modifyForm.supplier_account_number = v.bank_account_number ?? ''
    modifyForm.supplier_account_name = v.bank_account_name ?? ''
}
function submitModify() {
    modifyForm.put(route('manage.procurement.update', props.procurement.id), {
        preserveScroll: true,
        onSuccess: () => { showModify.value = false },
    })
}

const statusConfig = {
    pending:             { label: 'Awaiting Procurement Officer', class: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' },
    officer_approved:    { label: 'Awaiting Accountant', class: 'bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400' },
    accountant_approved: { label: 'Awaiting CEO',        class: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' },
    ceo_approved:        { label: 'Awaiting Purchase',   class: 'bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400' },
    purchased:           { label: 'Awaiting Receipt',    class: 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' },
    completed:           { label: 'Completed',           class: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' },
    rejected:            { label: 'Rejected',            class: 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' },
}

const actionLabel = computed(() => {
    const roles = user.value?.roles ?? []
    const permissions = user.value?.permissions ?? []
    if (props.procurement.can_officer_approve && (permissions.includes('approve-procurement-officer') || roles.includes('super-admin')))
        return 'Procurement Officer Review'
    if (props.procurement.can_accountant_approve && (roles.includes('accountant') || roles.includes('super-admin')))
        return 'Accountant Approval'
    if (props.procurement.can_ceo_approve && (roles.includes('ceo') || roles.includes('super-admin')))
        return 'CEO Approval'
    if (props.procurement.can_mark_purchased && (permissions.includes('purchase-procurement') || roles.includes('super-admin')))
        return 'Confirm Payment/Purchase'
    if (props.procurement.can_confirm_receipt && (permissions.includes('confirm-procurement-receipt') || roles.includes('super-admin')))
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
    }) : '-'
}

const timelineSteps = computed(() => {
    const p = props.procurement
    const s = p.status
    const steps = [
        { label: 'Submitted',           hint: null,                            by: p.submitted_by?.name,           at: p.created_at,            done: true,                          active: false },
        { label: 'Procurement Officer', hint: 'Awaiting procurement officer',  by: p.officer_approved_by?.name,    at: p.officer_approved_at,    done: !!p.officer_approved_at,       active: s === 'pending' },
        { label: 'Accountant Approval', hint: 'Awaiting accountant',           by: p.accountant_approved_by?.name, at: p.accountant_approved_at, done: !!p.accountant_approved_at,     active: s === 'officer_approved' },
        { label: 'CEO Approval',        hint: 'Awaiting CEO',                  by: p.ceo_approved_by?.name,        at: p.ceo_approved_at,        done: !!p.ceo_approved_at,           active: s === 'accountant_approved' },
        { label: 'Items Purchased',     hint: 'Awaiting purchase',             by: p.purchased_by?.name,           at: p.purchased_at,           done: !!p.purchased_at,              active: s === 'ceo_approved' },
        { label: 'Receipt Confirmed',   hint: 'Awaiting receipt confirmation', by: p.receipt_confirmed_by?.name,   at: p.receipt_confirmed_at,   done: !!p.receipt_confirmed_at,      active: s === 'purchased' },
    ]

    // A rejection lands on the stage that was awaiting action - surface it there
    // with the reason, rather than as a trailing note.
    if (s === 'rejected') {
        const idx = steps.findIndex(st => !st.done)
        if (idx !== -1) {
            steps[idx] = {
                ...steps[idx],
                active: false,
                declined: true,
                by: p.rejected_by_role ? `Declined by ${p.rejected_by_role.replace(/-/g, ' ')}` : 'Declined',
                at: p.updated_at,
                reason: p.rejection_reason,
            }
        }
    }
    return steps
})
</script>

<template>
    <ManageLayout>
        <Head :title="procurement.title" />

        <div class="p-4 lg:p-6">

            <!-- Header (sticky) -->
            <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-6 flex items-center gap-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
                <Link :href="route('manage.procurement.index')"
                      class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ procurement.title }}</h1>
                        <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium', statusConfig[procurement.status]?.class]">
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70" />
                            {{ statusConfig[procurement.status]?.label }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        <span class="font-mono">{{ procurement.reference }}</span>
                        · {{ procurement.building?.name }}
                        · {{ procurement.submitted_by?.name }}
                    </p>
                </div>
            </div>

            <!-- ── Body grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                <!-- ── Main column ── -->
                <div class="lg:col-span-2 flex flex-col gap-4 order-2 lg:order-none">

                    <!-- Continuation link -->
                    <div v-if="procurement.related_request || (procurement.continuations && procurement.continuations.length)"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-4 space-y-2">
                        <div v-if="procurement.related_request" class="flex items-center gap-2 text-sm">
                            <ArrowLeft class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                            <span class="text-gray-500 dark:text-gray-400">Continuation of</span>
                            <Link :href="route('manage.procurement.show', procurement.related_request.id)"
                                  class="font-medium text-gray-900 dark:text-white hover:underline truncate">
                                {{ procurement.related_request.reference }} · {{ procurement.related_request.title }}
                            </Link>
                        </div>
                        <div v-for="c in (procurement.continuations || [])" :key="c.id" class="flex items-center gap-2 text-sm">
                            <ArrowRight class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                            <span class="text-gray-500 dark:text-gray-400">Continued by</span>
                            <Link :href="route('manage.procurement.show', c.id)"
                                  class="font-medium text-gray-900 dark:text-white hover:underline truncate">
                                {{ c.reference }} · {{ c.title }}
                            </Link>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Line Items</h2>
                        </div>

                        <!-- Desktop table -->
                        <table class="hidden md:table w-full">
                            <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="text-left px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">Item</th>
                                <th class="text-right px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">Qty</th>
                                <th class="text-right px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">Unit Price</th>
                                <th class="text-right px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-gray-400">Total</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in procurement.items" :key="item.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3.5 align-top">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        {{ item.name }}
                                        <span v-if="!item.track_stock"
                                              class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                            Not stocked
                                        </span>
                                    </p>
                                    <p v-if="item.description" class="text-[11px] text-gray-400 mt-0.5 leading-snug">{{ item.description }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-right text-sm text-gray-500 dark:text-gray-400 tabular-nums align-top">{{ item.quantity }}</td>
                                <td class="px-5 py-3.5 text-right text-sm text-gray-500 dark:text-gray-400 tabular-nums align-top whitespace-nowrap">
                                    <span v-if="item.unit_price === null" class="text-[11px] font-medium px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">Pending</span>
                                    <template v-else>{{ formatAmount(item.unit_price) }}</template>
                                </td>
                                <td class="px-5 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white tabular-nums align-top whitespace-nowrap">{{ item.total_price === null ? '—' : formatAmount(item.total_price) }}</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/40">
                                <td colspan="3" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">Grand Total</td>
                                <td class="px-5 py-3.5 text-right text-lg font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">
                                    {{ formatAmount(procurement.total_amount) }}
                                </td>
                            </tr>
                            </tfoot>
                        </table>

                        <!-- Mobile cards -->
                        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="item in procurement.items" :key="item.id" class="px-4 py-3.5">
                                <div class="flex items-start justify-between gap-3 mb-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ item.total_price === null ? '—' : formatAmount(item.total_price) }}</p>
                                </div>
                                <p v-if="item.description" class="text-xs text-gray-400 mb-1.5">{{ item.description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ item.quantity }} × <span v-if="item.unit_price === null" class="text-amber-600 dark:text-amber-400">pricing pending</span><template v-else>{{ formatAmount(item.unit_price) }}</template>
                                </p>
                            </div>
                            <!-- Grand total -->
                            <div class="px-4 py-3.5 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Grand Total</p>
                                <p class="text-base font-semibold text-gray-900 dark:text-white tabular-nums">{{ formatAmount(procurement.total_amount) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Supporting documents (optional) -->
                    <div v-if="procurement.can_upload_documents || attachments.length"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Supporting Documents <span class="font-normal normal-case tracking-normal text-gray-400">(optional)</span></h2>
                        <DocumentManager
                            model-type="procurement"
                            :model-id="procurement.id"
                            :initial="attachments"
                            category="attachment"
                            :readonly="!procurement.can_upload_documents || ['completed','rejected'].includes(procurement.status)" />
                    </div>

                    <!-- Purchase receipt (required to complete) -->
                    <div v-if="isReceiptStage"
                         :class="procurement.status === 'purchased' && !hasReceipt ? 'ring-1 ring-amber-300 dark:ring-amber-700/50' : ''"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Purchase Receipt</h2>
                            <span v-if="procurement.status === 'purchased' && !hasReceipt"
                                  class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">Required</span>
                        </div>
                        <p v-if="procurement.status === 'purchased' && !hasReceipt" class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Attach the purchase receipt to confirm and complete this request.
                        </p>
                        <DocumentManager
                            model-type="procurement"
                            :model-id="procurement.id"
                            :initial="receipts"
                            category="receipt"
                            @updated="(docs) => receiptCount = docs.length"
                            :readonly="!procurement.can_upload_documents || procurement.status === 'completed'" />
                    </div>

                    <!-- Justification / Notes -->
                    <div v-if="procurement.justification || procurement.notes"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5 space-y-4">
                        <div v-if="procurement.justification">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Justification</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ procurement.justification }}</p>
                        </div>
                        <div v-if="procurement.notes" :class="procurement.justification ? 'pt-4 border-t border-gray-100 dark:border-gray-800' : ''">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Notes</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ procurement.notes }}</p>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div v-if="procurement.vendor || procurement.supplier_name"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-4">Supplier</p>

                        <!-- Name + contact row -->
                        <div class="flex items-center gap-3.5 mb-4">
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

                        <!-- Bank Details - below supplier row, full width -->
                        <template v-if="procurement.supplier_bank_name || procurement.supplier_account_number">
                            <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Bank Details</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div v-if="procurement.supplier_bank_name">
                                        <p class="text-xs text-gray-400 mb-1">Bank</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ procurement.supplier_bank_name }}</p>
                                    </div>
                                    <div v-if="procurement.supplier_account_number">
                                        <p class="text-xs text-gray-400 mb-1">Account No.</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ procurement.supplier_account_number }}</p>
                                    </div>
                                    <div v-if="procurement.supplier_account_name">
                                        <p class="text-xs text-gray-400 mb-1">Account Name</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ procurement.supplier_account_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-5">Approval Timeline</p>
                        <div>
                            <div v-for="(step, i) in timelineSteps" :key="step.label" class="flex gap-3">
                                <!-- Track: node + connector -->
                                <div class="flex flex-col items-center">
                                    <div :class="[
                                        'w-7 h-7 rounded-full flex items-center justify-center border-2 shrink-0 transition-all',
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
                                    <div v-if="i < timelineSteps.length - 1"
                                         :class="step.done ? 'bg-gray-900 dark:bg-white' : 'bg-gray-200 dark:bg-gray-800'"
                                         class="w-0.5 flex-1 min-h-[20px] my-1 transition-all" />
                                </div>

                                <!-- Content -->
                                <div class="pb-4 flex-1 min-w-0">
                                    <p :class="[
                                        'text-sm font-medium',
                                        step.declined ? 'text-red-600 dark:text-red-400' :
                                        step.done || step.active ? 'text-gray-900 dark:text-white' :
                                        'text-gray-400 dark:text-gray-600'
                                    ]">
                                        {{ step.label }}
                                        <span v-if="step.declined" class="ml-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 align-middle">Rejected</span>
                                    </p>
                                    <p v-if="step.by" class="text-xs text-gray-500 mt-0.5">{{ step.by }}</p>
                                    <p v-if="step.at" class="text-xs text-gray-400 mt-0.5">{{ formatDateTime(step.at) }}</p>
                                    <p v-if="step.active && !step.done && !step.declined" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ step.hint || 'Awaiting action' }}
                                    </p>
                                    <p v-if="step.declined && step.reason" class="text-xs text-gray-500 dark:text-gray-400 mt-1 rounded-lg bg-red-50 dark:bg-red-900/15 px-2.5 py-1.5">{{ step.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Sidebar ── -->
                <div class="flex flex-col gap-4 order-1 lg:order-none lg:sticky lg:top-20 self-start">

                    <!-- Officer modify (pending only) -->
                    <div v-if="procurement.can_officer_modify" class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 p-4">
                        <p v-if="procurement.has_unpriced_items" class="flex items-start gap-2 text-[11px] text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 rounded-lg px-2.5 py-2 mb-3">
                            <AlertTriangle class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                            Some items have no price yet. Set prices before approving.
                        </p>
                        <button @click="openModify"
                                class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                            <Pencil class="w-3.5 h-3.5" /> Modify request
                        </button>
                    </div>

                    <!-- Action panel - inverted, matches the sidebar logo/avatar accent -->
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
                                    :disabled="approveForm.processing || approveBlocked"
                                    :title="approveBlockReason"
                                    class="w-full py-2.5 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
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
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1.5">Total Amount</p>
                            <p class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white tabular-nums">
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
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ procurement.building?.name ?? '-' }}</span>
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

        <!-- ── Officer modify modal ── -->
        <Modal :show="showModify" max-width="2xl" @close="showModify = false">
            <div class="p-6 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2"><Pencil class="w-4 h-4 text-gray-400" /> Modify Request</h2>
                    <button @click="showModify = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><X class="w-4 h-4" /></button>
                </div>

                <form @submit.prevent="submitModify" class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Title</label>
                        <input v-model="modifyForm.title" type="text" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="modifyForm.errors.title" class="mt-1 text-xs text-red-600">{{ modifyForm.errors.title }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Justification</label>
                        <textarea v-model="modifyForm.justification" rows="2" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                    </div>

                    <!-- Supplier -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</p>
                        <select v-if="vendors.length" v-model="modifyForm.vendor_id" @change="onVendorPick(modifyForm.vendor_id)"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Pick from directory (optional)</option>
                            <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <input v-model="modifyForm.supplier_name" type="text" placeholder="Supplier name" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <input v-model="modifyForm.supplier_phone" type="text" placeholder="Phone" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <input v-model="modifyForm.supplier_email" type="email" placeholder="Email" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <BankSelect v-model="modifyForm.supplier_bank_name" />
                            <input v-model="modifyForm.supplier_account_number" type="text" maxlength="10" placeholder="Account number" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            <input v-model="modifyForm.supplier_account_name" type="text" placeholder="Account name" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</p>
                            <button type="button" @click="addModifyItem" class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"><Plus class="w-3.5 h-3.5" /> Add item</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(it, i) in modifyForm.items" :key="i" class="rounded-xl border border-gray-200/80 dark:border-gray-800 p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Item {{ i + 1 }}</span>
                                    <button v-if="modifyForm.items.length > 1" type="button" @click="removeModifyItem(i)" class="p-1 text-gray-300 hover:text-red-500"><Trash2 class="w-3.5 h-3.5" /></button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <input v-model="it.name" type="text" placeholder="Item name" class="col-span-2 w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <input v-model="it.quantity" type="number" min="1" placeholder="Qty" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <input v-model="it.unit_price" type="number" min="0" step="0.01" placeholder="Unit price" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <input v-model="it.description" type="text" placeholder="Description (optional)" class="col-span-2 sm:col-span-4 w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                </div>
                                <label class="inline-flex items-center gap-2 mt-2 text-[11px] text-gray-500 dark:text-gray-400 cursor-pointer">
                                    <input v-model="it.track_stock" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-gray-900 dark:focus:ring-white bg-white dark:bg-gray-950" />
                                    Add to stock on receipt
                                </label>
                            </div>
                        </div>
                        <p v-if="modifyForm.errors.items" class="mt-1 text-xs text-red-600">{{ modifyForm.errors.items }}</p>
                        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white tabular-nums">{{ formatAmount(modifyTotal) }}</span>
                        </div>
                    </div>

                    <!-- Mandatory note -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Reason for modification <span class="text-red-500">*</span></label>
                        <textarea v-model="modifyForm.modification_note" rows="2" placeholder="Explain what you changed and why" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                        <p v-if="modifyForm.errors.modification_note" class="mt-1 text-xs text-red-600">{{ modifyForm.errors.modification_note }}</p>
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit" :disabled="modifyForm.processing"
                                class="flex-1 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                            {{ modifyForm.processing ? 'Saving…' : 'Save changes' }}
                        </button>
                        <button type="button" @click="showModify = false" class="px-6 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </Modal>
    </ManageLayout>
</template>
