<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import {
    TrendingUp, TrendingDown, Download, FileText,
    AlertCircle, Plus, X
} from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    pendingMaintenance: Array,
    pendingProcurement: Array,
    transactions:       Object,
    summary:            Object,
    trend:              Array,
    buildings:          Array,
    categoryLabels:     Object,
    filters:            Object,
    dateRange:          Object,
})

const user = computed(() => usePage().props.auth.user)

// ── Filters ───────────────────────────────────────────────────
const period     = ref(props.filters.period)
const buildingId = ref(props.filters.building_id || '')
const year       = ref(props.filters.year)
const month      = ref(props.filters.month)
const quarter    = ref(props.filters.quarter)
const date       = ref(props.filters.date)
const typeFilter = ref(props.filters.type || '')
const catFilter  = ref(props.filters.category || '')

watch([period, buildingId, year, month, quarter, date, typeFilter, catFilter], () => {
    router.get(route('manage.financials.index'), {
        period:      period.value,
        building_id: buildingId.value || undefined,
        year:        year.value,
        month:       month.value,
        quarter:     quarter.value,
        date:        date.value,
        type:        typeFilter.value || undefined,
        category:    catFilter.value || undefined,
    }, { preserveState: true, replace: true })
})

// ── Payment modal ─────────────────────────────────────────────
const payModal    = ref(null) // { type, record }
const payForm     = useForm({
    payment_method:    'bank_transfer',
    payment_reference: '',
    bank_name:         '',
    amount:            '',
    notes:             '',
    transaction_date:  new Date().toISOString().split('T')[0],
})

function openPayModal(type, record) {
    payModal.value = { type, record }
    payForm.amount = record.actual_cost ?? record.total_amount ?? ''
}

function closePayModal() {
    payModal.value = null
    payForm.reset()
}

function submitPayment() {
    payForm.post(
        route('manage.financials.pay', {
            type: payModal.value.type,
            id:   payModal.value.record.id,
        }),
        {
            preserveScroll: true,
            onSuccess: closePayModal,
        }
    )
}

// ── Manual transaction modal ──────────────────────────────────
const showManual  = ref(false)
const manualForm  = useForm({
    building_id:       props.buildings.length === 1 ? props.buildings[0].id : '',
    type:              'income',
    description:       '',
    amount:            '',
    payment_method:    'cash',
    payment_reference: '',
    bank_name:         '',
    transaction_date:  new Date().toISOString().split('T')[0],
    notes:             '',
})

function submitManual() {
    manualForm.post(route('manage.financials.manual'), {
        preserveScroll: true,
        onSuccess: () => { showManual.value = false; manualForm.reset() },
    })
}

// ── Export ────────────────────────────────────────────────────
function exportReport(format) {
    const params = new URLSearchParams({
        period:   period.value,
        year:     year.value,
        month:    month.value,
        quarter:  quarter.value,
        date:     date.value,
        format,
        ...(buildingId.value ? { building_id: buildingId.value } : {}),
    })
    window.open(route('manage.financials.export') + '?' + params.toString(), '_blank')
}

// ── Helpers ───────────────────────────────────────────────────
const months = ['January','February','March','April','May','June',
    'July','August','September','October','November','December']

const years = computed(() => {
    const y = []
    for (let i = 2024; i <= new Date().getFullYear() + 1; i++) y.push(i)
    return y
})

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric'
    })
}

const maxTrend = computed(() =>
    Math.max(...props.trend.map(t => Math.max(t.income, t.expenses, 1)))
)

const inputClass = "w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
</script>

