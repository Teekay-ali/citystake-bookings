<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Pencil, AlertTriangle } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    item: Object,
    logs: Object,
})

const user = computed(() => usePage().props.auth.user)
const isManager = computed(() =>
    user.value?.roles?.includes('manager') || user.value?.roles?.includes('super-admin')
)

const logForm = useForm({
    type:     'usage',
    quantity: 1,
    reason:   '',
})

function submitLog() {
    logForm.post(route('manage.stock.log', props.item.id), {
        preserveScroll: true,
        onSuccess: () => logForm.reset(),
    })
}

const typeConfig = {
    usage:      { label: 'Usage',      class: 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' },
    restock:    { label: 'Restock',    class: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' },
    adjustment: { label: 'Adjustment', class: 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' },
}

const isLow = computed(() => props.item.quantity <= props.item.low_stock_threshold)

const stockPercent = computed(() => {
    // Cap at 100%, floor at 0
    const max = Math.max(props.item.low_stock_threshold * 3, props.item.quantity)
    return Math.min(100, Math.round((props.item.quantity / max) * 100))
})

function formatDateTime(d) {
    return new Date(d).toLocaleDateString('en-NG', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}
</script>

<template>
    <ManageLayout>
        <Head :title="item.name" />

        <div class="max-w-5xl px-6 py-8 lg:px-10">

            <!-- Back -->
            <Link :href="route('manage.stock.index')"
                  class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-widest text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors mb-8">
                <ArrowLeft class="w-3.5 h-3.5" />
                Stock
            </Link>

            <!-- ── Page header ── -->
            <div class="mb-8 pb-7 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-start justify-between gap-6 flex-wrap">
                    <div class="min-w-0 flex-1">
                        <!-- Category + building -->
                        <div class="flex items-center gap-2.5 mb-3 flex-wrap">
                            <span v-if="item.category"
                                  class="text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2.5 py-1 rounded-full">
                                {{ item.category }}
                            </span>
                            <span class="text-[11px] font-mono tracking-widest text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-md">
                                {{ item.building?.name }}
                            </span>
                            <!-- Low stock warning badge -->
                            <span v-if="isLow"
                                  class="inline-flex items-center gap-1 text-[11px] font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 px-2.5 py-1 rounded-full">
                                <AlertTriangle class="w-3 h-3" />
                                Low Stock
                            </span>
                        </div>

                        <h1 class="text-[28px] font-bold tracking-tight leading-snug text-gray-900 dark:text-white mb-2.5">
                            {{ item.name }}
                        </h1>

                        <p v-if="item.description" class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            {{ item.description }}
                        </p>
                    </div>

                    <!-- Current stock — right-aligned in header -->
                    <div class="text-right shrink-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1">Current Stock</p>
                        <p class="leading-none" :class="isLow ? 'text-amber-500' : 'text-gray-900 dark:text-white'">
                            <span class="text-[36px] font-extrabold tracking-tight tabular-nums">{{ item.quantity }}</span>
                            <span class="text-base font-medium text-gray-400 ml-1.5">{{ item.unit }}</span>
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1.5">
                            Alert at {{ item.low_stock_threshold }} {{ item.unit }}
                        </p>
                    </div>
                </div>

                <!-- Stock level bar -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Stock Level</span>
                        <span class="text-[11px] font-medium text-gray-400">{{ stockPercent }}%</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             :class="isLow ? 'bg-amber-400' : 'bg-gray-900 dark:bg-white'"
                             :style="{ width: stockPercent + '%' }" />
                    </div>
                </div>
            </div>

            <!-- ── Body grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                <!-- ── Main column ── -->
                <div class="lg:col-span-2 flex flex-col gap-4">

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 px-4 py-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1.5">On Hand</p>
                            <p class="text-xl font-extrabold tabular-nums"
                               :class="isLow ? 'text-amber-500' : 'text-gray-900 dark:text-white'">
                                {{ item.quantity }}
                                <span class="text-xs font-medium text-gray-400 ml-0.5">{{ item.unit }}</span>
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 px-4 py-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1.5">Alert At</p>
                            <p class="text-xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                                {{ item.low_stock_threshold }}
                                <span class="text-xs font-medium text-gray-400 ml-0.5">{{ item.unit }}</span>
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 px-4 py-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 mb-1.5">Log Entries</p>
                            <p class="text-xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                                {{ logs.total ?? logs.data.length }}
                            </p>
                        </div>
                    </div>

                    <!-- Usage history -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">History</h2>
                        </div>

                        <div v-if="logs.data.length === 0"
                             class="px-5 py-12 text-center">
                            <p class="text-sm text-gray-400">No history yet.</p>
                        </div>

                        <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="log in logs.data" :key="log.id"
                                 class="px-5 py-3.5 flex items-center justify-between gap-4 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full', typeConfig[log.type].class]">
                                            {{ typeConfig[log.type].label }}
                                        </span>
                                        <span v-if="log.reason" class="text-[12px] text-gray-500 dark:text-gray-400 truncate">
                                            {{ log.reason }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-400 leading-none">
                                        {{ log.logged_by?.name }}
                                        <span class="mx-1 text-gray-200 dark:text-gray-700">·</span>
                                        {{ formatDateTime(log.created_at) }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold tabular-nums"
                                       :class="log.quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                                        {{ log.quantity > 0 ? '+' : '' }}{{ log.quantity }}
                                        <span class="text-[11px] font-medium text-gray-400 ml-0.5">{{ item.unit }}</span>
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-0.5 tabular-nums">
                                        {{ log.quantity_before }} → {{ log.quantity_after }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Sidebar ── -->
                <div class="flex flex-col gap-4">

                    <!-- Log stock change — inverted panel, same as action panel in procurement -->
                    <div class="rounded-2xl bg-gray-900 dark:bg-white border border-gray-900 dark:border-gray-100 p-5">
                        <p class="text-sm font-bold text-white dark:text-gray-900 mb-0.5">Log Stock Change</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4">Record usage, restock, or adjustment</p>

                        <form @submit.prevent="submitLog" class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400 mb-1.5">Type</label>
                                <select v-model="logForm.type"
                                        class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 transition-all">
                                    <option value="usage" class="bg-gray-900 dark:bg-white">Usage (reduce)</option>
                                    <option value="restock" class="bg-gray-900 dark:bg-white">Restock (add)</option>
                                    <option value="adjustment" class="bg-gray-900 dark:bg-white">Adjustment</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400 mb-1.5">
                                    Quantity <span class="normal-case font-normal">({{ item.unit }})</span>
                                </label>
                                <input v-model="logForm.quantity" type="number" min="1"
                                       class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 transition-all" />
                                <p v-if="logForm.errors.quantity" class="mt-1 text-[11px] text-red-400">{{ logForm.errors.quantity }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.1em] text-gray-500 dark:text-gray-400 mb-1.5">Reason</label>
                                <input v-model="logForm.reason" type="text" placeholder="Optional"
                                       class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 placeholder:text-gray-600 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 transition-all" />
                            </div>
                            <button type="submit" :disabled="logForm.processing"
                                    class="w-full py-2.5 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-[0.98] transition-all disabled:opacity-50 cursor-pointer mt-1">
                                {{ logForm.processing ? 'Saving…' : 'Log Change' }}
                            </button>
                        </form>
                    </div>

                    <!-- Item details card -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">Item Details</p>
                        </div>
                        <div class="px-5 py-3.5 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Category</span>
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ item.category ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Building</span>
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ item.building?.name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Unit</span>
                                <span class="text-[12px] font-medium text-gray-700 dark:text-gray-300">{{ item.unit }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400 font-medium">Status</span>
                                <span :class="[
                                    'text-[11px] font-semibold px-2 py-0.5 rounded-full',
                                    isLow
                                        ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
                                        : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
                                ]">{{ isLow ? 'Low Stock' : 'In Stock' }}</span>
                            </div>
                        </div>
                        <div v-if="isManager" class="px-5 pb-4 pt-1">
                            <Link :href="route('manage.stock.edit', item.id)"
                                  class="w-full flex items-center justify-center gap-2 py-2 rounded-xl border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-all">
                                <Pencil class="w-3.5 h-3.5" />
                                Edit Item
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </ManageLayout>
</template>
