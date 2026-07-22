<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ClipboardCheck, Building2, CheckCircle, AlertTriangle, ArrowRight, DoorOpen, PlayCircle } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    tab:     String,
    today:   { type: Array, default: () => [] },
    history: { type: Object, default: null },
    stats:   { type: Object, default: () => ({}) },
})

function setTab(tab) {
    router.get(route('manage.inspections.index'), { tab }, { preserveState: true, replace: true })
}

function openRound(card) {
    if (card.round_id) router.get(route('manage.inspections.round', card.round_id))
    else router.post(route('manage.inspections.rounds.open'), { building_id: card.building_id })
}

// Progress % of inspectable units done
function progress(card) {
    return card.inspectable > 0 ? Math.round((card.inspected / card.inspectable) * 100) : 0
}

function roundBadge(card) {
    if (card.status === 'completed') return { label: 'Completed', cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' }
    if (card.status === 'in_progress') return { label: 'In progress', cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' }
    if (card.status === 'cancelled') return { label: 'Discarded', cls: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }
    return { label: 'Not started', cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' }
}

function ctaLabel(card) {
    if (!card.round_id || card.status === 'cancelled') return 'Start round'
    if (card.status === 'completed') return 'View round'
    return 'Continue round'
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
}
const todayLabel = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' })

const statCards = [
    { key: 'active_rounds',   label: 'Active rounds',    cls: 'text-blue-600 dark:text-blue-400' },
    { key: 'inspected_today', label: 'Inspected today',  cls: 'text-gray-500 dark:text-gray-400' },
    { key: 'rounds_week',     label: 'Rounds this week', cls: 'text-emerald-600 dark:text-emerald-400' },
    { key: 'open_concerns',   label: 'Open concerns',    cls: 'text-amber-600 dark:text-amber-400' },
]
</script>

<template>
    <Head title="Inspections" />

    <div class="p-4 lg:p-6">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Inspections</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Daily quality-control rounds · {{ todayLabel }}</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div v-for="s in statCards" :key="s.key"
                 class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none px-4 py-3">
                <p class="text-xs font-medium uppercase tracking-wider mb-1.5" :class="s.cls">{{ s.label }}</p>
                <p class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ stats[s.key] ?? 0 }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1 w-full sm:w-fit mb-6">
            <button @click="setTab('today')"
                    :class="tab === 'today' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 sm:flex-none px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition-all">
                Today
            </button>
            <button @click="setTab('history')"
                    :class="tab === 'history' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 sm:flex-none px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition-all">
                History
            </button>
        </div>

        <!-- ── Today: one card per property ── -->
        <template v-if="tab === 'today'">
            <div v-if="today.length === 0"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none py-16 text-center">
                <DoorOpen class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No properties available to inspect.</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <button v-for="card in today" :key="card.building_id"
                        @click="openRound(card)"
                        class="group text-left bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 hover:border-gray-300 dark:hover:border-gray-700 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <Building2 class="w-4 h-4 text-gray-400 shrink-0" />
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ card.building_name }}</h2>
                        </div>
                        <span class="shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full" :class="roundBadge(card).cls">{{ roundBadge(card).label }}</span>
                    </div>

                    <!-- Progress -->
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        <span>{{ card.inspected }} of {{ card.inspectable }} inspected</span>
                        <span v-if="card.concerns > 0" class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                            <AlertTriangle class="w-3 h-3" /> {{ card.concerns }}
                        </span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             :class="card.status === 'completed' ? 'bg-emerald-500' : 'bg-blue-500'"
                             :style="{ width: progress(card) + '%' }" />
                    </div>

                    <div class="flex items-center justify-between gap-2 mt-4">
                        <span class="text-[11px] text-gray-400">
                            {{ card.total }} unit{{ card.total !== 1 ? 's' : '' }}<template v-if="card.occupied"> · {{ card.occupied }} occupied</template>
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-900 dark:text-white">
                            <PlayCircle v-if="!card.round_id || card.status === 'cancelled'" class="w-3.5 h-3.5" />
                            {{ ctaLabel(card) }}
                            <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                        </span>
                    </div>
                </button>
            </div>
        </template>

        <!-- ── History: completed / discarded rounds ── -->
        <template v-else>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                <div v-if="!history || history.data.length === 0" class="py-16 text-center">
                    <ClipboardCheck class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No past rounds yet.</p>
                </div>

                <template v-else>
                    <table class="hidden md:table w-full text-[13px]">
                        <thead class="border-b border-gray-100 dark:border-gray-800">
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5 font-medium">Date</th>
                            <th class="px-4 py-2.5 font-medium">Property</th>
                            <th class="px-4 py-2.5 font-medium">Inspected</th>
                            <th class="px-4 py-2.5 font-medium">Result</th>
                            <th class="px-4 py-2.5 font-medium">By</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="r in history.data" :key="r.id"
                            @click="router.visit(route('manage.inspections.round', r.id))"
                            class="group cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
                            :class="r.status === 'cancelled' && 'text-gray-400 dark:text-gray-600'">
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ formatDate(r.round_date) }}</td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ r.building_name }}</td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ r.inspected }} unit{{ r.inspected !== 1 ? 's' : '' }}</td>
                            <td class="px-4 py-2.5">
                                <span v-if="r.status === 'cancelled'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">Discarded</span>
                                <span v-else-if="r.concerns > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">
                                    <AlertTriangle class="w-3 h-3" /> {{ r.concerns }} concern{{ r.concerns !== 1 ? 's' : '' }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle class="w-3 h-3" /> All clear
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-400 text-xs">{{ r.completed_by ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <ArrowRight class="inline w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" />
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="r in history.data" :key="r.id" :href="route('manage.inspections.round', r.id)"
                              class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ r.building_name }}</p>
                                <span v-if="r.status === 'cancelled'" class="text-xs text-gray-400">Discarded</span>
                                <span v-else-if="r.concerns > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">
                                    <AlertTriangle class="w-3 h-3" /> {{ r.concerns }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle class="w-3 h-3" /> OK
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ formatDate(r.round_date) }} · {{ r.inspected }} inspected · {{ r.completed_by ?? '—' }}</p>
                        </Link>
                    </div>
                </template>

                <div v-if="history && history.last_page > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex justify-center gap-1.5">
                    <Link v-for="link in history.links" :key="link.label" :href="link.url || '#'"
                          :class="[
                              'min-w-[32px] h-8 flex items-center justify-center px-2.5 rounded-lg text-xs transition-all',
                              link.active ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium' : 'border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                              !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                          ]"
                          v-html="link.label" />
                </div>
            </div>
        </template>
    </div>
</template>
