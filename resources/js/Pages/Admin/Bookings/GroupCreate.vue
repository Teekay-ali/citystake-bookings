<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted } from 'vue'
import { useAppToast } from '@/Composables/useAppToast'
import { ArrowLeft, Users, Plus, Trash2, Briefcase, CreditCard, Layers, Percent } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({ buildings: Array })
const toast = useAppToast()

const form = useForm({
    building_id: '',
    check_in: '',
    check_out: '',
    organization_id: '',
    lead_name: '',
    lead_email: '',
    lead_phone: '',
    payment_method: 'pos',
    payment_reference: '',
    discount_mode: 'auto',        // 'auto' | 'manual' | 'none'
    manual_discount: '',          // whole-group flat ₦, split across units
    discount_reason: '',
    members: [
        { unit_type_id: '', unit_id: '', guest_name: '', guest_email: '', guest_phone: '', guests: 1 },
        { unit_type_id: '', unit_id: '', guest_name: '', guest_email: '', guest_phone: '', guests: 1 },
    ],
})

const selectedBuilding = computed(() => props.buildings.find(b => b.id == form.building_id))
const unitTypes = computed(() => selectedBuilding.value?.unit_types || [])

const nights = computed(() => {
    if (!form.check_in || !form.check_out) return 0
    const d = Math.ceil((new Date(form.check_out) - new Date(form.check_in)) / 86400000)
    return d > 0 ? d : 0
})

// ── Units available per type for the shared dates ──
const unitsByType = ref({})   // { typeId: [ {id, label} ] }
const loadingTypes = ref({})

async function loadUnits(typeId) {
    if (!typeId || !form.check_in || !form.check_out) return
    loadingTypes.value[typeId] = true
    try {
        const res = await fetch(route('manage.bookings.available-units') +
            `?unit_type_id=${typeId}&check_in=${form.check_in}&check_out=${form.check_out}`)
        unitsByType.value[typeId] = await res.json()
    } catch { unitsByType.value[typeId] = [] }
    finally { loadingTypes.value[typeId] = false }
}

// Refetch everything when dates/building change
watch([() => form.building_id, () => form.check_in, () => form.check_out], () => {
    unitsByType.value = {}
    form.members.forEach(m => { m.unit_id = ''; if (m.unit_type_id) loadUnits(m.unit_type_id) })
})

function onTypeChange(m) {
    m.unit_id = ''
    if (m.unit_type_id && !unitsByType.value[m.unit_type_id]) loadUnits(m.unit_type_id)
}

// Units for a member's type, excluding units already picked by other rows
function unitsFor(member) {
    const taken = form.members.filter(x => x !== member).map(x => x.unit_id).filter(Boolean)
    return (unitsByType.value[member.unit_type_id] || []).filter(u => !taken.includes(u.id))
}

function addMember() {
    form.members.push({ unit_type_id: '', unit_id: '', guest_name: '', guest_email: '', guest_phone: '', guests: 1 })
}
function removeMember(i) {
    if (form.members.length > 2) form.members.splice(i, 1)
}

// ── Pricing (NGN, per unit) — mirrors the server ──
// The auto multi-room rate: 5%, or 10% at 7+ nights (a group is always 2+ rooms).
const autoRate = computed(() => nights.value >= 7 ? 10 : 5)

// One pass over the members so a whole-group manual discount can be split
// across units in proportion to each unit's room subtotal (matches the server).
const lineItems = computed(() => {
    const rows = form.members.map((m) => {
        const ut = unitTypes.value.find(t => t.id == m.unit_type_id)
        if (!ut || nights.value === 0) return null
        const price = parseFloat(ut.base_price_per_night) || 0
        const subtotal = price * nights.value
        const oneNightAtRate = (selectedBuilding.value?.one_night_caution_uses_rate ?? true) && nights.value === 1
        const caution = oneNightAtRate ? price : parseFloat(selectedBuilding.value?.caution_fee_amount ?? 70000)
        const unit = ut.units?.find(u => u.id == m.unit_id)
        return { label: unit ? `Unit ${unit.unit_number}` : ut.name, typeName: ut.name, subtotal, caution }
    }).filter(Boolean)

    const mode = form.discount_mode
    const totalSub = rows.reduce((s, r) => s + r.subtotal, 0)
    const manualTotal = Math.min(parseFloat(form.manual_discount) || 0, totalSub)
    let running = 0

    return rows.map((r, i) => {
        let discount = 0, rate = 0, type = null
        if (mode === 'auto') {
            rate = autoRate.value
            discount = Math.round(r.subtotal * (rate / 100))
            type = 'auto'
        } else if (mode === 'manual' && totalSub > 0) {
            discount = i === rows.length - 1
                ? Math.round(manualTotal - running)                    // absorb rounding drift
                : Math.round(manualTotal * (r.subtotal / totalSub))
            running += discount
            type = discount > 0 ? 'manual' : null
        }
        return { ...r, rate, type, discount, total: r.subtotal - discount + r.caution }
    })
})

