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

        <div class="p-4 lg:p-6">

            <!-- Header (sticky) -->
            <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-6 flex items-center gap-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
                <Link :href="route('manage.stock.index')"
                      class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">{{ item.name }}</h1>
                        <span :class="['inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium',
                            isLow ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400']">
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70" />
                            {{ isLow ? 'Low Stock' : 'In Stock' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        <template v-if="item.category">{{ item.category }} · </template>{{ item.building?.name }} · {{ item.unit }}
                    </p>
                </div>
            </div>

            <!-- ── Body grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

                <!-- ── Main column ── -->
                <div class="lg:col-span-2 flex flex-col gap-4 order-2 lg:order-none">

                    <!-- Stock level hero -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <div class="flex items-end justify-between gap-4 mb-4">
                            <div>
                                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Current Stock</p>
                                <p class="leading-none" :class="isLow ? 'text-amber-500' : 'text-gray-900 dark:text-white'">
                                    <span class="text-3xl font-semibold tabular-nums">{{ item.quantity }}</span>
                                    <span class="text-sm font-medium text-gray-400 ml-1.5">{{ item.unit }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Alert At</p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 tabular-nums">{{ item.low_stock_threshold }} {{ item.unit }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stock Level</span>
                            <span class="text-xs font-medium text-gray-400 tabular-nums">{{ stockPercent }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="isLow ? 'bg-amber-400' : 'bg-gray-900 dark:bg-white'"
                                 :style="{ width: stockPercent + '%' }" />
                        </div>
                        <p v-if="item.description" class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            {{ item.description }}
                        </p>
                    </div>

                    <!-- Usage history -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">History</h2>
                            <span class="text-xs text-gray-400 tabular-nums">{{ logs.total ?? logs.data.length }} entries</span>
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
                <div class="flex flex-col gap-4 order-1 lg:order-none lg:sticky lg:top-20 self-start">

                    <!-- Log stock change — inverted panel, same as action panel in procurement -->
                    <div class="rounded-2xl bg-gray-900 dark:bg-white border border-gray-900 dark:border-gray-100 shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                        <p class="text-sm font-bold text-white dark:text-gray-900 mb-0.5">Log Stock Change</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4">Record usage, restock, or adjustment</p>

                        <form @submit.prevent="submitLog" class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Type</label>
                                <select v-model="logForm.type"
                                        class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 transition-all">
                                    <option value="usage" class="bg-gray-900 dark:bg-white">Usage (reduce)</option>
                                    <option value="restock" class="bg-gray-900 dark:bg-white">Restock (add)</option>
                                    <option value="adjustment" class="bg-gray-900 dark:bg-white">Adjustment</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">
                                    Quantity <span class="normal-case font-normal">({{ item.unit }})</span>
                                </label>
                                <input v-model="logForm.quantity" type="number" min="1"
                                       class="w-full px-3 py-2.5 rounded-xl bg-white/5 dark:bg-black/5 border border-white/10 dark:border-black/10 text-sm text-white dark:text-gray-900 focus:outline-none focus:ring-2 focus:ring-white/20 dark:focus:ring-black/10 transition-all" />
                                <p v-if="logForm.errors.quantity" class="mt-1 text-[11px] text-red-400">{{ logForm.errors.quantity }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Reason</label>
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
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Item Details</p>
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
