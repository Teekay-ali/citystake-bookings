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
    usage:      { label: 'Usage',      class: 'text-red-600 dark:text-red-400' },
    restock:    { label: 'Restock',    class: 'text-emerald-600 dark:text-emerald-400' },
    adjustment: { label: 'Adjustment', class: 'text-blue-600 dark:text-blue-400' },
}

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

        <div class="p-6 lg:p-8 max-w-3xl">

            <Link :href="route('manage.stock.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Stock
            </Link>

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ item.name }}</h1>
                        <AlertTriangle v-if="item.quantity <= item.low_stock_threshold"
                                       class="w-5 h-5 text-amber-500" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ item.category ?? 'No category' }} · {{ item.building?.name }}
                    </p>
                </div>
                <Link v-if="isManager" :href="route('manage.stock.edit', item.id)"
                      class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                    <Pencil class="w-4 h-4" /> Edit
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Current stock -->
                    <div class="bg-white dark:bg-gray-900 border rounded-2xl p-6"
                         :class="item.quantity <= item.low_stock_threshold
                            ? 'border-amber-200 dark:border-amber-800'
                            : 'border-gray-200 dark:border-gray-800'">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Current Stock</p>
                                <p class="text-4xl font-bold"
                                   :class="item.quantity <= item.low_stock_threshold
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-gray-900 dark:text-white'">
                                    {{ item.quantity }}
                                    <span class="text-lg font-normal text-gray-400">{{ item.unit }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-400 mb-1">Low stock alert at</p>
                                <p class="text-lg font-medium text-gray-600 dark:text-gray-400">
                                    {{ item.low_stock_threshold }} {{ item.unit }}
                                </p>
                            </div>
                        </div>
                        <div v-if="item.quantity <= item.low_stock_threshold"
                             class="mt-3 flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                            <AlertTriangle class="w-4 h-4" />
                            Low stock — consider restocking
                        </div>
                    </div>

                    <!-- Usage history -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">History</h2>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="log in logs.data" :key="log.id"
                                 class="px-6 py-4 flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span :class="[typeConfig[log.type].class, 'text-xs font-medium capitalize']">
                                            {{ typeConfig[log.type].label }}
                                        </span>
                                        <span v-if="log.reason" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            — {{ log.reason }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ log.logged_by?.name }} · {{ formatDateTime(log.created_at) }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p :class="log.quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'"
                                       class="text-sm font-semibold">
                                        {{ log.quantity > 0 ? '+' : '' }}{{ log.quantity }} {{ item.unit }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ log.quantity_before }} → {{ log.quantity_after }}</p>
                                </div>
                            </div>
                            <div v-if="logs.data.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">
                                No history yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar — Log usage -->
                <div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 sticky top-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Log Stock Change</h3>
                        <form @submit.prevent="submitLog" class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Type</label>
                                <select v-model="logForm.type"
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                                    <option value="usage">Usage (reduce)</option>
                                    <option value="restock">Restock (add)</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    Quantity ({{ item.unit }})
                                </label>
                                <input v-model="logForm.quantity" type="number" min="1"
                                       class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                <p v-if="logForm.errors.quantity" class="mt-1 text-xs text-red-600">{{ logForm.errors.quantity }}</p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Reason</label>
                                <input v-model="logForm.reason" type="text" placeholder="Optional"
                                       class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                            </div>
                            <button type="submit" :disabled="logForm.processing"
                                    class="w-full px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-medium hover:opacity-90 disabled:opacity-50 transition-all">
                                {{ logForm.processing ? 'Saving...' : 'Log Change' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </ManageLayout>
</template>
