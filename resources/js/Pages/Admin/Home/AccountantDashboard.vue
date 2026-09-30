<script setup>
import { Link } from '@inertiajs/vue3'
import { TrendingUp, TrendingDown, CreditCard, ShoppingCart } from 'lucide-vue-next'
import { formatAmount } from './helpers'

defineProps({
    pendingPayments: Object,
    monthRevenue:    Number,
    monthExpenses:   Number,
})
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl p-5">
            <p class="text-xs text-emerald-600 dark:text-emerald-400 mb-1 flex items-center gap-1">
                <TrendingUp class="w-3.5 h-3.5" /> This Month Income
            </p>
            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ formatAmount(monthRevenue) }}</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-2xl p-5">
            <p class="text-xs text-red-600 dark:text-red-400 mb-1 flex items-center gap-1">
                <TrendingDown class="w-3.5 h-3.5" /> This Month Expenses
            </p>
            <p class="text-lg font-bold text-red-700 dark:text-red-400">{{ formatAmount(monthExpenses) }}</p>
        </div>
        <Link :href="route('manage.financials.index')"
              class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
            <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                <CreditCard class="w-3.5 h-3.5" /> Pending Maintenance
            </p>
            <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.maintenance }}</p>
        </Link>
        <Link :href="route('manage.financials.index')"
              class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-5 hover:opacity-90 transition-all">
            <p class="text-xs text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                <ShoppingCart class="w-3.5 h-3.5" /> Pending Procurement
            </p>
            <p class="text-3xl font-bold text-amber-700 dark:text-amber-400">{{ pendingPayments.procurement }}</p>
        </Link>
    </div>
</template>
