<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import StatCard from '@/Components/StatCard.vue'
import { TrendingUp, Scale, Wrench, ShoppingCart, ArrowRight } from 'lucide-vue-next'
import { formatAmount } from './helpers'

const props = defineProps({ owner: Object })

const netPositive    = computed(() => (props.owner?.net_month ?? 0) >= 0)
const approvalsCount = computed(() => (props.owner?.approvals?.maintenance_count ?? 0) + (props.owner?.approvals?.procurement_count ?? 0))
</script>

<template>
    <div>
        <!-- Your numbers -->
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Your numbers</h2>
            <Link :href="route('manage.dashboard')" class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 inline-flex items-center gap-1">
                Full dashboard <ArrowRight class="w-3 h-3" />
            </Link>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-6">
            <StatCard label="Revenue · this month" :icon="TrendingUp" label-class="text-emerald-600 dark:text-emerald-400">
                <template #value><span class="text-emerald-600 dark:text-emerald-400">{{ formatAmount(owner.revenue_month) }}</span></template>
            </StatCard>
            <StatCard label="Net · this month" :icon="Scale"
                      :label-class="netPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                <template #value><span :class="netPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">{{ formatAmount(owner.net_month) }}</span></template>
            </StatCard>
            <StatCard label="Awaiting your approval" :value="approvalsCount"
                      accent="amber" :active="approvalsCount > 0"
                      :sublabel="approvalsCount === 1 ? 'decision' : 'decisions'" />
            <StatCard label="Value in your queue" :value="formatAmount((owner.approvals.maintenance_value ?? 0) + (owner.approvals.procurement_value ?? 0))"
                      sublabel="pending sign-off" />
        </div>

        <!-- Awaiting your approval -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-lg shadow-sm shadow-gray-200/50 dark:shadow-none p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Awaiting your approval</h3>
            <div v-if="approvalsCount > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <Link v-if="owner.approvals.maintenance_count > 0" :href="route('manage.maintenance.index') + '?status=accountant_approved'"
                      class="flex items-center gap-2.5 p-3 rounded-lg bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-100 dark:hover:bg-amber-500/15 transition-colors">
                    <Wrench class="w-4 h-4 text-amber-500 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Maintenance</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ owner.approvals.maintenance_count }} request{{ owner.approvals.maintenance_count !== 1 ? 's' : '' }}</p>
                    </div>
                    <span class="ml-auto text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(owner.approvals.maintenance_value) }}</span>
                </Link>
                <Link v-if="owner.approvals.procurement_count > 0" :href="route('manage.procurement.index') + '?status=accountant_approved'"
                      class="flex items-center gap-2.5 p-3 rounded-lg bg-violet-50 dark:bg-violet-500/10 hover:bg-violet-100 dark:hover:bg-violet-500/15 transition-colors">
                    <ShoppingCart class="w-4 h-4 text-violet-500 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-900 dark:text-white">Procurement</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ owner.approvals.procurement_count }} request{{ owner.approvals.procurement_count !== 1 ? 's' : '' }}</p>
                    </div>
                    <span class="ml-auto text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ formatAmount(owner.approvals.procurement_value) }}</span>
                </Link>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                <Scale class="w-7 h-7 text-emerald-400 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Nothing awaiting your sign-off.</p>
            </div>
        </div>
    </div>
</template>
