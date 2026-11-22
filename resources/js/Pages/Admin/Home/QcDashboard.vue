<script setup>
import { Link } from '@inertiajs/vue3'
import StatCard from '@/Components/StatCard.vue'
import { ClipboardCheck, Search, Sparkles, CheckCircle2, ArrowRight } from 'lucide-vue-next'
import { ago, arrivesLabel } from './helpers'

defineProps({ qc: Object })
</script>

<template>
    <div>
        <!-- Turnover pipeline -->
        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Unit turnover</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
            <StatCard label="Ready to inspect" :icon="ClipboardCheck" label-class="text-blue-600 dark:text-blue-400"
                      :href="route('manage.inspections.index')" :value="qc.counts.ready_for_qa"
                      accent="blue" :active="qc.counts.ready_for_qa > 0" />
            <StatCard label="Inspecting now" :icon="Search" label-class="text-amber-600 dark:text-amber-400"
                      :href="route('manage.inspections.index')" :value="qc.counts.qa_in_progress"
                      accent="amber" :active="qc.counts.qa_in_progress > 0" />
            <StatCard label="In cleaning" :icon="Sparkles" :value="qc.counts.cleaning"
                      :sublabel="`${qc.counts.needs_cleaning} awaiting cleaning`" />
            <StatCard label="Guest ready" :icon="CheckCircle2" label-class="text-emerald-600 dark:text-emerald-400"
                      :value="qc.counts.ready">
                <template #sublabel>{{ qc.counts.occupied }} occupied<span v-if="qc.counts.blocked > 0"> · {{ qc.counts.blocked }} blocked</span></template>
            </StatCard>
        </div>

        <!-- QC worklist: units waiting to be inspected -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none overflow-hidden mb-8">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <ClipboardCheck class="w-4 h-4 text-blue-500" /> Inspection queue
                    <span v-if="qc.queue.length" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">{{ qc.queue.length }}</span>
                </h3>
                <Link :href="route('manage.inspections.index')" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">Open inspections →</Link>
            </div>
            <div v-if="qc.queue.length" class="divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="u in qc.queue" :key="u.unit_id"
                     class="flex items-center gap-3 px-5 py-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                         :class="u.state === 'qa_in_progress' ? 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400'">
                        {{ u.unit_number }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            Unit {{ u.unit_number }}
                            <span class="font-normal text-gray-400 dark:text-gray-500">· {{ u.unit_type }}</span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                            <span v-if="u.building">{{ u.building }} · </span>
                            <span :class="u.state === 'qa_in_progress' ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400'">
                                {{ u.state === 'qa_in_progress' ? 'Inspecting' : 'Ready for QA' }}
                            </span>
                            <span v-if="ago(u.since)"> · {{ ago(u.since) }}</span>
                            <span v-if="u.arrival" class="text-gray-500 dark:text-gray-400"> · {{ arrivesLabel(u.arrival) }}</span>
                        </p>
                    </div>
                    <Link :href="route('manage.inspections.index')"
                          class="inline-flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 shrink-0">
                        Inspect <ArrowRight class="w-3 h-3" />
                    </Link>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                <CheckCircle2 class="w-8 h-8 text-emerald-400 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No units waiting for inspection right now.</p>
            </div>
        </div>
    </div>
</template>
