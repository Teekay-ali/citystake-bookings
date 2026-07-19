<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useAppToast } from '@/Composables/useAppToast';
import {
    ArrowLeft, Calendar, Users, Mail, Phone,
    CreditCard, User, MessageSquare, Building2,
    Home, Receipt, ChevronRight, Briefcase, Plus, SlidersHorizontal, X
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
    prefill:   Object,
})

const toast = useAppToast();

const form = useForm({
    building_id:       props.prefill?.building_id  ?? '',
    unit_type_id:      props.prefill?.unit_type_id ?? '',
    check_in:          props.prefill?.check_in     ?? '',
    nights:            props.prefill?.nights       ?? '',
    check_out:         props.prefill?.check_out    ?? '',
    unit_id:           props.prefill?.unit_id      ?? '',
    guests:            props.prefill?.guests           ?? 1,
    guest_name:        props.prefill?.guest_name        ?? '',
    guest_email:       props.prefill?.guest_email       ?? '',
    guest_phone:       props.prefill?.guest_phone       ?? '',
    special_requests:  props.prefill?.special_requests  ?? '',
    payment_method:    'pos',
    payment_reference: '',
    // Pricing overrides (Block A)
    discount_mode:          'auto',   // 'auto' | 'manual' | 'none'
    manual_discount_amount: '',
    discount_reason:        '',
    cross_grade:            false,
    // Currency (Block B) - USD contracts with a locked exchange rate
    currency:      'NGN',
    price_usd:     '',
    exchange_rate: '',
    // Payer (Block D1) - optional organization
    organization_id: '',
    // Payment plan (weekly prepaid installments)
    payment_plan: 'full',
    // Backdated booking - allow a past check-in (migration / walk-in already started)
    backdated: false,
})

const isWeekly = computed(() => form.payment_plan === 'weekly')

// Weekly plans are NGN, no discount - keep the other controls consistent
watch(isWeekly, (on) => {
    if (on) { form.currency = 'NGN'; form.discount_mode = 'none' }
})

// Client-side weekly schedule preview (mirrors the server: week nights × nightly, caution on week 1)
const weeklySchedule = computed(() => {
    const n = calculateNights.value
    if (!isWeekly.value || !selectedUnitType.value || n < 1) return []
    const nightly = parseFloat(selectedUnitType.value.base_price_per_night) || 0
    const caution = pricing.value.cautionFee
    const weeks = Math.ceil(n / 7)
    const start = form.check_in ? new Date(form.check_in) : null
    const rows = []
    for (let w = 1; w <= weeks; w++) {
        const weekNights = Math.min(7, n - (w - 1) * 7)
        const due = start ? new Date(start.getTime() + (w - 1) * 7 * 86400000) : null
        rows.push({
            week: w,
            due: due ? due.toISOString().split('T')[0] : '',
            amount: Math.round(weekNights * nightly + (w === 1 ? caution : 0)),
        })
    }
    return rows
})
const dueNow = computed(() => weeklySchedule.value[0]?.amount ?? 0)

// ── Advanced options modal ──
const showOptions = ref(false)
const selectedOrg = computed(() => orgs.value.find(o => o.id == form.organization_id))
const activeOptions = computed(() => {
    const tags = []
    if (isWeekly.value) tags.push('Weekly plan')
    if (isUsd.value) tags.push('USD contract')
    if (form.discount_mode === 'manual') tags.push('Manual discount')
    else if (form.discount_mode === 'none' && !isWeekly.value && !isUsd.value) tags.push('No discount')
    if (form.organization_id) tags.push(selectedOrg.value?.name ?? 'Organization')
    if (form.backdated) tags.push('Backdated')
    return tags
})

// ── Organizations (bill-to payer) ──
const orgs        = ref([])
const showNewOrg  = ref(false)
const newOrg      = ref({ name: '', contact_phone: '' })
const creatingOrg = ref(false)

