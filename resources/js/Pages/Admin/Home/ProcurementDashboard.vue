<script setup>
import { Link } from '@inertiajs/vue3'
import { ClipboardList, ShoppingCart, Clock, Banknote, CheckCircle2 } from 'lucide-vue-next'
import { formatAmount } from './helpers'

defineProps({ procurement: Object })
</script>

<template>
    <div>
        <!-- Stat cards -->
        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Overview</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
            <Link :href="route('manage.procurement.index') + '?status=pending'"
                  class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                  :class="procurement.to_review > 0 ? 'ring-1 ring-amber-300/60 dark:ring-amber-700/40' : ''">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <ClipboardList class="w-3.5 h-3.5" /> Awaiting your review
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.to_review }}</p>
            </Link>
            <Link :href="route('manage.procurement.index') + '?status=ceo_approved'"
                  class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                  :class="procurement.to_purchase > 0 ? 'ring-1 ring-violet-300/60 dark:ring-violet-700/40' : ''">
                <p class="text-xs font-medium text-violet-600 dark:text-violet-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <ShoppingCart class="w-3.5 h-3.5" /> Ready to purchase
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.to_purchase }}</p>
            </Link>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5" /> In approval
                </p>
                <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ procurement.in_approval }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Banknote class="w-3.5 h-3.5" /> Open value
                </p>
                <p class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(procurement.open_value) }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ procurement.completed_month }} completed this month</p>
            </div>
        </div>

        <!-- The two action queues -->
        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Your queue</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">

            <!-- To review -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <ClipboardList class="w-4 h-4 text-amber-500" /> To review
                        <span v-if="procurement.to_review > 0" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">{{ procurement.to_review }}</span>
                    </h3>
                    <Link :href="route('manage.procurement.index') + '?status=pending'" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">View all →</Link>
                </div>
                <div v-if="procurement.reviewQueue.length" class="divide-y divide-gray-100 dark:divide-gray-800 flex-1">
                    <Link v-for="p in procurement.reviewQueue" :key="p.id" :href="route('manage.procurement.show', p.id)"
                          class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.title }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ p.reference }} · {{ p.building?.name }} · {{ p.submitted_by?.name }}</p>
                        </div>
                        <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(p.total_amount) }}</span>
                    </Link>
                </div>
                <div v-else class="flex-1 flex flex-col items-center justify-center py-10 text-center">
                    <CheckCircle2 class="w-8 h-8 text-emerald-400 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing to review - all caught up.</p>
                </div>
            </div>

            <!-- To purchase -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <ShoppingCart class="w-4 h-4 text-violet-500" /> Ready to purchase
                        <span v-if="procurement.to_purchase > 0" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400">{{ procurement.to_purchase }}</span>
                    </h3>
                    <Link :href="route('manage.procurement.index') + '?status=ceo_approved'" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">View all →</Link>
                </div>
                <div v-if="procurement.purchaseQueue.length" class="divide-y divide-gray-100 dark:divide-gray-800 flex-1">
                    <Link v-for="p in procurement.purchaseQueue" :key="p.id" :href="route('manage.procurement.show', p.id)"
                          class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.title }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ p.reference }} · {{ p.building?.name }}</p>
                        </div>
                        <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white shrink-0">{{ formatAmount(p.total_amount) }}</span>
                    </Link>
                </div>
                <div v-else class="flex-1 flex flex-col items-center justify-center py-10 text-center">
                    <ShoppingCart class="w-8 h-8 text-gray-300 dark:text-gray-700 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No approved requests waiting to be purchased.</p>
                </div>
            </div>
        </div>
    </div>
</template>
