<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import NumberField from '@/Components/NumberField.vue'
import { X, Plus, Utensils, Hammer, Tag, Ban, ShieldCheck } from 'lucide-vue-next'

const props = defineProps({
    show:    Boolean,
    booking: Object,
})
const emit = defineEmits(['close'])

const fmt = (v) => '₦' + Number(v || 0).toLocaleString('en-NG')

const categories = [
    { key: 'food',   label: 'Food / Restaurant', icon: Utensils, placeholder: 'e.g. Dinner at the restaurant' },
    { key: 'damage', label: 'Damaged Item',      icon: Hammer,   placeholder: 'e.g. Broken TV remote' },
    { key: 'other',  label: 'Other',             icon: Tag,      placeholder: 'What is this charge for?' },
]
const descPlaceholder = computed(() =>
    categories.find(c => c.key === form.value.category)?.placeholder ?? 'What is this charge for?'
)
const catMeta = {
    food:   { label: 'Food / Restaurant', cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', icon: Utensils },
    damage: { label: 'Damaged Item',      cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400',                 icon: Hammer },
    other:  { label: 'Other',             cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',               icon: Tag },
}

const available = computed(() => Number(props.booking.caution_available ?? props.booking.caution_fee))
const used      = computed(() => Number(props.booking.caution_used ?? 0))
const total     = computed(() => Number(props.booking.caution_fee))
const usedPct   = computed(() => total.value > 0 ? Math.min(100, (used.value / total.value) * 100) : 0)

const activeCharges = computed(() => (props.booking.caution_charges ?? []).filter(c => !c.voided_at))
const voidedCharges = computed(() => (props.booking.caution_charges ?? []).filter(c => c.voided_at))

// ── Add charge ──
const form = ref({ category: 'food', description: '', amount: null })
const submitting = ref(false)
const error = ref('')

watch(() => props.show, (open) => {
    if (open) { form.value = { category: 'food', description: '', amount: null }; error.value = '' }
})

const overBudget = computed(() => Number(form.value.amount) > available.value)

const isOther = computed(() => form.value.category === 'other')

function submit() {
    error.value = ''
    // Food / Damage use the category as their description; only "Other" needs a typed note.
    const description = isOther.value
        ? form.value.description.trim()
        : categories.find(c => c.key === form.value.category).label
    if (isOther.value && !description) { error.value = 'Describe the charge.'; return }
    if (!form.value.amount || form.value.amount <= 0) { error.value = 'Enter a valid amount.'; return }
    if (overBudget.value) { error.value = `Only ${fmt(available.value)} remaining.`; return }

    submitting.value = true
    router.post(route('manage.bookings.caution-charges.store', props.booking.booking_reference),
        { category: form.value.category, description, amount: form.value.amount }, {
        preserveScroll: true,
        onSuccess: () => { form.value = { category: 'food', description: '', amount: null } },
        onFinish: () => { submitting.value = false },
    })
}

// ── Void charge ──
const voidingId = ref(null)
const voidReason = ref('')

function startVoid(id) { voidingId.value = id; voidReason.value = '' }
function cancelVoid() { voidingId.value = null; voidReason.value = '' }

function confirmVoid(charge) {
    if (!voidReason.value.trim()) return
    router.post(route('manage.bookings.caution-charges.void', [props.booking.booking_reference, charge.id]),
        { reason: voidReason.value }, {
        preserveScroll: true,
        onSuccess: () => { voidingId.value = null; voidReason.value = '' },
    })
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-amber-500" /> Manage Caution Fee
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Charge food, damages or other costs against {{ booking.guest_name }}'s caution fee.
                    </p>
                </div>
                <button @click="emit('close')"
                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Balance meter -->
            <div class="rounded-xl border border-gray-200/80 dark:border-gray-800 p-4 mb-5 bg-gray-50/60 dark:bg-gray-800/30">
                <div class="flex items-end justify-between mb-2">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Available</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ fmt(available) }}</p>
                    </div>
                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                        <p>{{ fmt(used) }} used</p>
                        <p class="text-gray-400 dark:text-gray-500">of {{ fmt(total) }}</p>
                    </div>
                </div>
                <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         :class="usedPct >= 100 ? 'bg-red-500' : usedPct > 75 ? 'bg-amber-500' : 'bg-emerald-500'"
                         :style="{ width: usedPct + '%' }"></div>
                </div>
            </div>

            <!-- Settled / depleted notices -->
            <div v-if="booking.caution_fee_refunded"
                 class="rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/30 px-4 py-3 mb-5 text-xs text-gray-500 dark:text-gray-400">
                The caution fee has been settled — no further charges can be added.
            </div>
            <div v-else-if="available <= 0"
                 class="rounded-xl border border-amber-200/70 dark:border-amber-900/50 bg-amber-50/70 dark:bg-amber-900/20 px-4 py-3 mb-5 text-xs text-amber-700 dark:text-amber-400">
                The full caution fee has been used. Void a charge to free up balance.
            </div>

            <!-- Add charge -->
            <form v-if="!booking.caution_fee_refunded && available > 0" @submit.prevent="submit" class="space-y-3 mb-5">
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="c in categories" :key="c.key" type="button" @click="form.category = c.key"
                            :class="['flex flex-col items-center gap-1 py-2.5 rounded-lg border text-xs font-medium transition-all',
                                form.category === c.key
                                    ? 'border-gray-900 dark:border-white bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                    : 'border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800']">
                        <component :is="c.icon" class="w-4 h-4" />
                        {{ c.label }}
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-2" :class="isOther ? 'sm:grid-cols-[1fr_10rem]' : ''">
                    <input v-if="isOther" v-model="form.description" type="text" :placeholder="descPlaceholder"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                    <NumberField v-model="form.amount" prefix="₦" :min="0" :max="available" :step="1000" :invalid="overBudget" />
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs" :class="error ? 'text-red-600' : overBudget ? 'text-red-600' : 'text-gray-400 dark:text-gray-500'">
                        <template v-if="error">{{ error }}</template>
                        <template v-else-if="form.amount > 0 && !overBudget">Balance after: <span class="font-medium text-gray-600 dark:text-gray-300">{{ fmt(available - form.amount) }}</span></template>
                        <template v-else>Cannot exceed {{ fmt(available) }} remaining.</template>
                    </p>
                    <button type="submit" :disabled="submitting || available <= 0"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                        <Plus class="w-3.5 h-3.5" /> Add charge
                    </button>
                </div>
            </form>

            <!-- Charges list -->
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                    Charges ({{ activeCharges.length }})
                </p>

                <div v-if="activeCharges.length === 0 && voidedCharges.length === 0"
                     class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
                    No charges yet.
                </div>

                <div v-else class="space-y-2 max-h-72 overflow-auto -mx-1 px-1">
                    <div v-for="c in activeCharges" :key="c.id"
                         class="rounded-lg border border-gray-200/80 dark:border-gray-800 p-3">
                        <div class="flex items-center gap-3">
                            <span :class="['w-8 h-8 rounded-lg grid place-items-center shrink-0', catMeta[c.category].cls]">
                                <component :is="catMeta[c.category].icon" class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ c.description }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                    {{ catMeta[c.category].label }} · {{ c.recorded_by }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ fmt(c.amount) }}</span>
                            <button v-if="!booking.caution_fee_refunded && voidingId !== c.id" @click="startVoid(c.id)"
                                    class="p-1.5 text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Void charge">
                                <Ban class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Void confirm -->
                        <div v-if="voidingId === c.id" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                            <input v-model="voidReason" type="text" placeholder="Reason for voiding…"
                                   class="w-full px-3 py-1.5 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500" />
                            <div class="flex justify-end gap-2">
                                <button @click="cancelVoid" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">Cancel</button>
                                <button @click="confirmVoid(c)" :disabled="!voidReason.trim()"
                                        class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-500 disabled:opacity-40 transition-all">
                                    Void &amp; reverse
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Voided -->
                    <div v-for="c in voidedCharges" :key="'v' + c.id"
                         class="rounded-lg border border-dashed border-gray-200 dark:border-gray-800 p-3 opacity-60">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg grid place-items-center shrink-0 bg-gray-100 dark:bg-gray-800 text-gray-400">
                                <Ban class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 line-through truncate">{{ c.description }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">Voided by {{ c.voided_by }} · {{ c.void_reason }}</p>
                            </div>
                            <span class="text-sm text-gray-400 line-through tabular-nums whitespace-nowrap">{{ fmt(c.amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