onMounted(async () => {
    try {
        const res = await fetch(route('manage.organizations.options'))
        orgs.value = await res.json()
    } catch { orgs.value = [] }
})

async function createOrg() {
    if (!newOrg.value.name.trim()) return
    creatingOrg.value = true
    try {
        const { data: org } = await window.axios.post(route('manage.organizations.store'), newOrg.value)
        orgs.value.unshift(org)
        form.organization_id = org.id
        showNewOrg.value = false
        newOrg.value = { name: '', contact_phone: '' }
    } catch {
        toast.error('Could not create organization.')
    } finally {
        creatingOrg.value = false
    }
}

const isUsd = computed(() => form.currency === 'USD')

// Auto (nightly) discount is meaningless for a flat USD contract
watch(isUsd, (usd) => { if (usd && form.discount_mode === 'auto') form.discount_mode = 'none' })

// When cross-grading, the physical unit comes from a different (overflow) type
// while form.unit_type_id stays as the billed/rate type.
const crossGradeTypeId = ref('')
const physicalUnitTypeId = computed(() =>
    form.cross_grade && crossGradeTypeId.value ? crossGradeTypeId.value : form.unit_type_id
)

const availableUnits  = ref([])
const loadingUnits    = ref(false)
const unitsLoaded     = ref(false)

const selectedBuilding = computed(() =>
    props.buildings.find(b => b.id == form.building_id)
);

const availableUnitTypes = computed(() =>
    selectedBuilding.value?.unit_types || []
);

const selectedUnitType = computed(() =>
    availableUnitTypes.value.find(ut => ut.id == form.unit_type_id)
);

// Overflow is an UPGRADE: only offer apartment types priced above the requested
// one (cheapest upgrade first), never a downgrade.
const overflowTypes = computed(() => {
    const base = parseFloat(selectedUnitType.value?.base_price_per_night) || 0
    return availableUnitTypes.value
        .filter(t => t.id != form.unit_type_id && (parseFloat(t.base_price_per_night) || 0) > base)
        .sort((a, b) => (parseFloat(a.base_price_per_night) || 0) - (parseFloat(b.base_price_per_night) || 0))
})

// The upgraded apartment type physically assigned when overflowing
const crossGradeType = computed(() =>
    form.cross_grade && crossGradeTypeId.value
        ? availableUnitTypes.value.find(t => t.id == crossGradeTypeId.value)
        : null
)

// Requested type is sold out for the chosen dates (drives the overflow nudge)
const requestedTypeSoldOut = computed(() =>
    !form.cross_grade && unitsLoaded.value && availableUnits.value.length === 0
)

// Clear the chosen overflow type whenever overflow is switched off
watch(() => form.cross_grade, (on) => { if (!on) crossGradeTypeId.value = '' })

const calculateNights = computed(() => {
    if (!form.check_in || !form.check_out) return 0;
    const diff = Math.ceil(
        (new Date(form.check_out) - new Date(form.check_in)) / (1000 * 60 * 60 * 24)
    );
    return diff > 0 ? diff : 0;
});

// USD contracts are only for long (6-month+) stays (defined after calculateNights;
// watch() evaluates its source at setup, so the dependency must already exist).
const canUseUsd = computed(() => calculateNights.value >= 180)
watch(canUseUsd, (ok) => { if (!ok && form.currency === 'USD') form.currency = 'NGN' })

// Nights → auto-fill checkout
watch([() => form.check_in, () => form.nights], ([checkIn, nights]) => {
    if (checkIn && nights && parseInt(nights) > 0) {
        const d = new Date(checkIn);
        d.setDate(d.getDate() + parseInt(nights));
        form.check_out = d.toISOString().split('T')[0];
    }
});

// Manual checkout → sync nights
watch(() => form.check_out, (checkOut) => {
    if (form.check_in && checkOut) {
        const diff = Math.ceil(
            (new Date(checkOut) - new Date(form.check_in)) / (1000 * 60 * 60 * 24)
        );
        if (diff > 0 && diff !== parseInt(form.nights)) {
            form.nights = String(diff);
        }
    }
});

