<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from 'vue-toastification';
import {
    ArrowLeft, Calendar, Users, Mail, Phone,
    CreditCard, User, MessageSquare, Building2,
    Home, Receipt, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    buildings: Array,
});

const toast = useToast();

const form = useForm({
    building_id:       '',
    unit_type_id:      '',
    check_in:          '',
    nights:            '',
    check_out:         '',
    guests:            1,
    guest_name:        '',
    guest_email:       '',
    guest_phone:       '',
    special_requests:  '',
    payment_method:    'pos',
    payment_reference: '',
});

const selectedBuilding = computed(() =>
    props.buildings.find(b => b.id == form.building_id)
);

const availableUnitTypes = computed(() =>
    selectedBuilding.value?.unit_types || []
);

const selectedUnitType = computed(() =>
    availableUnitTypes.value.find(ut => ut.id == form.unit_type_id)
);

const calculateNights = computed(() => {
    if (!form.check_in || !form.check_out) return 0;
    const diff = Math.ceil(
        (new Date(form.check_out) - new Date(form.check_in)) / (1000 * 60 * 60 * 24)
    );
    return diff > 0 ? diff : 0;
});

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
    form.unit_type_id = '';
});

const pricing = computed(() => {
    if (!selectedUnitType.value || calculateNights.value === 0) {
        return { subtotal: 0, cautionFee: 0, discountAmount: 0, discountPercent: 0, discountType: null, total: 0 };
    }

    const nights     = calculateNights.value;
    const price      = parseFloat(selectedUnitType.value.base_price_per_night) || 0;
    const subtotal   = price * nights;
    const cautionFee = nights === 1
        ? price
        : parseFloat(selectedBuilding.value?.caution_fee_amount ?? 70000);

    let discountPercent = 0;
    let discountType    = null;
    if (nights >= 7) { discountPercent = 5; discountType = 'long_stay'; }

    const discountAmount = discountPercent > 0 ? Math.round(subtotal * (discountPercent / 100)) : 0;
    const total          = (subtotal - discountAmount) + cautionFee;

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

        <!-- ── Page shell: fixed height, two-column ── -->
        <div class="flex flex-col h-full overflow-hidden">
            <!-- Top bar -->
            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shrink-0">
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

            <!-- Body: left form + right summary -->
            <div class="flex flex-1 overflow-hidden">

                <!-- ── Left: scrollable form ── -->
                <div class="flex-1 overflow-y-auto p-6 space-y-5">

                    <!-- Property & Unit Type -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Building2 class="w-3.5 h-3.5" /> Property
                        </p>
                        <div class="grid grid-cols-2 gap-4">
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
                                        {{ ut.name }} — {{ formatPrice(ut.base_price_per_night) }}/night
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
                        <div class="grid grid-cols-2 gap-4">
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
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="col-span-2">
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
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <!-- POS -->
                            <label
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
                                    form.payment_method === 'pos'
                                        ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'
                                ]">
                                <input v-model="form.payment_method" type="radio" value="pos" class="sr-only" />
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
                            </label>

                            <!-- Bank Transfer -->
                            <label
                                :class="[
                                    'flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all',
                                    form.payment_method === 'bank_transfer'
                                        ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'
                                ]">
                                <input v-model="form.payment_method" type="radio" value="bank_transfer" class="sr-only" />
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
                            </label>
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

                <!-- ── Right: sticky summary ── -->
                <div class="w-72 shrink-0 border-l border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex flex-col overflow-y-auto">

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
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Property</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedUnitType.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedBuilding.name }}</p>
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
                            <span class="text-gray-500 dark:text-gray-400">Duration</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ calculateNights }} night{{ calculateNights > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Guests</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ form.guests }}</span>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-800" />

                        <!-- Pricing -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ formatPrice(selectedUnitType.base_price_per_night) }} × {{ calculateNights }}
                                </span>
                                <span class="text-gray-900 dark:text-white">{{ formatPrice(pricing.subtotal) }}</span>
                            </div>
                            <div v-if="pricing.discountAmount > 0" class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                <span>Long stay ({{ pricing.discountPercent }}% off)</span>
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

                        <div class="border-t border-gray-200 dark:border-gray-800" />

                        <!-- Total -->
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Total</span>
                            <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ formatPrice(pricing.total) }}</span>
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
    </ManageLayout>
</template>
