<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { TrendingUp, TrendingDown, Building2, Calendar, DollarSign, BarChart3, ArrowLeft } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    overallOccupancy:    Object,
    occupancyByProperty: Array,
    monthlyTrend:        Array,
    topPerformers:       Array,
    revenuePerNight:     Object,
    buildings:           Array,
    filters:             Object,
})

const selectedYear     = ref(props.filters.year)
const selectedMonth    = ref(props.filters.month)
const selectedBuilding = ref(props.filters.building_id || '')

const months = [
    { value: 1,  label: 'January'   }, { value: 2,  label: 'February'  },
    { value: 3,  label: 'March'     }, { value: 4,  label: 'April'     },
    { value: 5,  label: 'May'       }, { value: 6,  label: 'June'      },
    { value: 7,  label: 'July'      }, { value: 8,  label: 'August'    },
    { value: 9,  label: 'September' }, { value: 10, label: 'October'   },
    { value: 11, label: 'November'  }, { value: 12, label: 'December'  },
]

const years = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 3 }, (_, i) => y - i)
})

watch([selectedYear, selectedMonth, selectedBuilding], () => {
    router.get(route('manage.analytics.occupancy'), {
        year:        selectedYear.value,
        month:       selectedMonth.value,
        building_id: selectedBuilding.value || undefined,
    }, { preserveState: true, replace: true })
})

const formatPrice = (price) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN',
    minimumFractionDigits: 0, maximumFractionDigits: 0,
}).format(price || 0)

// Color system based on occupancy threshold
function occupancyColor(rate) {
    if (rate >= 80) return { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-500', label: 'text-emerald-500' }
    if (rate >= 60) return { text: 'text-blue-600 dark:text-blue-400',       bg: 'bg-blue-500',   label: 'text-blue-500' }
    if (rate >= 40) return { text: 'text-amber-600 dark:text-amber-400',     bg: 'bg-amber-500',  label: 'text-amber-500' }
    return           { text: 'text-red-600 dark:text-red-400',               bg: 'bg-red-500',    label: 'text-red-500' }
}

const maxTrendRate = computed(() =>
    Math.max(...(props.monthlyTrend?.map(t => t.rate) ?? [0]), 1)
)

// Sort properties by occupancy rate descending for the unified table
const rankedProperties = computed(() =>
    [...(props.occupancyByProperty ?? [])].sort((a, b) => b.occupancy_rate - a.occupancy_rate)
)

const selectedMonthLabel = computed(() =>
    months.find(m => m.value === selectedMonth.value)?.label ?? ''
)

const selectClass = "pl-3 pr-8 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
</script>

<template>
    <Head title="Occupancy Analytics" />

    <div class="p-6 lg:p-8">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Occupancy Analytics</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ selectedMonthLabel }} {{ selectedYear }}
                    <span v-if="selectedBuilding"> · {{ buildings.find(b => b.id == selectedBuilding)?.name }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select v-model="selectedBuilding" :class="selectClass" style="width: auto">
                    <option value="">All properties</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="selectedMonth" :class="selectClass" style="width: auto">
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
                <select v-model="selectedYear" :class="selectClass" style="width: auto">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>

        <!-- ── KPI strip ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">

            <!-- Hero: Occupancy rate -->
            <div class="bg-gray-900 dark:bg-white rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Occupancy Rate</p>
                <p class="text-3xl font-semibold text-white dark:text-gray-900 tracking-tight mb-1">
                    {{ overallOccupancy.rate }}%
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ overallOccupancy.booked_nights }} of {{ overallOccupancy.available_nights }} nights
                </p>
            </div>

            <!-- Booked nights -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Booked Nights</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ overallOccupancy.booked_nights }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Confirmed stays</p>
            </div>

            <!-- Available nights -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Available Nights</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ overallOccupancy.available_nights }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Total capacity</p>
            </div>

            <!-- RevPAN -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Rev / Available Night</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight mb-1">
                    {{ formatPrice(revenuePerNight.revenue_per_available_night) }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">RevPAN</p>
            </div>
        </div>

        <!-- ── Main grid ── -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 mb-3">

            <!-- Monthly trend chart — 2/5 width -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                <p class="text-sm font-semibold text-gray-900 dark:text-white mb-5">6-Month Trend</p>

                <div class="space-y-3">
                    <div v-for="trend in monthlyTrend" :key="trend.month" class="flex items-center gap-3">
                        <span class="text-xs text-gray-400 dark:text-gray-500 w-8 flex-shrink-0">{{ trend.month }}</span>
                        <div class="flex-1 h-5 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                            <div
                                class="h-full rounded transition-all"
                                :class="occupancyColor(trend.rate).bg"
                                :style="{ width: (trend.rate / maxTrendRate * 100) + '%', minWidth: trend.rate > 0 ? '4px' : '0' }">
                            </div>
                        </div>
                        <span class="text-xs font-semibold w-8 text-right flex-shrink-0"
                              :class="occupancyColor(trend.rate).text">
                            {{ trend.rate }}%
                        </span>
                    </div>
                </div>

                <!-- Legend -->
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 mt-5 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 flex-shrink-0"></span> ≥80% Excellent
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-blue-500 flex-shrink-0"></span> ≥60% Good
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-amber-500 flex-shrink-0"></span> ≥40% Fair
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                        <span class="w-2.5 h-2.5 rounded-sm bg-red-500 flex-shrink-0"></span> &lt;40% Low
                    </div>
                </div>
            </div>

            <!-- Property performance table — 3/5 width -->
            <div class="lg:col-span-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Property Performance</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Ranked by occupancy rate</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">#</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Property</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Booked</th>
                            <th class="text-right px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Available</th>
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider w-36">Rate</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="(property, index) in rankedProperties" :key="property.property"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-5 py-3.5 text-xs text-gray-400 dark:text-gray-500">{{ index + 1 }}</td>
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ property.property }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right text-sm text-gray-600 dark:text-gray-400">
                                {{ property.booked_nights }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-sm text-gray-600 dark:text-gray-400">
                                {{ property.available_nights }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="occupancyColor(property.occupancy_rate).bg"
                                            :style="{ width: property.occupancy_rate + '%' }">
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold w-10 text-right flex-shrink-0"
                                          :class="occupancyColor(property.occupancy_rate).text">
                                            {{ property.occupancy_rate }}%
                                        </span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!rankedProperties.length">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                                No data for this period.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>