// Reset unit type when building changes
watch(() => form.building_id, () => {
    form.unit_type_id    = ''
    form.unit_id         = ''
    availableUnits.value = []
    unitsLoaded.value    = false
})

let unitFetchController = null

watch([physicalUnitTypeId, () => form.check_in, () => form.check_out], async ([unitTypeId, checkIn, checkOut]) => {
    form.unit_id    = ''
    availableUnits.value = []
    unitsLoaded.value    = false

    if (!unitTypeId || !checkIn || !checkOut) return

    // Cancel any in-flight request before starting a new one
    if (unitFetchController) unitFetchController.abort()
    unitFetchController = new AbortController()

    loadingUnits.value = true
    try {
        const res = await fetch(
            route('manage.bookings.available-units') +
            `?unit_type_id=${unitTypeId}&check_in=${checkIn}&check_out=${checkOut}`,
            { signal: unitFetchController.signal }
        )
        availableUnits.value = await res.json()
        unitsLoaded.value    = true

        // Re-apply prefill unit after units load
        if (props.prefill?.unit_id) {
            const match = availableUnits.value.find(u => u.id == props.prefill.unit_id)
            if (match) form.unit_id = match.id
        }
    } catch (e) {
        if (e.name !== 'AbortError') availableUnits.value = []
    } finally {
        loadingUnits.value = false
    }
}, { immediate: true })


const pricing = computed(() => {
    const empty = { subtotal: 0, cautionFee: 0, discountAmount: 0, discountPercent: 0, discountType: null, total: 0 };
    if (!selectedUnitType.value || calculateNights.value === 0) return empty;

    const nights     = calculateNights.value;
    // 1-night caution = room rate only when the property opts in (defaults on);
    // otherwise the flat property caution fee. Mirrors the server.
    const flatCaution     = parseFloat(selectedBuilding.value?.caution_fee_amount ?? 70000);
    const oneNightAtRate  = (selectedBuilding.value?.one_night_caution_uses_rate ?? true)
        && nights === 1;
    const cautionFee = isUsd.value
        ? flatCaution
        : (oneNightAtRate
            ? (parseFloat(selectedUnitType.value.base_price_per_night) || 0)
            : flatCaution);

    // Subtotal (always NGN): USD contract = price_usd × rate; otherwise nightly rate
    const subtotal = isUsd.value
        ? Math.round((parseFloat(form.price_usd) || 0) * (parseFloat(form.exchange_rate) || 0))
        : (parseFloat(selectedUnitType.value.base_price_per_night) || 0) * nights;

    let discountPercent = 0;
    let discountType    = null;
    let discountAmount  = 0;

    if (form.discount_mode === 'manual') {
        discountAmount = Math.min(Math.max(parseFloat(form.manual_discount_amount) || 0, 0), subtotal);
        discountType   = discountAmount > 0 ? 'manual' : null;
    } else if (form.discount_mode === 'none' || isUsd.value) {
        // Auto (nightly) discount is meaningless for a flat USD contract
        discountType = null;
    } else {
        if (nights >= 7) { discountPercent = 5; discountType = 'long_stay'; }
        discountAmount = discountPercent > 0 ? Math.round(subtotal * (discountPercent / 100)) : 0;
    }

    const total = (subtotal - discountAmount) + cautionFee;

    return { subtotal, cautionFee, discountAmount, discountPercent, discountType, total };
});

const formatPrice = (v) =>
    new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const formatDateShort = (d) =>
    d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

const submit = () => {
    form.post(route('manage.bookings.store'), {
        onError: () => toast.error('Please check the form for errors.'),
    });
};

// Shared input classes
const inputCls = (hasError) => [
    'w-full px-3 py-2.5 bg-white dark:bg-gray-950 rounded-lg text-sm text-gray-900 dark:text-white',
    'focus:outline-none focus:ring-2 transition-all',
    hasError
        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white',
];
</script>

