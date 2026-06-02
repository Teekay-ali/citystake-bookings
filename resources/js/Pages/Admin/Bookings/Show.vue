<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import DocumentManager from '@/Components/DocumentManager.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import {
    ArrowLeft, LogIn, LogOut, Download, XCircle, Trash2,
    User, Phone, Mail, MessageSquare, PauseCircle,
    Clock, CheckCircle, ChevronRight,
    Building2, Calendar, Shield, Receipt, AlertTriangle,
} from 'lucide-vue-next'

const props = defineProps({
    booking: Object,
    promptPhotoId: { type: Boolean, default: false },
})

const page  = usePage()
const toast = useToast()

const can = (p) => page.props.auth.user?.permissions?.includes(p)

const photoIdPrompt = ref(props.promptPhotoId)

const lateCheckoutHours = ref(null)

// ── Active panel (right sidebar tabs) ──────────────────────────
const activePanel = ref('actions')

// ── Check-in ───────────────────────────────────────────────────
const checkInForm = useForm({
    amount_received:        props.booking.total_amount,
    checkin_payment_method: '',
    checkin_notes:          '',
})
function submitCheckIn() {
    checkInForm.post(route('manage.bookings.check-in', props.booking.id), { preserveScroll: true })
}

// ── Check-out ──────────────────────────────────────────────────
const showCheckOutModal = ref(false)
const isCheckingOut     = ref(false)
function confirmCheckOut() {
    isCheckingOut.value = true
    router.post(route('manage.bookings.check-out', props.booking.id), {}, {
        onFinish: () => { isCheckingOut.value = false; showCheckOutModal.value = false },
    })
}

// ── Cancel ─────────────────────────────────────────────────────
const showCancelModal = ref(false)
const isCancelling    = ref(false)
function cancelBooking() {
    isCancelling.value = true
    router.post(route('manage.bookings.cancel', props.booking.id), {}, {
        onSuccess: () => { showCancelModal.value = false },
        onError:   () => toast.error('Failed to cancel booking.'),
        onFinish:  () => { isCancelling.value = false },
    })
}

// ── Adjustment ─────────────────────────────────────────────────
const showAdjForm = ref(false)
const adjForm = useForm({
    amount_type:       'fixed',
    amount_value:      '',
    reason:            '',
    notes:             '',
    payment_reference: '',
    transaction_date:  new Date().toISOString().split('T')[0],
})

function submitAdjustment() {
    adjForm.post(route('manage.bookings.adjustments.store', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => { showAdjForm.value = false; adjForm.reset() },
    })
}
function deleteAdjustment(adj) {
    if (!confirm('Remove this adjustment? This will also delete the financial transaction record.')) return
    router.delete(route('manage.bookings.adjustments.destroy', [props.booking.id, adj.id]), { preserveScroll: true })
}

// ── Pause & Resume ─────────────────────────────────────────────
const showPauseForm   = ref(false)
const showResumeForm  = ref(false)
const pauseDeparture  = ref('')
const resumeCheckIn   = ref('')
const resumeUnitId    = ref('')
const resumeUnits     = ref([])
const loadingResumeUnits = ref(false)
const isPausing       = ref(false)
const isResuming      = ref(false)

function submitPause() {
    isPausing.value = true
    router.post(route('manage.bookings.pause', props.booking.id), {
        paused_departure: pauseDeparture.value,
    }, {
        onSuccess: () => { showPauseForm.value = false },
        onFinish:  () => { isPausing.value = false },
    })
}

async function loadResumeUnits() {
    if (!resumeCheckIn.value) return
    loadingResumeUnits.value = true
    const checkOut = new Date(resumeCheckIn.value)
    checkOut.setDate(checkOut.getDate() + props.booking.remaining_nights)
    const checkOutStr = checkOut.toISOString().split('T')[0]
    try {
        const res = await fetch(
            route('manage.bookings.available-units') +
            `?unit_type_id=${props.booking.unit_type_id}&check_in=${resumeCheckIn.value}&check_out=${checkOutStr}&exclude_booking=${props.booking.id}`
        )
        resumeUnits.value = await res.json()
    } catch {
        resumeUnits.value = []
    } finally {
        loadingResumeUnits.value = false
    }
}

function submitResume() {
    isResuming.value = true
    router.post(route('manage.bookings.resume', props.booking.id), {
        resume_check_in: resumeCheckIn.value,
        unit_id:         resumeUnitId.value || null,
    }, {
        onSuccess: () => { showResumeForm.value = false },
        onFinish:  () => { isResuming.value = false },
    })
}

