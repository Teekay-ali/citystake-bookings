<script setup>
import StatCard from '@/Components/StatCard.vue'
import { TrendingUp, TrendingDown, CreditCard, ShoppingCart } from 'lucide-vue-next'
import { formatAmount } from './helpers'

defineProps({
    pendingPayments: Object,
    monthRevenue:    Number,
    monthExpenses:   Number,
})
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-6">
        <StatCard label="This Month Income" :icon="TrendingUp" label-class="text-emerald-600 dark:text-emerald-400">
            <template #value><span class="text-emerald-600 dark:text-emerald-400">{{ formatAmount(monthRevenue) }}</span></template>
        </StatCard>
        <StatCard label="This Month Expenses" :icon="TrendingDown" label-class="text-red-600 dark:text-red-400">
            <template #value><span class="text-red-600 dark:text-red-400">{{ formatAmount(monthExpenses) }}</span></template>
        </StatCard>
        <StatCard label="Pending Maintenance" :icon="CreditCard" label-class="text-amber-600 dark:text-amber-400"
                  :href="route('manage.financials.index')" :value="pendingPayments.maintenance" />
        <StatCard label="Pending Procurement" :icon="ShoppingCart" label-class="text-amber-600 dark:text-amber-400"
                  :href="route('manage.financials.index')" :value="pendingPayments.procurement" />
    </div>
</template>