<template>
    <ManageLayout>
        <Head title="New Booking" />

        <!-- ── Page shell ── -->
        <div class="flex flex-col min-h-full lg:h-full lg:overflow-hidden">
            <!-- Top bar -->
            <div class="flex items-center justify-between px-4 lg:px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shrink-0">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('manage.bookings.index')"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">New Booking</h1>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Walk-in / admin booking</p>
                    </div>
                </div>
            </div>

            <!-- Body: stacked on mobile, side-by-side on lg -->
            <div class="flex flex-col lg:flex-row lg:flex-1 lg:overflow-hidden">

                <!-- ── Form ── -->
                <div class="flex-1 lg:overflow-y-auto p-4 lg:p-6 space-y-5">

                    <!-- Property & Unit Type -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Building2 class="w-3.5 h-3.5" /> Property
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Building <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.building_id"
                                    :class="inputCls(form.errors.building_id)"
                                >
                                    <option value="">Select building</option>
                                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Unit Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.unit_type_id"
                                    :disabled="!form.building_id"
                                    :class="[...inputCls(form.errors.unit_type_id), !form.building_id && 'opacity-40 cursor-not-allowed']"
                                >
                                    <option value="">Select unit type</option>
                                    <option v-for="ut in availableUnitTypes" :key="ut.id" :value="ut.id">
                                        {{ ut.name }} - {{ formatPrice(ut.base_price_per_night) }}/night
                                    </option>
                                </select>
                                <p v-if="form.errors.unit_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.unit_type_id }}</p>
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-gray-100 dark:border-gray-800" />

                    <!-- Stay Details -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Calendar class="w-3.5 h-3.5" /> Stay Details
                        </p>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <!-- Check-in -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Check-in <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.check_in" type="date" :class="inputCls(form.errors.check_in)" />
                                <p v-if="form.errors.check_in" class="mt-1 text-xs text-red-600">{{ form.errors.check_in }}</p>
                            </div>
                            <!-- Check-out -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Check-out <span class="text-red-500">*</span>
                                </label>
                                <input v-model="form.check_out" type="date" :class="inputCls(form.errors.check_out)" />
                                <p v-if="form.errors.check_out" class="mt-1 text-xs text-red-600">{{ form.errors.check_out }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nights -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Nights <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.nights"
                                    type="number"
                                    min="1"
                                    placeholder="e.g. 3"
                                    :class="inputCls(form.errors.nights)"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Auto-fills checkout date</p>
                            </div>
                            <!-- Guests -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Guests <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.guests"
                                    type="number"
                                    min="1"
                                    :max="selectedUnitType?.max_guests || 10"
                                    :class="inputCls(form.errors.guests)"
                                />
                                <p v-if="form.errors.guests" class="mt-1 text-xs text-red-600">{{ form.errors.guests }}</p>
                            </div>

                            <!-- Unit Selection -->
                            <div v-if="form.unit_type_id && form.check_in && form.check_out" class="mt-4 space-y-3">

                                <!-- Overflow (upgrade) — decided before picking a unit -->
                                <div>
                                    <label class="flex items-start gap-2 cursor-pointer select-none">
                                        <input type="checkbox" v-model="form.cross_grade" :disabled="overflowTypes.length === 0"
                                               class="mt-0.5 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-950 text-gray-900 dark:text-indigo-500 focus:ring-gray-900 dark:focus:ring-indigo-500 disabled:opacity-40" />
                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                            Assign an upgraded apartment type <span class="text-gray-400">(overflow)</span>
                                            - still billed at the <span class="font-medium text-gray-700 dark:text-gray-300">{{ selectedUnitType?.name }}</span> rate
                                        </span>
                                    </label>

                                    <!-- Nudge when the requested type is fully booked -->
                                    <p v-if="requestedTypeSoldOut && overflowTypes.length > 0" class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-400">
                                        All {{ selectedUnitType?.name }} units are booked for these dates — enable overflow to assign an upgraded apartment at the same rate.
                                    </p>
                                    <p v-else-if="requestedTypeSoldOut" class="mt-1.5 text-[11px] text-red-500">
                                        All {{ selectedUnitType?.name }} units are booked and no upgraded apartment type is available to overflow into.
                                    </p>
                                </div>

                                <!-- Physical apartment type (only when overflowing) — chosen before the unit -->
                                <div v-if="form.cross_grade">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Physical apartment type <span class="text-red-500">*</span></label>
                                    <select v-model="crossGradeTypeId" :class="inputCls(false)">
                                        <option value="">Select the upgraded apartment being assigned…</option>
                                        <option v-for="ut in overflowTypes" :key="ut.id" :value="ut.id">
                                            {{ ut.name }} - {{ formatPrice(ut.base_price_per_night) }}/night
                                        </option>
                                    </select>
                                </div>

                                <!-- Unit dropdown — reflects the effective (billed or overflow) type -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                        Unit
                                        <span v-if="form.cross_grade" class="text-red-500">*</span>
                                        <span v-else class="text-gray-400 dark:text-gray-500">(optional - auto-assigned if blank)</span>
                                    </label>
                                    <div v-if="loadingUnits" class="flex items-center gap-2 px-3 py-2.5 border border-gray-200 dark:border-gray-800 rounded-lg">
                                        <div class="w-3 h-3 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                                        <span class="text-xs text-gray-400">Checking availability…</span>
                                    </div>
                                    <select v-else v-model="form.unit_id" :class="inputCls(false)"
                                            :disabled="form.cross_grade && !crossGradeTypeId">
                                        <option value="" :disabled="form.cross_grade">
                                            {{ form.cross_grade ? (crossGradeTypeId ? 'Select the overflow unit…' : 'Choose a physical apartment type first') : 'Auto-assign best available unit' }}
                                        </option>
                                        <option v-for="unit in availableUnits" :key="unit.id" :value="unit.id">
                                            {{ unit.label }}
                                        </option>
                                    </select>
                                    <p v-if="unitsLoaded && availableUnits.length === 0 && !(form.cross_grade && !crossGradeTypeId)" class="mt-1 text-xs text-red-500">
                                        No units available for these dates.
                                    </p>
                                    <p v-else-if="unitsLoaded && availableUnits.length > 0" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        {{ availableUnits.length }} unit{{ availableUnits.length !== 1 ? 's' : '' }} available
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- Duration pill -->
                        <div v-if="calculateNights > 0" class="mt-3 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                <span class="font-semibold">{{ calculateNights }} night{{ calculateNights > 1 ? 's' : '' }}</span>
                                &mdash; {{ formatDateShort(form.check_in) }} → {{ formatDateShort(form.check_out) }}
                            </p>
                        </div>
                    </section>

                    <div class="border-t border-gray-100 dark:border-gray-800" />

                    <!-- Guest Information -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <User class="w-3.5 h-3.5" /> Guest Information
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.guest_name"
                                    type="text"
                                    placeholder="Guest's full name"
                                    :class="inputCls(form.errors.guest_name)"
                                />
                                <p v-if="form.errors.guest_name" class="mt-1 text-xs text-red-600">{{ form.errors.guest_name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.guest_email"
                                    type="email"
                                    placeholder="guest@example.com"
                                    :class="inputCls(form.errors.guest_email)"
                                />
                                <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">{{ form.errors.guest_email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Phone <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.guest_phone"
                                    type="tel"
                                    placeholder="080XXXXXXXX"
                                    :class="inputCls(form.errors.guest_phone)"
                                />
                                <p v-if="form.errors.guest_phone" class="mt-1 text-xs text-red-600">{{ form.errors.guest_phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                Special Requests
                            </label>
                            <textarea
                                v-model="form.special_requests"
                                rows="2"
                                placeholder="Any special requirements..."
                                class="w-full px-3 py-2.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all resize-none"
                            />
                        </div>

                    </section>

                    <div class="border-t border-gray-100 dark:border-gray-800" />

                    <!-- Payment -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <CreditCard class="w-3.5 h-3.5" /> Payment
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <!-- POS -->
                            <button type="button" @click="form.payment_method = 'pos'"
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all text-left',
                                    form.payment_method === 'pos'
                                        ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'
                                ]">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                    <CreditCard class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">POS</p>
                                    <p class="text-xs text-gray-400">Card payment</p>
                                </div>
                                <div v-if="form.payment_method === 'pos'" class="ml-auto w-4 h-4 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white dark:bg-gray-900" />
                                </div>
                            </button>

                            <!-- Bank Transfer -->
                            <button type="button" @click="form.payment_method = 'bank_transfer'"
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all text-left',
                                    form.payment_method === 'bank_transfer'
                                        ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'
                                ]">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                                    <Receipt class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Transfer</p>
                                    <p class="text-xs text-gray-400">Bank transfer</p>
                                </div>
                                <div v-if="form.payment_method === 'bank_transfer'" class="ml-auto w-4 h-4 rounded-full bg-gray-900 dark:bg-white flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-white dark:bg-gray-900" />
                                </div>
                            </button>
                        </div>

                        <!-- Reference field -->
                        <div v-if="form.payment_method === 'bank_transfer' || form.payment_method === 'pos'">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                Payment Reference <span class="text-gray-400">(optional)</span>
                            </label>
                            <input
                                v-model="form.payment_reference"
                                type="text"
                                placeholder="Transaction reference"
                                :class="inputCls(false)"
                            />
                        </div>
                    </section>

                    <!-- Bottom spacer so last field isn't hugging the edge -->
                    <div class="h-4" />
                </div>

                <!-- ── Summary ── -->
                <div class="w-full lg:w-72 lg:shrink-0 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex flex-col lg:overflow-y-auto">

                    <div class="p-5 border-b border-gray-200 dark:border-gray-800">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <Receipt class="w-3.5 h-3.5" /> Booking Summary
                        </p>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!selectedUnitType || calculateNights === 0" class="flex-1 flex items-center justify-center p-6">
                        <div class="text-center">
                            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                                <Home class="w-5 h-5 text-gray-400" />
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Select a property and dates<br>to see the summary</p>
                        </div>
                    </div>

                    <!-- Populated summary -->
                    <div v-else class="p-5 space-y-5 flex-1">

                        <!-- Property -->
                        <div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">{{ crossGradeType ? 'BOOKED (BILLED)' : 'PROPERTY' }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedUnitType.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedBuilding.name }}</p>
                        </div>

                        <!-- Upgraded apartment (overflow) -->
                        <div v-if="crossGradeType" class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 px-3 py-2.5">
                            <p class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400 uppercase tracking-wider mb-0.5">Assigned apartment · Upgrade</p>
                            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ crossGradeType.name }}</p>
                            <p class="text-[11px] text-indigo-500/90 dark:text-indigo-400/90 mt-0.5">Billed at the {{ selectedUnitType.name }} rate</p>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white dark:bg-gray-900 rounded-lg p-2.5 border border-gray-200 dark:border-gray-800">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">Check-in</p>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ formatDateShort(form.check_in) }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-900 rounded-lg p-2.5 border border-gray-200 dark:border-gray-800">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">Check-out</p>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ formatDateShort(form.check_out) }}</p>
                            </div>
                        </div>

                        <!-- Nights + Guests -->
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">DURATION</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ calculateNights }} night{{ calculateNights > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">GUESTS</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ form.guests }}</span>
                        </div>

                        <div v-if="form.unit_id" class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Unit</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ availableUnits.find(u => u.id == form.unit_id)?.label ?? '-' }}
                            </span>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-800" />

                        <!-- Pricing -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">
                                    <template v-if="isUsd">${{ Number(form.price_usd || 0).toLocaleString() }} × ₦{{ Number(form.exchange_rate || 0).toLocaleString() }}</template>
                                    <template v-else>{{ formatPrice(selectedUnitType.base_price_per_night) }} × {{ calculateNights }}</template>
                                </span>
                                <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.subtotal) }}</span>
                            </div>
                            <div v-if="pricing.discountAmount > 0" class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                <span>
                                    <template v-if="pricing.discountType === 'manual'">Discount (manual)</template>
                                    <template v-else-if="pricing.discountType === 'long_stay'">Long stay ({{ pricing.discountPercent }}% off)</template>
                                    <template v-else>Discount</template>
                                </span>
                                <span>−{{ formatPrice(pricing.discountAmount) }}</span>
                            </div>
                            <div v-if="pricing.cautionFee > 0" class="flex items-start justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    Caution fee
                                    <span class="px-1 py-0.5 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 text-[10px]">
                                        Refundable
                                    </span>
                                </span>
                                <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.cautionFee) }}</span>
                            </div>
                        </div>

                        <!-- Options (advanced) -->
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1.5">OPTIONS</p>
                            <button type="button" @click="showOptions = true"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all">
                                <span class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <SlidersHorizontal class="w-4 h-4 text-gray-400" /> Booking options
                                </span>
                                <ChevronRight class="w-4 h-4 text-gray-400" />
                            </button>
                            <div v-if="activeOptions.length" class="flex flex-wrap gap-1 mt-1.5">
                                <span v-for="t in activeOptions" :key="t" class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ t }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-800" />

                        <!-- Total -->
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ isWeekly ? 'Full stay total' : 'Total' }}</span>
                            <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ formatPrice(pricing.total) }}</span>
                        </div>
                        <div v-if="isWeekly" class="flex items-baseline justify-between mt-1">
                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Collect now (week 1)</span>
                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ formatPrice(dueNow) }}</span>
                        </div>

                        <!-- Payment method badge -->
                        <div v-if="form.payment_method" class="flex items-center justify-between text-xs">
                            <span class="text-gray-400 dark:text-gray-500">Payment via</span>
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-full font-medium text-gray-700 dark:text-gray-300 capitalize">
                                {{ form.payment_method === 'bank_transfer' ? 'Bank Transfer' : 'POS' }}
                            </span>
                        </div>
                    </div>

                    <!-- Submit button pinned to bottom of sidebar -->
                    <div class="p-5 border-t border-gray-200 dark:border-gray-800 mt-auto">
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                            {{ form.processing ? 'Creating…' : 'Create Booking' }}
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Pricing & payment options modal ── -->
        <Modal :show="showOptions" max-width="md" @close="showOptions = false">
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <SlidersHorizontal class="w-4 h-4 text-gray-400" /> Pricing &amp; payment options
                    </h3>
                    <button @click="showOptions = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Payment plan -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Payment plan</p>
                    <div class="flex items-center gap-1 p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <button v-for="p in [['full','Pay in full'],['weekly','Weekly plan']]" :key="p[0]"
                                type="button" @click="form.payment_plan = p[0]"
                                :class="form.payment_plan === p[0] ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                                class="flex-1 py-1.5 rounded-md text-xs font-medium transition-all">{{ p[1] }}</button>
                    </div>
                    <div v-if="isWeekly" class="mt-2 rounded-lg border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800 overflow-hidden">
                        <div v-for="row in weeklySchedule" :key="row.week" class="flex items-center justify-between px-3 py-1.5 text-[11px]">
                            <span class="text-gray-500 dark:text-gray-400">Week {{ row.week }} · {{ row.due }}<span v-if="row.week === 1" class="text-emerald-600 dark:text-emerald-400"> · due now</span></span>
                            <span class="tabular-nums text-gray-900 dark:text-white">{{ formatPrice(row.amount) }}</span>
                        </div>
                    </div>
                    <p v-if="isWeekly" class="mt-1.5 text-[11px] text-gray-400">Week 1 (incl. caution) is collected now; each week is paid before it begins.</p>
                </div>

                <!-- Currency -->
                <div v-if="!isWeekly && canUseUsd">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Currency</p>
                    <div class="flex items-center gap-1 p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <button v-for="c in [['NGN','₦ Naira'],['USD','$ USD contract']]" :key="c[0]"
                                type="button" @click="form.currency = c[0]"
                                :class="form.currency === c[0] ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                                class="flex-1 py-1.5 rounded-md text-xs font-medium transition-all">{{ c[1] }}</button>
                    </div>
                    <div v-if="isUsd" class="mt-2 grid grid-cols-2 gap-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                            <input v-model.number="form.price_usd" type="number" min="0" step="0.01" placeholder="USD price"
                                   class="w-full pl-7 pr-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white tabular-nums" />
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">₦</span>
                            <input v-model.number="form.exchange_rate" type="number" min="0" step="0.01" placeholder="Rate /$"
                                   class="w-full pl-7 pr-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white tabular-nums" />
                        </div>
                        <p class="col-span-2 text-[11px] text-gray-400">Rate is locked to this booking. Financials recorded in ₦.</p>
                    </div>
                </div>

                <!-- Discount -->
                <div v-if="!isWeekly">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Discount</p>
                    <div class="flex items-center gap-1 p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <button v-for="mode in (isUsd ? [['manual','Manual ₦'],['none','None']] : [['auto','Auto'],['manual','Manual ₦'],['none','None']])" :key="mode[0]"
                                type="button" @click="form.discount_mode = mode[0]"
                                :class="form.discount_mode === mode[0] ? 'bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'"
                                class="flex-1 py-1.5 rounded-md text-xs font-medium transition-all">{{ mode[1] }}</button>
                    </div>
                    <div v-if="form.discount_mode === 'manual'" class="mt-2 space-y-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">₦</span>
                            <input v-model.number="form.manual_discount_amount" type="number" min="0" :max="pricing.subtotal" placeholder="Discount amount"
                                   class="w-full pl-7 pr-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white tabular-nums" />
                        </div>
                        <input v-model="form.discount_reason" type="text" placeholder="Reason (required)"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Bill to organization -->
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5"><Briefcase class="w-3.5 h-3.5" /> Bill to organization</p>
                    <div v-if="!showNewOrg" class="flex items-center gap-2">
                        <select v-model="form.organization_id" :class="inputCls(false)">
                            <option value="">Individual (no organization)</option>
                            <option v-for="o in orgs" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                        <button type="button" @click="showNewOrg = true" class="shrink-0 inline-flex items-center gap-1 px-3 py-2.5 text-xs font-medium border border-gray-200 dark:border-gray-800 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <Plus class="w-3.5 h-3.5" /> New
                        </button>
                    </div>
                    <div v-else class="p-3 border border-gray-200 dark:border-gray-800 rounded-lg space-y-2 bg-gray-50/60 dark:bg-gray-900/40">
                        <input v-model="newOrg.name" type="text" placeholder="Organization name" :class="inputCls(false)" />
                        <input v-model="newOrg.contact_phone" type="text" placeholder="Contact phone (optional)" :class="inputCls(false)" />
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showNewOrg = false" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">Cancel</button>
                            <button type="button" @click="createOrg" :disabled="creatingOrg || !newOrg.name.trim()" class="px-3 py-1.5 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg disabled:opacity-50">
                                {{ creatingOrg ? 'Adding…' : 'Add' }}
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">Organization is the payer; the guest is the occupant.</p>
                </div>

                <!-- Backdated booking -->
                <div>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input v-model="form.backdated" type="checkbox"
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-950 text-gray-900 dark:text-indigo-500 focus:ring-gray-900 dark:focus:ring-indigo-500" />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Backdated booking</span>
                            <span class="block text-[11px] text-gray-400">Allow a check-in date in the past (migrating an old booking, or a walk-in that already started). Recorded in the audit log.</span>
                        </span>
                    </label>
                </div>

                <button @click="showOptions = false" class="w-full py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-all">Done</button>
            </div>
        </Modal>
    </ManageLayout>
</template>