// ── Late checkout ──────────────────────────────────────────────
const lateCheckoutForm = useForm({ action: '' })

function approveLateCheckout(action) {
    router.post(route('manage.bookings.late-checkout.approve', props.booking.id), {
        action,
        hours: action === 'approved' ? lateCheckoutHours.value : null,
    }, { preserveScroll: true })
}

function settleLateCheckout() {
    router.post(route('manage.bookings.late-checkout.settle', props.booking.id), {}, { preserveScroll: true })
}
function requestLateCheckout() {
    router.post(route('manage.bookings.late-checkout.request', props.booking.id), {}, { preserveScroll: true })
}

// ── Caution fee ────────────────────────────────────────────────
const isRefunding            = ref(false)
const showCautionForm        = ref(false)
const cautionAction          = ref('full_refund')
const cautionDeductionAmount = ref(null)
const cautionDeductionReason = ref('')

// Receptionist submits request
function submitCautionRequest() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.request', props.booking.id), {
        action:           cautionAction.value,
        reason:           cautionDeductionReason.value,
        deduction_amount: cautionDeductionAmount.value,
    }, {
        onSuccess: () => { showCautionForm.value = false },
        onFinish:  () => { isRefunding.value = false },
    })
}

// Manager approves pending request (no extra data needed — uses stored values)
function approveCautionRequest() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.refund', props.booking.id), {}, {
        onFinish: () => { isRefunding.value = false },
    })
}

// Manager processes directly (no prior request)
function submitCautionDirect() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.refund', props.booking.id), {
        action:           cautionAction.value,
        reason:           cautionDeductionReason.value,
        deduction_amount: cautionDeductionAmount.value,
    }, {
        onSuccess: () => { showCautionForm.value = false },
        onFinish:  () => { isRefunding.value = false },
    })
}

// ── Modify booking ─────────────────────────────────────────────
const showModifyForm  = ref(false)
const modifyForm      = useForm({
    check_in:         props.booking.check_in,
    check_out:        props.booking.check_out,
    nights:           props.booking.nights,
    unit_id:          props.booking.unit_id,
    guests:           props.booking.guests,
    guest_name:       props.booking.guest_name,
    guest_email:      props.booking.guest_email,
    guest_phone:      props.booking.guest_phone,
    special_requests: props.booking.special_requests ?? '',
})

const modifyUnits        = ref([])
const loadingModifyUnits = ref(false)

watch([() => modifyForm.check_in, () => modifyForm.check_out], async ([checkIn, checkOut]) => {
    if (!checkIn || !checkOut) return
    modifyForm.unit_id = ''
    modifyUnits.value  = []
    loadingModifyUnits.value = true
    try {
        const res = await fetch(
            route('manage.bookings.available-units') +
            `?unit_type_id=${props.booking.unit_type_id}&check_in=${checkIn}&check_out=${checkOut}&exclude_booking=${props.booking.id}`
        )
        modifyUnits.value = await res.json()
    } catch {
        modifyUnits.value = []
    } finally {
        loadingModifyUnits.value = false
    }
})

// Sync nights when dates change
watch([() => modifyForm.check_in, () => modifyForm.check_out], ([ci, co]) => {
    if (ci && co) {
        const diff = Math.ceil((new Date(co) - new Date(ci)) / (1000 * 60 * 60 * 24))
        if (diff > 0) modifyForm.nights = diff
    }
})

watch(() => modifyForm.nights, (n) => {
    if (modifyForm.check_in && n > 0) {
        const d = new Date(modifyForm.check_in)
        d.setDate(d.getDate() + parseInt(n))
        modifyForm.check_out = d.toISOString().split('T')[0]
    }
})

function submitModify() {
    modifyForm.post(route('manage.bookings.modify', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => { showModifyForm.value = false },
    })
}


// ── Helpers ────────────────────────────────────────────────────
const fmt = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const fmtDate = (d) => d
    ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
    : '—'

const fmtDateTime = (d) => d
    ? new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : '—'