const grandTotal = computed(() => lineItems.value.reduce((s, r) => s + r.total, 0))
const memberTotal = (i) => lineItems.value[i]?.total ?? 0

// ── Organizations ──
const orgs = ref([])
onMounted(async () => {
    try { orgs.value = await (await fetch(route('manage.organizations.options'))).json() } catch { orgs.value = [] }
})

const fmt = (v) => '₦' + Number(v || 0).toLocaleString('en-NG')

const inputCls = "w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"

function submit() {
    form.transform(d => ({
        ...d,
        organization_id: d.organization_id || null,
        // Only send discount inputs relevant to the chosen mode.
        manual_discount: d.discount_mode === 'manual' ? d.manual_discount : null,
        discount_reason: d.discount_mode === 'manual' ? d.discount_reason : null,
    }))
        .post(route('manage.bookings.group.store'), {
            onError: () => toast.error('Please check the form for errors.'),
        })
}
</script>

<template>
    <Head title="New Group Booking" />

    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('manage.bookings.index')" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div class="flex-1">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                    <Layers class="w-5 h-5 text-gray-400" /> New Group Booking
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Book multiple units for one date range</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:items-start">
            <!-- Left: form -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Shared details -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Building <span class="text-red-500">*</span></label>
                            <select v-model="form.building_id" :class="inputCls">
                                <option value="">Select…</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Check-in <span class="text-red-500">*</span></label>
                            <input v-model="form.check_in" type="date" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Check-out <span class="text-red-500">*</span></label>
                            <input v-model="form.check_out" type="date" :min="form.check_in" :class="inputCls" />
                        </div>
                    </div>
                    <p v-if="nights > 0" class="text-xs text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-1.5">{{ nights }} night{{ nights > 1 ? 's' : '' }} · applies to all units</p>

                    <!-- Lead / payer -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Lead / booker name <span class="text-red-500">*</span></label>
                            <input v-model="form.lead_name" type="text" :class="inputCls" placeholder="Who made the booking" />
                            <p v-if="form.errors.lead_name" class="mt-1 text-xs text-red-600">{{ form.errors.lead_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Lead email</label>
                            <input v-model="form.lead_email" type="email" :class="inputCls" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Lead phone</label>
                            <input v-model="form.lead_phone" type="text" :class="inputCls" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5 flex items-center gap-1.5"><Briefcase class="w-3.5 h-3.5" /> Bill to organization (optional)</label>
                        <select v-model="form.organization_id" :class="inputCls">
                            <option value="">Individual (no organization)</option>
                            <option v-for="o in orgs" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>

                    <!-- Discount (applies to the whole group) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5 flex items-center gap-1.5"><Percent class="w-3.5 h-3.5" /> Discount</label>
                        <div class="flex items-center gap-1 p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800">
                            <button v-for="dm in [['auto','Auto'],['manual','Manual'],['none','None']]" :key="dm[0]" type="button" @click="form.discount_mode = dm[0]"
                                    :class="form.discount_mode === dm[0] ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                                    class="flex-1 py-1.5 rounded-md text-xs font-medium transition-all">{{ dm[1] }}</button>
                        </div>
                        <p v-if="form.discount_mode === 'auto'" class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                            Automatic multi-room rate: {{ autoRate }}% off each room{{ nights >= 7 ? ' (7+ nights)' : '' }}.
                        </p>
                        <p v-else-if="form.discount_mode === 'none'" class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                            No discount applied to any unit.
                        </p>
                        <div v-else class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <input v-model.number="form.manual_discount" type="number" min="0" step="1000" placeholder="Total discount ₦" :class="inputCls" />
                                <p v-if="form.errors.manual_discount" class="mt-1 text-xs text-red-600">{{ form.errors.manual_discount }}</p>
                            </div>
                            <div>
                                <input v-model="form.discount_reason" type="text" placeholder="Reason (required)" :class="inputCls" />
                                <p v-if="form.errors.discount_reason" class="mt-1 text-xs text-red-600">{{ form.errors.discount_reason }}</p>
                            </div>
                            <p class="sm:col-span-2 text-[11px] text-gray-400 dark:text-gray-500">Split across all units in proportion to each room's price.</p>
                        </div>
                    </div>
                </div>

                <!-- Members -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Units &amp; guests ({{ form.members.length }})</h2>
                        <button type="button" @click="addMember" class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <Plus class="w-3.5 h-3.5" /> Add unit
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(m, i) in form.members" :key="i" class="rounded-xl border border-gray-200/80 dark:border-gray-800 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Unit {{ i + 1 }}</span>
                                <button v-if="form.members.length > 2" type="button" @click="removeMember(i)" class="p-1 text-gray-300 hover:text-red-500 rounded"><Trash2 class="w-3.5 h-3.5" /></button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <select v-model="m.unit_type_id" @change="onTypeChange(m)" :disabled="!form.building_id || !form.check_in || !form.check_out" :class="[inputCls, 'disabled:opacity-40']">
                                    <option value="">Apartment type…</option>
                                    <option v-for="ut in unitTypes" :key="ut.id" :value="ut.id">{{ ut.name }} - {{ fmt(ut.base_price_per_night) }}/night</option>
                                </select>
                                <select v-model="m.unit_id" :disabled="!m.unit_type_id" :class="[inputCls, 'disabled:opacity-40']">
                                    <option value="">{{ loadingTypes[m.unit_type_id] ? 'Loading…' : 'Select unit…' }}</option>
                                    <option v-for="u in unitsFor(m)" :key="u.id" :value="u.id">{{ u.label }}</option>
                                </select>
                                <input v-model="m.guest_name" type="text" placeholder="Guest name *" :class="inputCls" />
                                <input v-model.number="m.guests" type="number" min="1" placeholder="Guests" :class="inputCls" />
                                <input v-model="m.guest_email" type="email" placeholder="Guest email (optional)" :class="inputCls" />
                                <input v-model="m.guest_phone" type="text" placeholder="Guest phone (optional)" :class="inputCls" />
                            </div>
                            <p class="text-right text-[11px] text-gray-400 mt-1.5">{{ fmt(memberTotal(i)) }}</p>
                        </div>
                    </div>
                    <p v-if="form.errors.members" class="mt-2 text-xs text-red-600">{{ form.errors.members }}</p>
                </div>
            </div>

            <!-- Right: summary -->
            <div class="lg:sticky lg:top-4 space-y-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Payment</p>
                    <div class="flex items-center gap-1 p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800 mb-3">
                        <button v-for="pm in [['pos','POS'],['bank_transfer','Transfer']]" :key="pm[0]" type="button" @click="form.payment_method = pm[0]"
                                :class="form.payment_method === pm[0] ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                                class="flex-1 py-1.5 rounded-md text-xs font-medium transition-all">{{ pm[1] }}</button>
                    </div>
                    <input v-model="form.payment_reference" type="text" placeholder="Payment reference (optional)" :class="inputCls" />

                    <div class="border-t border-gray-100 dark:border-gray-800 my-4" />

                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Booking Summary</p>

                    <p v-if="lineItems.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
                        Select unit types and dates to see the breakdown.
                    </p>

                    <div v-else class="space-y-3">
                        <div v-for="(li, i) in lineItems" :key="i" class="text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-gray-900 dark:text-white">{{ li.label }}</span>
                                <span class="text-gray-400">{{ li.typeName }}</span>
                            </div>
                            <div class="space-y-1 pl-3 border-l border-gray-100 dark:border-gray-800">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Room · {{ nights }} night{{ nights !== 1 ? 's' : '' }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 tabular-nums">{{ fmt(li.subtotal) }}</span>
                                </div>
                                <div v-if="li.discount > 0" class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>Discount<template v-if="li.type === 'auto'"> ({{ li.rate }}%)</template><template v-else> (share)</template></span>
                                    <span class="tabular-nums">− {{ fmt(li.discount) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Caution (refundable)</span>
                                    <span class="text-gray-700 dark:text-gray-300 tabular-nums">{{ fmt(li.caution) }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-0.5 font-medium">
                                    <span class="text-gray-900 dark:text-white">Subtotal</span>
                                    <span class="text-gray-900 dark:text-white tabular-nums">{{ fmt(li.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-baseline justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Group total</span>
                        <span class="text-xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ fmt(grandTotal) }}</span>
                    </div>

                    <button @click="submit" :disabled="form.processing"
                            class="mt-4 w-full py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold rounded-xl hover:opacity-90 disabled:opacity-50 transition-all">
                        {{ form.processing ? 'Creating…' : `Create group booking` }}
                    </button>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 text-center">All units settled now, before check-in.</p>
                </div>
            </div>
        </div>
    </div>
</template>
