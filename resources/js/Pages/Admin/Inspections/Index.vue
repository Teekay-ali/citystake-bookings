<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ClipboardCheck, Building2, CheckCircle, AlertTriangle, ArrowRight, DoorOpen, Clock3, Loader2 } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    tab:       String,
    toInspect: { type: Array, default: () => [] },
    history:   { type: Object, default: null },
    stats:     { type: Object, default: () => ({}) },
})

const OVERDUE_DAYS = 14

function setTab(tab) {
    router.get(route('manage.inspections.index'), { tab }, { preserveState: true, replace: true })
}

function startInspection(unit) {
    if (unit.in_progress_id) router.get(route('manage.inspections.show', unit.in_progress_id))
    else router.post(route('manage.inspections.start'), { unit_id: unit.id })
}

// Status pill for a unit: in-progress > never > overdue > recent
function unitStatus(unit) {
    if (unit.in_progress_id) return { label: 'In progress', tone: 'blue', dot: 'bg-blue-500' }
    if (!unit.last_inspected_at) return { label: 'Never inspected', tone: 'amber', dot: 'bg-amber-500' }
    const days = Math.floor((Date.now() - new Date(unit.last_inspected_at)) / 86400000)
    if (days >= OVERDUE_DAYS) return { label: `Due · ${days}d ago`, tone: 'amber', dot: 'bg-amber-500' }
    if (days <= 0) return { label: 'Inspected today', tone: 'gray', dot: 'bg-emerald-500' }
    return { label: `${days}d ago`, tone: 'gray', dot: 'bg-emerald-500' }
}
const toneCls = {
    blue:  'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
    amber: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
    gray:  'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400',
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
}

const statCards = [
    { key: 'to_inspect',     label: 'Vacant to inspect', cls: 'text-gray-500 dark:text-gray-400' },
    { key: 'in_progress',    label: 'In progress',       cls: 'text-blue-600 dark:text-blue-400' },
    { key: 'completed_week', label: 'Done this week',    cls: 'text-emerald-600 dark:text-emerald-400' },
    { key: 'open_concerns',  label: 'Open concerns',     cls: 'text-amber-600 dark:text-amber-400' },
]
</script>

<template>
    <Head title="Inspections" />

    <div class="p-4 lg:p-6">

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">Inspections</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Routine quality-control checks of vacant units</p>
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
            <button @click="setTab('to_inspect')"
                    :class="tab === 'to_inspect' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 sm:flex-none px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition-all">
                To inspect
            </button>
            <button @click="setTab('history')"
                    :class="tab === 'history' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 sm:flex-none px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition-all">
                History
            </button>
        </div>

        <!-- ── To inspect ── -->
        <template v-if="tab === 'to_inspect'">
            <div v-if="toInspect.length === 0"
                 class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none py-16 text-center">
                <DoorOpen class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No vacant units to inspect right now.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Units appear here as soon as guests check out.</p>
            </div>

            <div v-else class="space-y-8">
                <section v-for="building in toInspect" :key="building.building_id">
                    <div class="flex items-center gap-2 mb-3">
                        <Building2 class="w-4 h-4 text-gray-400" />
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ building.building_name }}</h2>
                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">{{ building.units.length }} vacant</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <button v-for="unit in building.units" :key="unit.id"
                                @click="startInspection(unit)"
                                class="group text-left bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 hover:border-gray-300 dark:hover:border-gray-700 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-gray-900 dark:text-white leading-tight">Unit {{ unit.unit_number }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ unit.unit_type }}<span v-if="unit.floor"> · {{ unit.floor }} floor</span></p>
                                </div>
                                <span class="shrink-0 w-2 h-2 rounded-full mt-1.5" :class="unitStatus(unit).dot" />
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-4">
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-1 rounded-lg" :class="toneCls[unitStatus(unit).tone]">
                                    <Loader2 v-if="unit.in_progress_id" class="w-3 h-3" />
                                    <Clock3 v-else class="w-3 h-3" />
                                    {{ unitStatus(unit).label }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold transition-colors"
                                      :class="unit.in_progress_id ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'">
                                    {{ unit.in_progress_id ? 'Resume' : 'Inspect' }}
                                    <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                                </span>
                            </div>
                        </button>
                    </div>
                </section>
            </div>
        </template>

        <!-- ── History ── -->
        <template v-else>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden">
                <div v-if="!history || history.data.length === 0" class="py-16 text-center">
                    <ClipboardCheck class="w-10 h-10 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">No completed inspections yet.</p>
                </div>

                <template v-else>
                    <table class="hidden md:table w-full text-[13px]">
                        <thead class="border-b border-gray-100 dark:border-gray-800">
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-2.5 font-medium">Unit</th>
                            <th class="px-4 py-2.5 font-medium">Property</th>
                            <th class="px-4 py-2.5 font-medium">Inspector</th>
                            <th class="px-4 py-2.5 font-medium">Result</th>
                            <th class="px-4 py-2.5 font-medium">Completed</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="i in history.data" :key="i.id"
                            @click="router.visit(route('manage.inspections.show', i.id))"
                            class="group cursor-pointer hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">Unit {{ i.unit_number }}</td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ i.building_name }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-semibold flex items-center justify-center">{{ (i.inspector ?? '?').charAt(0).toUpperCase() }}</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ i.inspector ?? '-' }}</span>
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="i.overall_result === 'concerns'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">
                                    <AlertTriangle class="w-3 h-3" /> {{ i.findings_count }} concern{{ i.findings_count !== 1 ? 's' : '' }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle class="w-3 h-3" /> All OK
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-400 text-xs whitespace-nowrap">{{ formatDate(i.completed_at) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <ArrowRight class="inline w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-all" />
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                        <Link v-for="i in history.data" :key="i.id" :href="route('manage.inspections.show', i.id)"
                              class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Unit {{ i.unit_number }}</p>
                                <span v-if="i.overall_result === 'concerns'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">
                                    <AlertTriangle class="w-3 h-3" /> {{ i.findings_count }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle class="w-3 h-3" /> OK
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ i.building_name }} · {{ i.inspector ?? '-' }} · {{ formatDate(i.completed_at) }}</p>
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