const statusConfig = computed(() => {
    const s = props.booking.display_status ?? props.booking.status
    const map = {
        confirmed:        { label: 'Confirmed',        cls: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' },
        checked_in:       { label: 'Checked In',       cls: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800' },
        completed:        { label: 'Completed',        cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700' },
        cancelled:        { label: 'Cancelled',        cls: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800' },
        paused:           { label: 'Paused',           cls: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800' },
        payment_pending:  { label: 'Awaiting Payment', cls: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' },
        active:           { label: 'Active Stay',      cls: 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 border-violet-200 dark:border-violet-800' },
        overdue_checkout: { label: 'Overdue Checkout', cls: 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800' },
    }
    return map[s] ?? map['confirmed']
})

const inputCls = (hasError = false) => [
    'w-full px-3 py-2 bg-white dark:bg-gray-950 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
    hasError
        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white',
]
</script>

<template>
    <ManageLayout>
        <Head :title="`Booking · ${booking.booking_reference}`" />

        <div class="flex flex-col h-full overflow-hidden">

            <!-- ── Top bar ── -->
            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shrink-0">
                <div class="flex items-center gap-3">
                    <Link :href="route('manage.bookings.index')"
                          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ booking.booking_reference }}</h1>
                            <span :class="['text-xs font-medium px-2 py-0.5 rounded-full border', statusConfig.cls]">
                                {{ statusConfig.label }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ booking.guest_name }} · {{ fmtDate(booking.check_in) }} → {{ fmtDate(booking.check_out) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('bookings.invoice', booking.id)"
                          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        <Download class="w-3.5 h-3.5" /> Invoice
                    </Link>
                    <Link :href="route('manage.messages.index', { booking: booking.booking_reference })"
                          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        <MessageSquare class="w-3.5 h-3.5" />
                        Messages
                        <span v-if="booking.unreadMessageCount > 0"
                              class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
                            {{ booking.unreadMessageCount }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- ── Body ── -->
            <div class="flex flex-1 overflow-hidden">

                <!-- ── Left: scrollable details ── -->
                <div class="flex-1 overflow-y-auto p-6 space-y-5">

                    <!-- Guest + Property row -->
                    <div class="grid grid-cols-2 gap-4">

                        <!-- Guest -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <User class="w-3.5 h-3.5" /> Guest
                            </p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ booking.guest_name }}</p>
                            <a :href="`mailto:${booking.guest_email}`"
                               class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-1 transition-colors">
                                <Mail class="w-3 h-3" /> {{ booking.guest_email }}
                            </a>
                            <a :href="`tel:${booking.guest_phone}`"
                               class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                <Phone class="w-3 h-3" /> {{ booking.guest_phone }}
                            </a>
                            <div v-if="booking.user" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-900">
                                <p class="text-xs text-gray-400">Platform account: <span class="text-gray-700 dark:text-gray-300">{{ booking.user.email }}</span></p>
                            </div>
                        </div>

                        <!-- Property -->
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <Building2 class="w-3.5 h-3.5" /> Property
                            </p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ booking.unit_type?.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ booking.building?.name }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-2">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">Unit</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ booking.unit?.unit_number }}</p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-2">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-0.5">Floor</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ booking.unit?.floor ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photo ID -->
                    <div :class="['border rounded-xl p-4 transition-all', photoIdPrompt ? 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/10' : 'border-gray-200 dark:border-gray-800']">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Shield class="w-3.5 h-3.5" /> Guest Photo ID
                        </p>
                        <div v-if="photoIdPrompt" class="flex items-start gap-2 mb-3 p-2.5 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                            <AlertTriangle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <p class="text-xs text-amber-700 dark:text-amber-400">Please upload the guest's photo ID before they check in.</p>
                        </div>
                        <DocumentManager
                            model-type="booking"
                            :model-id="booking.id"
                            :initial="booking.documents ?? []"
                            :readonly="!can('confirm-checkin')"
                            @updated="photoIdPrompt = false"
                        />
                    </div>

                    <!-- Stay -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Calendar class="w-3.5 h-3.5" /> Stay Details
                        </p>
                        <div class="grid grid-cols-4 gap-3">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Check-in</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ fmtDate(booking.check_in) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Check-out</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ fmtDate(booking.check_out) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Nights</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ booking.nights }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Guests</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ booking.guests }}</p>
                            </div>
                        </div>
                        <div v-if="booking.special_requests" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-900">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Special Requests</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ booking.special_requests }}</p>
                        </div>
                    </div>

                    <!-- Financials -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Receipt class="w-3.5 h-3.5" /> Financials
                        </p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">{{ fmt(booking.subtotal / booking.nights) }} × {{ booking.nights }} nights</span>
                                <span class="text-gray-900 dark:text-white">{{ fmt(booking.subtotal) }}</span>
                            </div>
                            <div v-if="booking.discount_amount > 0" class="flex justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                <span>Discount ({{ booking.discount_percent }}% off)</span>
                                <span>−{{ fmt(booking.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    Caution fee
                                    <span v-if="booking.caution_fee_refunded" class="px-1 py-0.5 text-[10px] rounded bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Refunded</span>
                                    <span v-else class="px-1 py-0.5 text-[10px] rounded bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">Refundable</span>
                                </span>
                                <span class="text-gray-900 dark:text-white">{{ fmt(booking.caution_fee) }}</span>
                            </div>
                            <div v-if="booking.late_checkout_fee > 0" class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Late checkout fee</span>
                                <span class="text-gray-900 dark:text-white">{{ fmt(booking.late_checkout_fee) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-100 dark:border-gray-900">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-gray-900 dark:text-white">{{ fmt(booking.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs pt-1">
                                <span class="text-gray-400">Payment</span>
                                <span :class="booking.payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'" class="font-medium capitalize">
                                    {{ booking.payment_status === 'paid' ? `Paid · ${booking.payment_method?.replace('_', ' ')}` : 'Pending' }}
                                </span>
                            </div>
                            <div v-if="booking.paystack_reference || booking.payment_reference" class="flex justify-between text-xs">
                                <span class="text-gray-400">Reference</span>
                                <span class="text-gray-600 dark:text-gray-400 font-mono text-[11px]">{{ booking.paystack_reference || booking.payment_reference }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustments -->
                    <div v-if="booking.adjustments?.length || can('manage-bookings')" class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                <AlertTriangle class="w-3.5 h-3.5" /> Adjustments
                            </p>
                            <button v-if="can('manage-bookings') && !showAdjForm"
                                    @click="showAdjForm = true"
                                    class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                                + Add
                            </button>
                        </div>

                        <div v-if="booking.adjustments?.length" class="space-y-2 mb-3">
                            <div v-for="adj in booking.adjustments" :key="adj.id"
                                 class="flex items-start justify-between text-xs p-2 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ adj.reason }}</p>
                                    <p class="text-gray-400 mt-0.5">{{ fmtDate(adj.transaction_date) }}{{ adj.notes ? ` · ${adj.notes}` : '' }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ml-3">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ fmt(adj.amount_naira) }}</span>
                                    <button v-if="can('manage-bookings')" @click="deleteAdjustment(adj)"
                                            class="text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!showAdjForm" class="text-xs text-gray-400 mb-3">No adjustments applied.</p>

                        <!-- Add form -->
                        <form v-if="showAdjForm" @submit.prevent="submitAdjustment" class="space-y-3 pt-3 border-t border-gray-100 dark:border-gray-900">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                                    <select v-model="adjForm.amount_type" :class="inputCls">
                                        <option value="fixed">Fixed (₦)</option>
                                        <option value="percent">Percent (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                    <input v-model="adjForm.amount_value" type="number" step="0.01" min="0" :class="inputCls" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Reason *</label>
                                <input v-model="adjForm.reason" type="text" :class="inputCls" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                                    <input v-model="adjForm.transaction_date" type="date" :class="inputCls" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Reference</label>
                                    <input v-model="adjForm.payment_reference" type="text" :class="inputCls" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Notes</label>
                                <input v-model="adjForm.notes" type="text" :class="inputCls" />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="adjForm.processing"
                                        class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                    Apply
                                </button>
                                <button type="button" @click="showAdjForm = false"
                                        class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Timeline -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5" /> Timeline
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-gray-400 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Booking Created</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.paid_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Payment Confirmed</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.paid_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.checked_in_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Checked In</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.checked_in_at) }} · {{ booking.checked_in_by_name }}</p>
                                </div>
                            </div>
                            <div v-if="booking.checked_out_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-gray-600 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Checked Out</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.checked_out_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.paused_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Booking Paused</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.paused_at) }} · {{ booking.remaining_nights }} nights remaining</p>
                                </div>
                            </div>
                            <div v-if="booking.resumed_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-violet-400 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Booking Resumed</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.resumed_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.cancelled_at" class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-red-500 mt-1.5 shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Cancelled</p>
                                    <p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.cancelled_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-4" />
                </div>

                <!-- ── Right: actions sidebar ── -->
                <div class="w-72 shrink-0 border-l border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 overflow-y-auto flex flex-col">

                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Actions</p>
                    </div>

                    <div class="p-4 space-y-4 flex-1">

                        <!-- Check-in panel -->
                        <div v-if="booking.status === 'confirmed' && booking.payment_status === 'paid' && can('confirm-checkin')"
                             class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white dark:bg-gray-900">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                                <LogIn class="w-3.5 h-3.5 text-emerald-500" /> Check In Guest
                            </p>
                            <form @submit.prevent="submitCheckIn" class="space-y-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Amount Received (₦)</label>
                                    <input v-model="checkInForm.amount_received" type="number" step="0.01" :class="inputCls" required />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Payment Method</label>
                                    <select v-model="checkInForm.checkin_payment_method" :class="inputCls" required>
                                        <option value="">Select method</option>
                                        <option value="pos">POS</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="paystack">Paystack</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                                    <textarea v-model="checkInForm.checkin_notes" rows="2" :class="inputCls + ' resize-none'" />
                                </div>
                                <button type="submit" :disabled="checkInForm.processing"
                                        class="w-full py-2.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg disabled:opacity-50 transition-all flex items-center justify-center gap-1.5">
                                    <LogIn class="w-3.5 h-3.5" /> Confirm Check-in
                                </button>
                            </form>
                        </div>

                        <!-- Checked-in state -->
                        <div v-if="booking.status === 'checked_in'"
                             class="border border-blue-200 dark:border-blue-800 rounded-xl p-4 bg-blue-50 dark:bg-blue-900/20">
                            <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-2 flex items-center gap-1.5">
                                <CheckCircle class="w-3.5 h-3.5" /> Currently Checked In
                            </p>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Since {{ fmtDateTime(booking.checked_in_at) }}</p>
                            <p v-if="booking.checked_in_by_name" class="text-xs text-blue-500 dark:text-blue-500 mb-3">By {{ booking.checked_in_by_name }}</p>
                            <button v-if="can('confirm-checkin')"
                                    @click="showCheckOutModal = true"
                                    class="w-full py-2.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all flex items-center justify-center gap-1.5">
                                <LogOut class="w-3.5 h-3.5" /> Check Out Guest
                            </button>

                            <!-- Pause booking -->
                            <div v-if="booking.status === 'checked_in' && can('confirm-checkin')"
                                 class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white dark:bg-gray-900 mt-3">
                                <button v-if="!showPauseForm"
                                        @click="showPauseForm = true"
                                        class="w-full py-2 text-xs border border-violet-200 dark:border-violet-800 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 text-violet-600 dark:text-violet-400 transition-all flex items-center justify-center gap-1.5">
                                    <PauseCircle class="w-3.5 h-3.5" /> Pause Booking
                                </button>
                                <form v-else @submit.prevent="submitPause" class="space-y-3">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Guest departing early</p>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Actual Departure Date</label>
                                        <input v-model="pauseDeparture" type="date"
                                               :min="booking.check_in"
                                               :max="booking.check_out"
                                               :class="inputCls(false)" required />
                                        <p v-if="pauseDeparture" class="mt-1 text-[11px] text-gray-400">
                                            {{ Math.ceil((new Date(booking.check_out) - new Date(pauseDeparture)) / (1000*60*60*24)) }} night(s) will be held for later use
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" :disabled="isPausing || !pauseDeparture"
                                                class="flex-1 py-2 text-xs font-medium bg-violet-600 hover:bg-violet-700 text-white rounded-lg disabled:opacity-50 transition-all">
                                            Confirm Pause
                                        </button>
                                        <button type="button" @click="showPauseForm = false"
                                                class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>

                        <!-- Resume booking -->
                        <div v-if="booking.status === 'paused'"
                             class="border border-violet-200 dark:border-violet-800 rounded-xl p-4 bg-violet-50 dark:bg-violet-900/20">
                            <p class="text-xs font-semibold text-violet-700 dark:text-violet-400 mb-2 flex items-center gap-1.5">
                                <PauseCircle class="w-3.5 h-3.5" /> Booking Paused
                            </p>
                            <p class="text-xs text-violet-600 dark:text-violet-400 mb-1">
                                {{ booking.remaining_nights }} night{{ booking.remaining_nights !== 1 ? 's' : '' }} remaining
                            </p>
                            <p class="text-xs text-violet-500 dark:text-violet-500 mb-3">
                                Departed: {{ fmtDate(booking.paused_departure) }}
                            </p>

                            <div v-if="!showResumeForm">
                                <button @click="showResumeForm = true"
                                        class="w-full py-2.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-all">
                                    Resume Booking
                                </button>
                            </div>

                            <form v-else @submit.prevent="submitResume" class="space-y-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">New Check-in Date</label>
                                    <input v-model="resumeCheckIn" type="date"
                                           :min="new Date().toISOString().split('T')[0]"
                                           :class="inputCls(false)"
                                           @change="loadResumeUnits"
                                           required />
                                    <p v-if="resumeCheckIn" class="mt-1 text-[11px] text-gray-400">
                                        Check-out: {{ (() => { const d = new Date(resumeCheckIn); d.setDate(d.getDate() + booking.remaining_nights); return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) })() }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Unit</label>
                                    <div v-if="loadingResumeUnits" class="flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg">
                                        <div class="w-3 h-3 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                                        <span class="text-xs text-gray-400">Checking…</span>
                                    </div>
                                    <select v-else v-model="resumeUnitId" :class="inputCls(false)">
                                        <option value="">Auto-assign available unit</option>
                                        <option v-for="u in resumeUnits" :key="u.id" :value="u.id">{{ u.label }}</option>
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" :disabled="isResuming || !resumeCheckIn"
                                            class="flex-1 py-2 text-xs font-medium bg-violet-600 hover:bg-violet-700 text-white rounded-lg disabled:opacity-50 transition-all">
                                        Confirm Resume
                                    </button>
                                    <button type="button" @click="showResumeForm = false"
                                            class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Late checkout -->
                        <div v-if="['confirmed','checked_in'].includes(booking.status)"
                             class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white dark:bg-gray-900">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-amber-500" /> Late Checkout
                            </p>
                            <div v-if="!booking.late_checkout_requested">
                                <button @click="requestLateCheckout"
                                        class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all">
                                    Request Late Checkout
                                </button>
                            </div>
                            <div v-else class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Status</span>
                                    <span class="font-medium capitalize text-gray-900 dark:text-white">{{ booking.late_checkout_status }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Fee</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ fmt(booking.late_checkout_fee) }}</span>
                                </div>
                                <div v-if="booking.late_checkout_status === 'pending' && can('approve-late-checkout')" class="space-y-2 pt-1">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Hours past checkout</label>
                                        <input v-model.number="lateCheckoutHours" type="number" min="1" max="24" placeholder="e.g. 2"
                                               class="w-full px-3 py-1.5 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                        <p v-if="lateCheckoutHours" class="mt-1 text-[11px] text-gray-400">
                                            Fee: {{ fmt(lateCheckoutHours * (booking.building?.late_checkout_fee_per_hour ?? 10000)) }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="approveLateCheckout('approved')"
                                                :disabled="!lateCheckoutHours"
                                                class="flex-1 py-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-lg transition-all">Approve</button>
                                        <button @click="approveLateCheckout('rejected')"
                                                class="flex-1 py-1.5 text-xs border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">Reject</button>
                                    </div>
                                </div>
                                <button v-if="booking.late_checkout_status === 'approved' && can('confirm-checkin')"
                                        @click="settleLateCheckout"
                                        class="w-full py-1.5 text-xs bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-all">
                                    Mark Fee as Settled
                                </button>
                            </div>
                        </div>

                        <!-- Caution fee -->
                        <div v-if="booking.caution_fee > 0 && !booking.caution_fee_refunded"
                             class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white dark:bg-gray-900">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-1.5">
                                <Shield class="w-3.5 h-3.5 text-amber-500" />
                                Caution Fee · {{ fmt(booking.caution_fee) }}
                            </p>

                            <!-- ── PENDING REQUEST: receptionist waiting ── -->
                            <div v-if="booking.caution_refund_requested && !can('manage-bookings')"
                                 class="px-3 py-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <p class="text-xs font-medium text-blue-700 dark:text-blue-400">Refund request submitted</p>
                                <p class="text-[11px] text-blue-500 dark:text-blue-400 mt-0.5">Awaiting manager approval</p>
                            </div>

                            <!-- ── PENDING REQUEST: manager approves ── -->
                            <div v-else-if="booking.caution_refund_requested && can('manage-bookings')" class="space-y-3">
                                <div class="px-3 py-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 space-y-1 text-xs">
                                    <p class="font-semibold text-blue-700 dark:text-blue-400">Refund Request Pending</p>
                                    <p class="text-blue-600 dark:text-blue-400">
                                        Action: <span class="font-medium capitalize">{{ booking.caution_refund_action?.replace(/_/g, ' ') }}</span>
                                    </p>
                                    <p v-if="booking.caution_refund_deduction_amount" class="text-blue-600 dark:text-blue-400">
                                        Deduction: <span class="font-medium">{{ fmt(booking.caution_refund_deduction_amount) }}</span>
                                    </p>
                                    <p v-if="booking.caution_refund_reason" class="text-blue-600 dark:text-blue-400">
                                        Reason: <span class="font-medium">{{ booking.caution_refund_reason }}</span>
                                    </p>
                                </div>
                                <button @click="approveCautionRequest" :disabled="isRefunding"
                                        class="w-full py-2.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                    Approve & Process
                                </button>
                            </div>

                            <!-- ── NO PENDING REQUEST: receptionist raises one ── -->
                            <template v-else-if="booking.status === 'completed' && can('confirm-checkin') && !can('manage-bookings')">
                                <div v-if="!showCautionForm" class="space-y-2">
                                    <button @click="cautionAction = 'full_refund'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all">
                                        Full Refund
                                    </button>
                                    <button @click="cautionAction = 'partial_deduction'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 text-amber-600 dark:text-amber-400 transition-all">
                                        Partial Deduction
                                    </button>
                                    <button @click="cautionAction = 'full_forfeit'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-all">
                                        Full Forfeit
                                    </button>
                                </div>
                                <form v-else @submit.prevent="submitCautionRequest" class="space-y-3">
                                    <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ cautionAction === 'full_refund' ? 'Full Refund' : cautionAction === 'partial_deduction' ? 'Partial Deduction' : 'Full Forfeit' }}
                                        </p>
                                    </div>
                                    <div v-if="cautionAction === 'partial_deduction'">
                                        <label class="block text-xs text-gray-500 mb-1">Deduction Amount (₦)</label>
                                        <input v-model.number="cautionDeductionAmount" type="number" min="1" :max="booking.caution_fee - 1"
                                               class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                        <p v-if="cautionDeductionAmount" class="mt-1 text-[11px] text-gray-400">
                                            Refund: {{ fmt(booking.caution_fee - cautionDeductionAmount) }}
                                        </p>
                                    </div>
                                    <div v-if="cautionAction !== 'full_refund'">
                                        <label class="block text-xs text-gray-500 mb-1">Reason <span class="text-red-500">*</span></label>
                                        <textarea v-model="cautionDeductionReason" rows="2"
                                                  class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" :disabled="isRefunding"
                                                class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                            Submit Request
                                        </button>
                                        <button type="button" @click="showCautionForm = false"
                                                class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                            Back
                                        </button>
                                    </div>
                                </form>
                            </template>

                            <!-- ── NO PENDING REQUEST: manager processes directly ── -->
                            <template v-else-if="booking.status === 'completed' && can('manage-bookings')">
                                <div v-if="!showCautionForm" class="space-y-2">
                                    <button @click="cautionAction = 'full_refund'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all">
                                        Full Refund
                                    </button>
                                    <button @click="cautionAction = 'partial_deduction'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 text-amber-600 dark:text-amber-400 transition-all">
                                        Partial Deduction
                                    </button>
                                    <button @click="cautionAction = 'full_forfeit'; showCautionForm = true"
                                            class="w-full py-2 text-xs border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-all">
                                        Full Forfeit
                                    </button>
                                </div>
                                <form v-else @submit.prevent="submitCautionDirect" class="space-y-3">
                                    <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ cautionAction === 'full_refund' ? 'Full Refund' : cautionAction === 'partial_deduction' ? 'Partial Deduction' : 'Full Forfeit' }}
                                        </p>
                                    </div>
                                    <div v-if="cautionAction === 'partial_deduction'">
                                        <label class="block text-xs text-gray-500 mb-1">Deduction Amount (₦)</label>
                                        <input v-model.number="cautionDeductionAmount" type="number" min="1" :max="booking.caution_fee - 1"
                                               class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                        <p v-if="cautionDeductionAmount" class="mt-1 text-[11px] text-gray-400">
                                            Refund: {{ fmt(booking.caution_fee - cautionDeductionAmount) }}
                                        </p>
                                    </div>
                                    <div v-if="cautionAction !== 'full_refund'">
                                        <label class="block text-xs text-gray-500 mb-1">Reason <span class="text-red-500">*</span></label>
                                        <textarea v-model="cautionDeductionReason" rows="2"
                                                  class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" :disabled="isRefunding"
                                                class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                            Process
                                        </button>
                                        <button type="button" @click="showCautionForm = false"
                                                class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                            Back
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>

                        <!-- Already processed -->
                        <div v-else-if="booking.caution_fee_refunded"
                             class="px-3 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                            <p class="text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                                <CheckCircle class="w-3.5 h-3.5" />
                                Caution fee processed
                                <span v-if="booking.caution_fee_deduction > 0" class="text-amber-600 dark:text-amber-400 ml-1">
                                    · {{ fmt(booking.caution_fee_deduction) }} deducted
                                </span>
                            </p>
                        </div>

                        <!-- Modify booking -->
                        <div v-if="!['cancelled','completed'].includes(booking.status) && can('manage-bookings')"
                             class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900">
                            <button @click="showModifyForm = !showModifyForm"
                                    class="w-full flex items-center justify-between px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-all">
        <span class="flex items-center gap-1.5">
            <ChevronRight class="w-3.5 h-3.5" />
            Modify Booking
        </span>
                                <span :class="showModifyForm ? 'rotate-90' : ''" class="transition-transform">
            <ChevronRight class="w-3 h-3 text-gray-400" />
        </span>
                            </button>

                            <form v-if="showModifyForm" @submit.prevent="submitModify"
                                  class="px-4 pb-4 space-y-3 border-t border-gray-100 dark:border-gray-900 pt-3">

                                <!-- Dates -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Check-in</label>
                                        <input v-model="modifyForm.check_in" type="date" :class="inputCls(false)" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Check-out</label>
                                        <input v-model="modifyForm.check_out" type="date" :class="inputCls(false)" />
                                    </div>
                                </div>

                                <!-- Nights -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Nights</label>
                                    <input v-model.number="modifyForm.nights" type="number" min="1" :class="inputCls(false)" />
                                    <p class="mt-1 text-[11px] text-gray-400">Change nights to auto-update checkout</p>
                                </div>

                                <!-- Unit -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Unit</label>
                                    <div v-if="loadingModifyUnits" class="flex items-center gap-2 px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg">
                                        <div class="w-3 h-3 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                                        <span class="text-xs text-gray-400">Checking…</span>
                                    </div>
                                    <select v-else v-model="modifyForm.unit_id" :class="inputCls(false)">
                                        <option :value="booking.unit_id">Unit {{ booking.unit?.unit_number }} (current)</option>
                                        <option v-for="u in modifyUnits" :key="u.id" :value="u.id">{{ u.label }}</option>
                                    </select>
                                </div>

                                <!-- Guests -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Guests</label>
                                    <input v-model.number="modifyForm.guests" type="number" min="1" :class="inputCls(false)" />
                                </div>

                                <!-- Guest details -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Guest Name</label>
                                    <input v-model="modifyForm.guest_name" type="text" :class="inputCls(false)" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Phone</label>
                                    <input v-model="modifyForm.guest_phone" type="tel" :class="inputCls(false)" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Email</label>
                                    <input v-model="modifyForm.guest_email" type="email" :class="inputCls(false)" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Special Requests</label>
                                    <textarea v-model="modifyForm.special_requests" rows="2"
                                              class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                                </div>

                                <div v-if="Object.keys(modifyForm.errors).length" class="text-xs text-red-500">
                                    {{ Object.values(modifyForm.errors)[0] }}
                                </div>

                                <div class="flex gap-2 pt-1">
                                    <button type="submit" :disabled="modifyForm.processing"
                                            class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                                        Save Changes
                                    </button>
                                    <button type="button" @click="showModifyForm = false"
                                            class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Cancel -->
                        <div v-if="booking.status !== 'cancelled' && booking.status !== 'completed' && booking.status !== 'checked_in' && can('manage-bookings')"
                             class="border border-red-100 dark:border-red-900/50 rounded-xl p-4 bg-white dark:bg-gray-900">
                            <button @click="showCancelModal = true"
                                    class="w-full py-2 text-xs text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex items-center justify-center gap-1.5">
                                <XCircle class="w-3.5 h-3.5" /> Cancel Booking
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Modals -->
        <ConfirmationModal
            :show="showCheckOutModal"
            :processing="isCheckingOut"
            title="Confirm Guest Checkout?"
            message="This will mark the guest as checked out and complete the booking. This action cannot be undone."
            confirm-text="Yes, Check Out"
            cancel-text="Not Yet"
            variant="default"
            @confirm="confirmCheckOut"
            @close="showCheckOutModal = false"
        />
        <ConfirmationModal
            :show="showCancelModal"
            :processing="isCancelling"
            title="Cancel This Booking?"
            message="Are you sure you want to cancel this booking? This will free up the unit and notify the guest. This action cannot be undone."
            confirm-text="Yes, Cancel Booking"
            cancel-text="Keep Booking"
            variant="danger"
            @confirm="cancelBooking"
            @close="showCancelModal = false"
        />
    </ManageLayout>
</template>