<template>
    <ManageLayout>
        <Head title="Financials" />

        <div class="p-6 lg:p-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Financials</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ formatDate(dateRange.start) }} — {{ formatDate(dateRange.end) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showManual = true"
                            class="flex items-center gap-2 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 transition-all">
                        <Plus class="w-4 h-4" />
                        Log Transaction
                    </button>
                    <button @click="exportReport('csv')"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                        <Download class="w-4 h-4" /> CSV
                    </button>
                    <button @click="exportReport('pdf')"
                            class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                        <FileText class="w-4 h-4" /> PDF
                    </button>
                </div>
            </div>

            <!-- ── Pending Payment Queue ── -->
            <div v-if="(pendingMaintenance.length + pendingProcurement.length) > 0"
                 class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <AlertCircle class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    <h2 class="text-sm font-semibold text-amber-700 dark:text-amber-400">
                        Pending Payments ({{ pendingMaintenance.length + pendingProcurement.length }})
                    </h2>
                </div>
                <div class="space-y-2">
                    <div v-for="item in [...pendingMaintenance, ...pendingProcurement]"
                         :key="item.id"
                         class="flex items-center justify-between gap-4 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-800 rounded-xl p-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">
                                    {{ item.reference ? 'Procurement' : 'Maintenance' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ item.building?.name }}</span>
                            </div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatAmount(item.actual_cost ?? item.total_amount) }}
                                · {{ item.vendor?.name ?? (item.supplier_name ?? 'No vendor') }}
                            </p>
                        </div>
                        <button
                            @click="openPayModal(item.reference ? 'procurement' : 'maintenance', item)"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition-all shrink-0">
                            Record Payment
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Period filters ── -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 mb-6 flex flex-wrap gap-3 items-center">
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                    <button v-for="p in ['daily','monthly','quarterly','yearly']" :key="p"
                            @click="period = p"
                            :class="period === p
                            ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400'"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium capitalize transition-all">
                        {{ p }}
                    </button>
                </div>

                <input v-if="period === 'daily'" v-model="date" type="date" :class="inputClass" style="width:auto" />

                <template v-if="period === 'monthly'">
                    <select v-model="month" :class="inputClass" style="width:auto">
                        <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                    </select>
                    <select v-model="year" :class="inputClass" style="width:auto">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <template v-if="period === 'quarterly'">
                    <select v-model="quarter" :class="inputClass" style="width:auto">
                        <option value="1">Q1 (Jan–Mar)</option>
                        <option value="2">Q2 (Apr–Jun)</option>
                        <option value="3">Q3 (Jul–Sep)</option>
                        <option value="4">Q4 (Oct–Dec)</option>
                    </select>
                    <select v-model="year" :class="inputClass" style="width:auto">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </template>

                <select v-if="period === 'yearly'" v-model="year" :class="inputClass" style="width:auto">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>

                <select v-model="buildingId" :class="inputClass" style="width:auto">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
            </div>

            <!-- ── Summary cards ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1">Total Income</p>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ formatAmount(summary.total_income) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-5">
                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Total Expenses</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ formatAmount(summary.total_expenses) }}</p>
                </div>
                <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-5 bg-white dark:bg-gray-900">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net Profit</p>
                    <p class="text-2xl font-bold"
                       :class="summary.net_profit >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400'">
                        {{ summary.net_profit < 0 ? '-' : '' }}{{ formatAmount(Math.abs(summary.net_profit)) }}
                    </p>
                    <div class="flex items-center gap-1 mt-1">
                        <TrendingUp v-if="summary.net_profit >= 0" class="w-3.5 h-3.5 text-emerald-500" />
                        <TrendingDown v-else class="w-3.5 h-3.5 text-red-500" />
                        <span class="text-xs"
                              :class="summary.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                            {{ summary.profit_margin }}% margin
                        </span>
                    </div>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 cursor-pointer"
                     @click="router.get(route('manage.financials.index'))">
                    <p class="text-xs text-amber-600 dark:text-amber-400 mb-1">Pending Payments</p>
                    <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ summary.pending_count }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-500 mt-1">awaiting action</p>
                </div>
            </div>

            <!-- ── Trend chart + Transaction ledger ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

                <!-- Trend -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">12-Month Trend</h2>
                    <div class="flex items-end gap-0.5 h-32">
                        <div v-for="t in trend" :key="t.month" class="flex-1 flex flex-col justify-end gap-0.5">
                            <div class="w-full bg-emerald-400 dark:bg-emerald-500 rounded-sm"
                                 :style="{ height: maxTrend > 0 ? Math.max((t.income / maxTrend * 100), 1) + '%' : '2px' }"
                                 :title="`${t.month}\nIncome: ${formatAmount(t.income)}`" />
                            <div class="w-full bg-red-400 dark:bg-red-500 rounded-sm"
                                 :style="{ height: maxTrend > 0 ? Math.max((t.expenses / maxTrend * 100), 1) + '%' : '2px' }"
                                 :title="`${t.month}\nExpenses: ${formatAmount(t.expenses)}`" />
                        </div>
                    </div>
                    <div class="flex gap-3 mt-3">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-emerald-400" />
                            <span class="text-xs text-gray-400">Income</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-sm bg-red-400" />
                            <span class="text-xs text-gray-400">Expenses</span>
                        </div>
                    </div>
                </div>

                <!-- Transaction ledger -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Transaction Ledger
                        </h2>
                        <div class="flex gap-2">
                            <select v-model="typeFilter"
                                    class="px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none">
                                <option value="">All Types</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                            <select v-model="catFilter"
                                    class="px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none">
                                <option value="">All Categories</option>
                                <option v-for="(label, key) in categoryLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-96 overflow-y-auto">
                        <div v-for="t in transactions.data" :key="t.id"
                             class="flex items-center justify-between px-5 py-3 gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.description }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ formatDate(t.transaction_date) }}</span>
                                    <span>·</span>
                                    <span>{{ categoryLabels[t.category] ?? t.category }}</span>
                                    <span>·</span>
                                    <span>{{ t.building?.name }}</span>
                                    <span v-if="t.payment_method">· {{ t.payment_method.replace('_', ' ') }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p :class="t.type === 'income'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'"
                                   class="text-sm font-semibold">
                                    {{ t.type === 'expense' ? '-' : '+' }}{{ formatAmount(t.amount) }}
                                </p>
                                <p class="text-xs text-gray-400">{{ t.recorded_by?.name }}</p>
                            </div>
                        </div>
                        <div v-if="transactions.data.length === 0"
                             class="px-5 py-10 text-center text-sm text-gray-400">
                            No transactions found for this period.
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.last_page > 1"
                         class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-2">
                        <Link v-for="link in transactions.links" :key="link.label"
                              :href="link.url || '#'"
                              :class="[
                                'px-3 py-1 rounded-lg text-xs transition-all',
                                link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                              v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Pay Expense Modal ── -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="payModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 w-full max-w-md shadow-2xl">
                    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Record Payment</h3>
                        <button @click="closePayModal" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-all">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-5">
                        <!-- Item summary -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 mb-5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ payModal.record.title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ payModal.record.building?.name }}
                                · {{ formatAmount(payModal.record.actual_cost ?? payModal.record.total_amount) }}
                            </p>
                        </div>

                        <form @submit.prevent="submitPayment" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Payment Method *</label>
                                    <select v-model="payForm.payment_method" :class="inputClass">
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="pos">POS</option>
                                        <option value="paystack">Paystack</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Amount (₦) *</label>
                                    <input v-model="payForm.amount" type="number" min="0" step="0.01" :class="inputClass" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Bank Name</label>
                                <input v-model="payForm.bank_name" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Payment Reference</label>
                                <input v-model="payForm.payment_reference" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Transaction Date *</label>
                                <input v-model="payForm.transaction_date" type="date" :class="inputClass" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Notes</label>
                                <textarea v-model="payForm.notes" rows="2" :class="inputClass + ' resize-none'" />
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" :disabled="payForm.processing"
                                        class="flex-1 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                    {{ payForm.processing ? 'Recording...' : 'Record Payment' }}
                                </button>
                                <button type="button" @click="closePayModal"
                                        class="px-5 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-700 dark:text-gray-300 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Manual Transaction Modal ── -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="showManual" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 w-full max-w-md shadow-2xl">
                    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Log Manual Transaction</h3>
                        <button @click="showManual = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-all">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-5">
                        <form @submit.prevent="submitManual" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Building *</label>
                                    <select v-model="manualForm.building_id" :class="inputClass">
                                        <option value="">Select</option>
                                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                    <p v-if="manualForm.errors.building_id" class="mt-1 text-xs text-red-600">{{ manualForm.errors.building_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Type *</label>
                                    <select v-model="manualForm.type" :class="inputClass">
                                        <option value="income">Income</option>
                                        <option value="expense">Expense</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Description *</label>
                                <input v-model="manualForm.description" type="text" :class="inputClass" />
                                <p v-if="manualForm.errors.description" class="mt-1 text-xs text-red-600">{{ manualForm.errors.description }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Amount (₦) *</label>
                                    <input v-model="manualForm.amount" type="number" min="0" step="0.01" :class="inputClass" />
                                    <p v-if="manualForm.errors.amount" class="mt-1 text-xs text-red-600">{{ manualForm.errors.amount }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Date *</label>
                                    <input v-model="manualForm.transaction_date" type="date" :class="inputClass" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Payment Method</label>
                                    <select v-model="manualForm.payment_method" :class="inputClass">
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="pos">POS</option>
                                        <option value="paystack">Paystack</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Reference</label>
                                    <input v-model="manualForm.payment_reference" type="text" :class="inputClass" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Bank Name</label>
                                <input v-model="manualForm.bank_name" type="text" :class="inputClass" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Notes</label>
                                <textarea v-model="manualForm.notes" rows="2" :class="inputClass + ' resize-none'" />
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" :disabled="manualForm.processing"
                                        class="flex-1 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                    {{ manualForm.processing ? 'Saving...' : 'Record Transaction' }}
                                </button>
                                <button type="button" @click="showManual = false"
                                        class="px-5 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-700 dark:text-gray-300 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>

    </ManageLayout>
</template>
