<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import DocumentManager from '@/Components/DocumentManager.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import CautionChargesModal from './Partials/CautionChargesModal.vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { useAppToast } from '@/Composables/useAppToast'
import {
    ArrowLeft, LogIn, LogOut, Download, XCircle, Trash2,
    User, Phone, Mail, MessageSquare, PauseCircle,
    Clock, CheckCircle, ChevronRight,
    Building2, Calendar, Shield, Receipt, AlertTriangle, Flag, Briefcase, Layers, ArrowRightLeft,
} from 'lucide-vue-next'

const props = defineProps({
    booking: Object,
    promptPhotoId: { type: Boolean, default: false },
})

const page  = usePage()
const toast = useAppToast()

const can = (p) => page.props.auth.user?.permissions?.includes(p)

const photoIdPrompt = ref(props.promptPhotoId)

const lateCheckoutHours = ref(null)

// ── Check-in ───────────────────────────────────────────────────
const checkInForm = useForm({
    // Payment is settled before check-in - check-in is arrival confirmation only.
    checkin_notes: '',
})
function submitCheckIn() {
    checkInForm.post(route('manage.bookings.check-in', props.booking.booking_reference), { preserveScroll: true })
}

// ── Check-out ──────────────────────────────────────────────────
const showCheckOutModal = ref(false)
const isCheckingOut     = ref(false)
function confirmCheckOut() {
    isCheckingOut.value = true
    router.post(route('manage.bookings.check-out', props.booking.booking_reference), {}, {
        onFinish: () => { isCheckingOut.value = false; showCheckOutModal.value = false },
    })
}

// ── Cancel ─────────────────────────────────────────────────────
const showCancelModal = ref(false)
const isCancelling    = ref(false)
function cancelBooking() {
    isCancelling.value = true
    router.post(route('manage.bookings.cancel', props.booking.booking_reference), {}, {
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
    adjForm.post(route('manage.bookings.adjustments.store', props.booking.booking_reference), {
        preserveScroll: true,
        onSuccess: () => { showAdjForm.value = false; adjForm.reset() },
    })
}
function deleteAdjustment(adj) {
    if (!confirm('Remove this adjustment? This will also delete the financial transaction record.')) return
    router.delete(route('manage.bookings.adjustments.destroy', [props.booking.booking_reference, adj.id]), { preserveScroll: true })
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
    router.post(route('manage.bookings.pause', props.booking.booking_reference), {
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
    router.post(route('manage.bookings.resume', props.booking.booking_reference), {
        resume_check_in: resumeCheckIn.value,
        unit_id:         resumeUnitId.value || null,
    }, {
        onSuccess: () => { showResumeForm.value = false },
        onFinish:  () => { isResuming.value = false },
    })
}

// ── Late checkout ──────────────────────────────────────────────
const lateCheckoutForm         = useForm({ action: '' })
const isLateCheckoutProcessing = ref(false)

function approveLateCheckout(action) {
    if (isLateCheckoutProcessing.value) return
    isLateCheckoutProcessing.value = true
    router.post(route('manage.bookings.late-checkout.approve', props.booking.booking_reference), {
        action,
        hours: action === 'approved' ? lateCheckoutHours.value : null,
    }, {
        preserveScroll: true,
        onFinish: () => { isLateCheckoutProcessing.value = false },
    })
}

function settleLateCheckout() {
    if (isLateCheckoutProcessing.value) return
    isLateCheckoutProcessing.value = true
    router.post(route('manage.bookings.late-checkout.settle', props.booking.booking_reference), {}, {
        preserveScroll: true,
        onFinish: () => { isLateCheckoutProcessing.value = false },
    })
}

function requestLateCheckout() {
    if (isLateCheckoutProcessing.value) return
    isLateCheckoutProcessing.value = true
    router.post(route('manage.bookings.late-checkout.request', props.booking.booking_reference), {}, {
        preserveScroll: true,
        onFinish: () => { isLateCheckoutProcessing.value = false },
    })
}

// ── Weekly payment plan ────────────────────────────────────────
const payingInstallment = ref(null)
const installmentForm   = useForm({ payment_method: 'bank_transfer', payment_reference: '' })

function installmentStatus(inst) {
    if (inst.paid_at) return 'paid'
    return new Date(inst.due_date) < new Date(new Date().toDateString()) ? 'overdue' : 'upcoming'
}
// The earliest unpaid installment is the one staff can record next.
const nextDueInstallment = computed(() =>
    (props.booking.installments ?? []).find(i => !i.paid_at)
)
// Weekly bookings check in once week 1 is paid (rest is prepaid week-by-week);
// everyone else must be fully paid. Mirrors Booking::canCheckIn().
const paidEnoughToCheckIn = computed(() =>
    props.booking.payment_status === 'paid'
    || (props.booking.payment_plan === 'weekly'
        && !!(props.booking.installments ?? []).find(i => i.week_number === 1)?.paid_at)
)
function submitInstallment(inst) {
    installmentForm.post(route('manage.bookings.installments.pay', [props.booking.booking_reference, inst.id]), {
        preserveScroll: true,
        onSuccess: () => { payingInstallment.value = null; installmentForm.reset() },
    })
}

// ── Caution fee ────────────────────────────────────────────────
const isRefunding            = ref(false)
const showCautionModal       = ref(false)
const showCautionForm        = ref(false)
const cautionAction          = ref('full_refund')
const cautionDeductionAmount = ref(null)
const cautionDeductionReason = ref('')

// Receptionist submits request
function submitCautionRequest() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.request', props.booking.booking_reference), {
        action:           cautionAction.value,
        reason:           cautionDeductionReason.value,
        deduction_amount: cautionDeductionAmount.value,
    }, {
        onSuccess: () => { showCautionForm.value = false },
        onFinish:  () => { isRefunding.value = false },
    })
}

// Manager approves pending request (no extra data needed - uses stored values)
function approveCautionRequest() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.refund', props.booking.booking_reference), {}, {
        onFinish: () => { isRefunding.value = false },
    })
}

// Manager processes directly (no prior request)
function submitCautionDirect() {
    isRefunding.value = true
    router.post(route('manage.bookings.caution-fee.refund', props.booking.booking_reference), {
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
let modifyFetchController = null

watch([() => modifyForm.check_in, () => modifyForm.check_out], async ([checkIn, checkOut]) => {
    if (!checkIn || !checkOut) return
    if (modifyFetchController) modifyFetchController.abort()
    modifyFetchController = new AbortController()
    modifyForm.unit_id = ''
    modifyUnits.value  = []
    loadingModifyUnits.value = true
    try {
        const res = await fetch(
            route('manage.bookings.available-units') +
            `?unit_type_id=${props.booking.unit_type_id}&check_in=${checkIn}&check_out=${checkOut}&exclude_booking=${props.booking.id}`,
            { signal: modifyFetchController.signal }
        )
        modifyUnits.value = await res.json()
    } catch (e) {
        if (e.name !== 'AbortError') modifyUnits.value = []
    } finally {
        loadingModifyUnits.value = false
    }
})

// Keep modifyForm in sync if Inertia refreshes the booking prop
watch(() => props.booking, (booking) => {
    modifyForm.check_in         = booking.check_in
    modifyForm.check_out        = booking.check_out
    modifyForm.nights           = booking.nights
    modifyForm.unit_id          = booking.unit_id
    modifyForm.guests           = booking.guests
    modifyForm.guest_name       = booking.guest_name
    modifyForm.guest_email      = booking.guest_email
    modifyForm.guest_phone      = booking.guest_phone
    modifyForm.special_requests = booking.special_requests ?? ''
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
    modifyForm.post(route('manage.bookings.modify', props.booking.booking_reference), {
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
    : '-'

const fmtDateTime = (d) => d
    ? new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    : '-'

const guestInitials = computed(() =>
    (props.booking.guest_name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
)

// A completed stay is fully wrapped up once the caution fee is settled (refunded/deducted) or never applied.
const cautionSettled = computed(() =>
    props.booking.caution_fee_refunded || Number(props.booking.caution_fee ?? 0) <= 0
)

const stayCompleted = computed(() =>
    (props.booking.display_status ?? props.booking.status) === 'completed' && cautionSettled.value
)

const cautionOutcome = computed(() => {
    const b = props.booking
    if (Number(b.caution_fee ?? 0) <= 0) return 'No caution fee held'
    const deduction = Number(b.caution_fee_deduction ?? 0)
    if (deduction <= 0) return `Caution fully refunded · ${fmt(b.caution_fee)}`
    if (deduction >= Number(b.caution_fee)) return `Caution fully forfeited · ${fmt(deduction)}`
    return `Caution: ${fmt(deduction)} deducted, ${fmt(Number(b.caution_fee) - deduction)} refunded`
})

const statusConfig = computed(() => {
    const s = props.booking.display_status ?? props.booking.status
    if (s === 'completed') {
        return cautionSettled.value
            ? { label: 'Stay completed',  dot: 'bg-emerald-500', cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' }
            : { label: 'Caution pending', dot: 'bg-amber-500',   cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' }
    }
    const map = {
        confirmed:        { label: 'Confirmed',        dot: 'bg-emerald-500', cls: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' },
        checked_in:       { label: 'Checked In',       dot: 'bg-blue-500',    cls: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' },
        completed:        { label: 'Completed',        dot: 'bg-gray-400',    cls: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' },
        cancelled:        { label: 'Cancelled',        dot: 'bg-red-500',     cls: 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400' },
        paused:           { label: 'Paused',           dot: 'bg-violet-500',  cls: 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' },
        payment_pending:  { label: 'Awaiting Payment', dot: 'bg-amber-500',   cls: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' },
        active:           { label: 'Active Stay',      dot: 'bg-violet-500',  cls: 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' },
        overdue_checkout: { label: 'Overdue Checkout', dot: 'bg-orange-500',  cls: 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400' },
    }
    return map[s] ?? map['confirmed']
})

const inputCls = (hasError = false) => [
    'w-full px-3 py-2 bg-white dark:bg-gray-950 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
    hasError
        ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
        : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white',
]

// Shared card + section-label styling
const card = 'bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none'
const sectionLabel = 'text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5'
</script>

<template>
    <ManageLayout>
        <Head :title="`Booking · ${booking.booking_reference}`" />

        <div class="p-4 lg:p-6">

            <!-- ── Header row (sticky) ── -->
            <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-5 flex items-center justify-between gap-3 flex-wrap bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3 min-w-0">
                    <Link :href="route('manage.bookings.index')"
                          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shrink-0">
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-semibold text-gray-900 dark:text-white font-mono truncate">{{ booking.booking_reference }}</h1>
                            <span :class="['inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-md', statusConfig.cls]">
                                <span :class="statusConfig.dot" class="w-1.5 h-1.5 rounded-full" />
                                {{ statusConfig.label }}
                            </span>
                            <Link v-if="booking.booking_group" :href="route('manage.booking-groups.show', booking.booking_group.reference)"
                                  class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                <Layers class="w-3 h-3" /> Group
                            </Link>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ fmtDate(booking.check_in) }} → {{ fmtDate(booking.check_out) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="route('manage.bookings.invoice', booking.booking_reference)" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        <Download class="w-3.5 h-3.5" /> Invoice
                    </a>
                    <Link :href="route('manage.messages.index', { booking: booking.booking_reference })"
                          class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                        <MessageSquare class="w-3.5 h-3.5" /> Messages
                        <span v-if="booking.unreadMessageCount > 0" class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
                            {{ booking.unreadMessageCount }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- ── Two-column body ── -->
            <div class="lg:grid lg:grid-cols-[1fr_20rem] lg:gap-4 lg:items-start">

                <!-- ════ Main details ════ -->
                <div class="space-y-4">

                    <!-- Summary hero -->
                    <div :class="card" class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-gray-900 dark:bg-white flex items-center justify-center shrink-0">
                                    <span class="text-sm font-semibold text-white dark:text-gray-900">{{ guestInitials }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-gray-900 dark:text-white truncate flex items-center gap-2">
                                        {{ booking.guest_name }}
                                        <span v-if="booking.organization" class="inline-flex items-center gap-1 text-[11px] font-medium px-1.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400">
                                            <Briefcase class="w-3 h-3" /> {{ booking.organization.name }}
                                        </span>
                                    </p>
                                    <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                        <a :href="`mailto:${booking.guest_email}`" class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                            <Mail class="w-3 h-3" /> {{ booking.guest_email }}
                                        </a>
                                        <a :href="`tel:${booking.guest_phone}`" class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                            <Phone class="w-3 h-3" /> {{ booking.guest_phone }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ fmt(booking.total_amount) }}</p>
                                <p v-if="booking.currency === 'USD'" class="text-[11px] text-gray-400 dark:text-gray-500 tabular-nums mt-0.5">
                                    ${{ Number(booking.price_usd).toLocaleString() }} @ ₦{{ Number(booking.exchange_rate).toLocaleString() }}/$
                                </p>
                                <p class="text-xs font-medium mt-0.5" :class="booking.payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                    {{ booking.payment_status === 'paid' ? `Paid · ${booking.payment_method?.replace('_', ' ')}` : 'Payment pending' }}
                                </p>
                            </div>
                        </div>

                        <!-- Property line -->
                        <div class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 mt-4 flex-wrap">
                            <Building2 class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                            <span class="font-medium text-gray-900 dark:text-white">{{ booking.unit_type?.name }}</span>
                            <span class="text-gray-400">·</span>
                            <span>{{ booking.building?.name }}</span>
                            <span v-if="booking.unit?.unit_number" class="text-gray-400">·</span>
                            <span v-if="booking.unit?.unit_number">Unit {{ booking.unit.unit_number }}<template v-if="booking.unit?.floor">, Floor {{ booking.unit.floor }}</template></span>
                        </div>

                        <!-- Overflow / cross-grade notice -->
                        <div v-if="booking.cross_graded" class="flex items-center gap-2 mt-3 px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-500/20">
                            <ArrowRightLeft class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <span class="font-semibold">Overflow allocation</span> - billed as
                                <span class="font-medium">{{ booking.unit_type?.name }}</span>, assigned
                                <span class="font-medium">{{ booking.assigned_unit_type }}</span> (Unit {{ booking.unit?.unit_number }})
                            </p>
                        </div>

                        <!-- Stay completed / settled badge -->
                        <div v-if="stayCompleted"
                             class="flex items-center gap-2 mt-4 px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20">
                            <Flag class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Stay completed</p>
                                <p class="text-[11px] text-emerald-600/80 dark:text-emerald-400/70 truncate">
                                    Checked out<template v-if="booking.checked_out_at"> · {{ fmtDate(booking.checked_out_at) }}</template> · {{ cautionOutcome }}
                                </p>
                            </div>
                        </div>

                        <!-- Key facts -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
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
                        <div v-if="booking.special_requests" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Special requests</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ booking.special_requests }}</p>
                        </div>
                        <div v-if="booking.user" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-xs text-gray-400">Platform account · <span class="text-gray-600 dark:text-gray-300">{{ booking.user.email }}</span></p>
                        </div>
                    </div>

                    <!-- Photo ID -->
                    <div :class="[card, photoIdPrompt ? 'ring-1 ring-amber-300 dark:ring-amber-700' : '']" class="p-4">
                        <p :class="sectionLabel" class="mb-3"><Shield class="w-3.5 h-3.5" /> Guest Photo ID</p>
                        <div v-if="photoIdPrompt" class="flex items-start gap-2 mb-3 p-2.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
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

                    <!-- Weekly payment plan -->
                    <div v-if="booking.payment_plan === 'weekly'" :class="card" class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p :class="sectionLabel"><Receipt class="w-3.5 h-3.5" /> Weekly payment plan</p>
                            <span class="text-xs font-medium tabular-nums" :class="booking.balance_due > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ booking.balance_due > 0 ? fmt(booking.balance_due) + ' outstanding' : 'Fully paid' }}
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            <div v-for="inst in booking.installments" :key="inst.id"
                                 class="flex items-center gap-3 rounded-lg px-3 py-2"
                                 :class="installmentStatus(inst) === 'overdue' ? 'bg-red-50/60 dark:bg-red-900/10' : 'bg-gray-50/60 dark:bg-gray-800/30'">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">Week {{ inst.week_number }}</p>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500">Due {{ fmtDate(inst.due_date) }}</p>
                                </div>
                                <span class="text-xs tabular-nums text-gray-900 dark:text-white">{{ fmt(inst.amount) }}</span>
                                <span v-if="installmentStatus(inst) === 'paid'" class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">Paid</span>
                                <span v-else-if="installmentStatus(inst) === 'overdue'" class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400">Overdue</span>
                                <button v-if="!inst.paid_at && nextDueInstallment && inst.id === nextDueInstallment.id && can('confirm-checkin')"
                                        @click="payingInstallment = payingInstallment === inst.id ? null : inst.id"
                                        class="text-[11px] font-medium px-2 py-1 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:opacity-90 transition-all">
                                    Record
                                </button>
                                <span v-else-if="!inst.paid_at" class="text-[10px] text-gray-400">Upcoming</span>
                            </div>

                            <!-- Inline record-payment form -->
                            <div v-if="payingInstallment" class="rounded-lg border border-gray-200 dark:border-gray-800 p-3 space-y-2">
                                <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400">Record this week's payment</p>
                                <select v-model="installmentForm.payment_method" :class="inputCls()">
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="pos">POS</option>
                                    <option value="cash">Cash</option>
                                </select>
                                <input v-model="installmentForm.payment_reference" type="text" placeholder="Reference (optional)" :class="inputCls()" />
                                <div class="flex justify-end gap-2">
                                    <button @click="payingInstallment = null" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">Cancel</button>
                                    <button @click="submitInstallment(nextDueInstallment)" :disabled="installmentForm.processing"
                                            class="px-3 py-1.5 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg disabled:opacity-50">
                                        Confirm payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials receipt -->
                    <div :class="card" class="p-4">
                        <p :class="sectionLabel" class="mb-3"><Receipt class="w-3.5 h-3.5" /> Financials</p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">{{ fmt(booking.subtotal / booking.nights) }} × {{ booking.nights }} nights</span>
                                <span class="text-gray-900 dark:text-white tabular-nums">{{ fmt(booking.subtotal) }}</span>
                            </div>
                            <div v-if="booking.discount_amount > 0" class="flex justify-between text-xs text-emerald-600 dark:text-emerald-400">
                                <span>Discount ({{ booking.discount_percent }}% off)</span>
                                <span class="tabular-nums">−{{ fmt(booking.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    Caution fee
                                    <span v-if="booking.caution_fee_refunded" class="px-1 py-0.5 text-[10px] rounded bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">Refunded</span>
                                    <span v-else class="px-1 py-0.5 text-[10px] rounded bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">Refundable</span>
                                </span>
                                <span class="text-gray-900 dark:text-white tabular-nums">{{ fmt(booking.caution_fee) }}</span>
                            </div>
                            <div v-if="booking.late_checkout_fee > 0" class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Late checkout fee</span>
                                <span class="text-gray-900 dark:text-white tabular-nums">{{ fmt(booking.late_checkout_fee) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-gray-900 dark:text-white tabular-nums">{{ fmt(booking.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-xs pt-1">
                                <span class="text-gray-400">Payment</span>
                                <span :class="booking.payment_status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'" class="font-medium capitalize">
                                    {{ booking.payment_status === 'paid' ? `Paid · ${booking.payment_method?.replace('_', ' ')}` : 'Pending' }}
                                </span>
                            </div>
                            <div v-if="booking.payment_reference" class="flex justify-between text-xs">
                                <span class="text-gray-400">Reference</span>
                                <span class="text-gray-600 dark:text-gray-400 font-mono text-[11px]">{{ booking.payment_reference }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustments -->
                    <div v-if="booking.adjustments?.length || can('manage-bookings')" :class="card" class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p :class="sectionLabel"><AlertTriangle class="w-3.5 h-3.5" /> Adjustments</p>
                            <button v-if="can('manage-bookings') && !showAdjForm" @click="showAdjForm = true"
                                    class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">+ Add</button>
                        </div>

                        <div v-if="booking.adjustments?.length" class="space-y-2 mb-3">
                            <div v-for="adj in booking.adjustments" :key="adj.id"
                                 class="flex items-start justify-between text-xs p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ adj.reason }}</p>
                                    <p class="text-gray-400 mt-0.5">{{ fmtDate(adj.transaction_date) }}{{ adj.notes ? ` · ${adj.notes}` : '' }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ml-3">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold tabular-nums">{{ fmt(adj.amount_naira) }}</span>
                                    <button v-if="can('manage-bookings')" @click="deleteAdjustment(adj)"
                                            class="text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!showAdjForm" class="text-xs text-gray-400 mb-3">No adjustments applied.</p>

                        <form v-if="showAdjForm" @submit.prevent="submitAdjustment" class="space-y-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                                    <select v-model="adjForm.amount_type" :class="inputCls()">
                                        <option value="fixed">Fixed (₦)</option>
                                        <option value="percent">Percent (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                    <input v-model="adjForm.amount_value" type="number" step="0.01" min="0" :class="inputCls()" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Reason *</label>
                                <input v-model="adjForm.reason" type="text" :class="inputCls()" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                                    <input v-model="adjForm.transaction_date" type="date" :class="inputCls()" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Reference</label>
                                    <input v-model="adjForm.payment_reference" type="text" :class="inputCls()" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Notes</label>
                                <input v-model="adjForm.notes" type="text" :class="inputCls()" />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="adjForm.processing"
                                        class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">Apply</button>
                                <button type="button" @click="showAdjForm = false"
                                        class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Timeline -->
                    <div :class="card" class="p-4">
                        <p :class="sectionLabel" class="mb-3"><Clock class="w-3.5 h-3.5" /> Timeline</p>
                        <div class="space-y-3 relative">
                            <div class="absolute left-[3px] top-1 bottom-1 w-px bg-gray-100 dark:bg-gray-800" />
                            <div class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-gray-400 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Booking Created</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.created_at) }}</p></div>
                            </div>
                            <div v-if="booking.paid_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Payment Confirmed</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.paid_at) }}</p></div>
                            </div>
                            <div v-if="booking.checked_in_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Checked In</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.checked_in_at) }} · {{ booking.checked_in_by_name }}</p></div>
                            </div>
                            <div v-if="booking.checked_out_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-gray-600 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Checked Out</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.checked_out_at) }}</p></div>
                            </div>
                            <div v-if="booking.paused_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Booking Paused</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.paused_at) }} · {{ booking.remaining_nights }} nights remaining</p></div>
                            </div>
                            <div v-if="booking.resumed_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-violet-400 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Booking Resumed</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.resumed_at) }}</p></div>
                            </div>
                            <div v-if="booking.cancelled_at" class="relative flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-red-500 mt-1.5 shrink-0 ring-4 ring-white dark:ring-gray-900" />
                                <div><p class="text-xs font-medium text-gray-900 dark:text-white">Cancelled</p><p class="text-[11px] text-gray-400">{{ fmtDateTime(booking.cancelled_at) }}</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ Actions rail ════ -->
                <div class="space-y-3 mt-4 lg:mt-0 lg:sticky lg:top-20">

                    <!-- Check-in panel -->
                    <div v-if="booking.status === 'confirmed' && paidEnoughToCheckIn && can('confirm-checkin')" :class="card" class="p-4">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5"><LogIn class="w-3.5 h-3.5 text-emerald-500" /> Check In Guest</p>
                        <form @submit.prevent="submitCheckIn" class="space-y-3">
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg px-3 py-2">
                                <template v-if="booking.payment_plan === 'weekly'">Week 1 is paid - the guest can check in. Later weeks are collected before each week begins.</template>
                                <template v-else>Payment is settled. Confirm the guest's arrival.</template>
                            </p>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Notes <span class="text-gray-400">(optional)</span></label>
                                <textarea v-model="checkInForm.checkin_notes" rows="2" :class="[...inputCls(false), 'resize-none']" />
                            </div>
                            <button type="submit" :disabled="checkInForm.processing"
                                    class="w-full py-2.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg disabled:opacity-50 transition-all flex items-center justify-center gap-1.5">
                                <LogIn class="w-3.5 h-3.5" /> Confirm Check-in
                            </button>
                        </form>
                    </div>

                    <!-- Checked-in state -->
                    <div v-if="booking.status === 'checked_in'" :class="card" class="p-4 ring-1 ring-blue-200 dark:ring-blue-800">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-2 flex items-center gap-1.5"><CheckCircle class="w-3.5 h-3.5" /> Currently Checked In</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">Since {{ fmtDateTime(booking.checked_in_at) }}</p>
                        <p v-if="booking.checked_in_by_name" class="text-xs text-blue-500 dark:text-blue-500 mb-3">By {{ booking.checked_in_by_name }}</p>
                        <button v-if="can('confirm-checkin')" @click="showCheckOutModal = true"
                                class="w-full py-2.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 transition-all flex items-center justify-center gap-1.5">
                            <LogOut class="w-3.5 h-3.5" /> Check Out Guest
                        </button>

                        <!-- Pause booking -->
                        <div v-if="booking.status === 'checked_in' && can('confirm-checkin')" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button v-if="!showPauseForm" @click="showPauseForm = true"
                                    class="w-full py-2 text-xs border border-violet-200 dark:border-violet-800 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-900/20 text-violet-600 dark:text-violet-400 transition-all flex items-center justify-center gap-1.5">
                                <PauseCircle class="w-3.5 h-3.5" /> Pause Booking
                            </button>
                            <form v-else @submit.prevent="submitPause" class="space-y-3">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">Guest departing early</p>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Actual Departure Date</label>
                                    <input v-model="pauseDeparture" type="date" :min="booking.check_in" :max="booking.check_out" :class="inputCls(false)" required />
                                    <p v-if="pauseDeparture" class="mt-1 text-[11px] text-gray-400">
                                        {{ Math.ceil((new Date(booking.check_out) - new Date(pauseDeparture)) / (1000*60*60*24)) }} night(s) will be held for later use
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" :disabled="isPausing || !pauseDeparture"
                                            class="flex-1 py-2 text-xs font-medium bg-violet-600 hover:bg-violet-700 text-white rounded-lg disabled:opacity-50 transition-all">Confirm Pause</button>
                                    <button type="button" @click="showPauseForm = false"
                                            class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Resume booking -->
                    <div v-if="booking.status === 'paused'" :class="card" class="p-4 ring-1 ring-violet-200 dark:ring-violet-800">
                        <p class="text-xs font-semibold text-violet-700 dark:text-violet-400 mb-2 flex items-center gap-1.5"><PauseCircle class="w-3.5 h-3.5" /> Booking Paused</p>
                        <p class="text-xs text-violet-600 dark:text-violet-400 mb-1">{{ booking.remaining_nights }} night{{ booking.remaining_nights !== 1 ? 's' : '' }} remaining</p>
                        <p class="text-xs text-violet-500 dark:text-violet-500 mb-3">Departed: {{ fmtDate(booking.paused_departure) }}</p>

                        <div v-if="!showResumeForm">
                            <button @click="showResumeForm = true" class="w-full py-2.5 text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-all">Resume Booking</button>
                        </div>

                        <form v-else @submit.prevent="submitResume" class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">New Check-in Date</label>
                                <input v-model="resumeCheckIn" type="date" :min="new Date().toISOString().split('T')[0]" :class="inputCls(false)" @change="loadResumeUnits" required />
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
                                        class="flex-1 py-2 text-xs font-medium bg-violet-600 hover:bg-violet-700 text-white rounded-lg disabled:opacity-50 transition-all">Confirm Resume</button>
                                <button type="button" @click="showResumeForm = false"
                                        class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Late checkout -->
                    <div v-if="['confirmed','checked_in'].includes(booking.status)" :class="card" class="p-4">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5"><Clock class="w-3.5 h-3.5 text-amber-500" /> Late Checkout</p>
                        <div v-if="!booking.late_checkout_requested">
                            <button @click="requestLateCheckout" :disabled="isLateCheckoutProcessing"
                                    class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40 text-gray-600 dark:text-gray-400 transition-all">
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
                                    <button @click="approveLateCheckout('approved')" :disabled="!lateCheckoutHours || isLateCheckoutProcessing"
                                            class="flex-1 py-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 text-white rounded-lg transition-all">Approve</button>
                                    <button @click="approveLateCheckout('rejected')" :disabled="isLateCheckoutProcessing"
                                            class="flex-1 py-1.5 text-xs border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-40 transition-all">Reject</button>
                                </div>
                            </div>
                            <button v-if="booking.late_checkout_status === 'approved' && can('confirm-checkin')" @click="settleLateCheckout" :disabled="isLateCheckoutProcessing"
                                    class="w-full py-1.5 text-xs bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white rounded-lg transition-all">Mark Fee as Settled</button>
                        </div>
                    </div>

                    <!-- Caution fee -->
                    <div v-if="booking.caution_fee > 0 && !booking.caution_fee_refunded" :class="card" class="p-4">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5"><Shield class="w-3.5 h-3.5 text-amber-500" /> Caution Fee · {{ fmt(booking.caution_fee) }}</p>

                        <!-- In-stay balance + manage charges -->
                        <div v-if="can('manage-bookings') || can('confirm-checkin')" class="mb-3">
                            <div class="flex items-center justify-between text-[11px] mb-1">
                                <span class="text-gray-500 dark:text-gray-400">Available</span>
                                <span class="font-semibold text-gray-900 dark:text-white tabular-nums">{{ fmt(booking.caution_available ?? booking.caution_fee) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full"
                                     :class="(booking.caution_used ?? 0) >= booking.caution_fee ? 'bg-red-500' : (booking.caution_used ?? 0) / booking.caution_fee > 0.75 ? 'bg-amber-500' : 'bg-emerald-500'"
                                     :style="{ width: Math.min(100, ((booking.caution_used ?? 0) / booking.caution_fee) * 100) + '%' }"></div>
                            </div>
                            <button @click="showCautionModal = true"
                                    class="mt-2 w-full py-2 text-xs font-medium border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-all">
                                Manage charges<span v-if="(booking.caution_charges?.filter(c => !c.voided_at).length)"> · {{ booking.caution_charges.filter(c => !c.voided_at).length }}</span>
                            </button>
                        </div>

                        <!-- PENDING REQUEST: receptionist waiting -->
                        <div v-if="booking.caution_refund_requested && !can('manage-bookings')" class="px-3 py-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-xs font-medium text-blue-700 dark:text-blue-400">Refund request submitted</p>
                            <p class="text-[11px] text-blue-500 dark:text-blue-400 mt-0.5">Awaiting manager approval</p>
                        </div>

                        <!-- PENDING REQUEST: manager approves -->
                        <div v-else-if="booking.caution_refund_requested && can('manage-bookings')" class="space-y-3">
                            <div class="px-3 py-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg space-y-1 text-xs">
                                <p class="font-semibold text-blue-700 dark:text-blue-400">Refund Request Pending</p>
                                <p class="text-blue-600 dark:text-blue-400">Action: <span class="font-medium capitalize">{{ booking.caution_refund_action?.replace(/_/g, ' ') }}</span></p>
                                <p v-if="booking.caution_refund_deduction_amount" class="text-blue-600 dark:text-blue-400">Deduction: <span class="font-medium">{{ fmt(booking.caution_refund_deduction_amount) }}</span></p>
                                <p v-if="booking.caution_refund_reason" class="text-blue-600 dark:text-blue-400">Reason: <span class="font-medium">{{ booking.caution_refund_reason }}</span></p>
                            </div>
                            <button @click="approveCautionRequest" :disabled="isRefunding"
                                    class="w-full py-2.5 text-xs font-semibold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">Approve &amp; Process</button>
                        </div>

                        <!-- NO PENDING REQUEST: receptionist raises one -->
                        <template v-else-if="booking.status === 'completed' && can('confirm-checkin') && !can('manage-bookings')">
                            <div v-if="!showCautionForm" class="space-y-2">
                                <button @click="cautionAction = 'full_refund'; showCautionForm = true" class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all">Full Refund</button>
                                <button @click="cautionAction = 'partial_deduction'; showCautionForm = true" class="w-full py-2 text-xs border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 text-amber-600 dark:text-amber-400 transition-all">Partial Deduction</button>
                                <button @click="cautionAction = 'full_forfeit'; showCautionForm = true" class="w-full py-2 text-xs border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-all">Full Forfeit</button>
                            </div>
                            <form v-else @submit.prevent="submitCautionRequest" class="space-y-3">
                                <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ cautionAction === 'full_refund' ? 'Full Refund' : cautionAction === 'partial_deduction' ? 'Partial Deduction' : 'Full Forfeit' }}</p>
                                </div>
                                <div v-if="cautionAction === 'partial_deduction'">
                                    <label class="block text-xs text-gray-500 mb-1">Deduction Amount (₦)</label>
                                    <input v-model.number="cautionDeductionAmount" type="number" min="1" :max="(booking.caution_available ?? booking.caution_fee) - 1"
                                           class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <p v-if="cautionDeductionAmount" class="mt-1 text-[11px] text-gray-400">Refund: {{ fmt((booking.caution_available ?? booking.caution_fee) - cautionDeductionAmount) }}</p>
                                </div>
                                <div v-if="cautionAction !== 'full_refund'">
                                    <label class="block text-xs text-gray-500 mb-1">Reason <span class="text-red-500">*</span></label>
                                    <textarea v-model="cautionDeductionReason" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" :disabled="isRefunding" class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">Submit Request</button>
                                    <button type="button" @click="showCautionForm = false" class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Back</button>
                                </div>
                            </form>
                        </template>

                        <!-- NO PENDING REQUEST: manager processes directly -->
                        <template v-else-if="booking.status === 'completed' && can('manage-bookings')">
                            <div v-if="!showCautionForm" class="space-y-2">
                                <button @click="cautionAction = 'full_refund'; showCautionForm = true" class="w-full py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all">Full Refund</button>
                                <button @click="cautionAction = 'partial_deduction'; showCautionForm = true" class="w-full py-2 text-xs border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 text-amber-600 dark:text-amber-400 transition-all">Partial Deduction</button>
                                <button @click="cautionAction = 'full_forfeit'; showCautionForm = true" class="w-full py-2 text-xs border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-all">Full Forfeit</button>
                            </div>
                            <form v-else @submit.prevent="submitCautionDirect" class="space-y-3">
                                <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ cautionAction === 'full_refund' ? 'Full Refund' : cautionAction === 'partial_deduction' ? 'Partial Deduction' : 'Full Forfeit' }}</p>
                                </div>
                                <div v-if="cautionAction === 'partial_deduction'">
                                    <label class="block text-xs text-gray-500 mb-1">Deduction Amount (₦)</label>
                                    <input v-model.number="cautionDeductionAmount" type="number" min="1" :max="(booking.caution_available ?? booking.caution_fee) - 1"
                                           class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <p v-if="cautionDeductionAmount" class="mt-1 text-[11px] text-gray-400">Refund: {{ fmt((booking.caution_available ?? booking.caution_fee) - cautionDeductionAmount) }}</p>
                                </div>
                                <div v-if="cautionAction !== 'full_refund'">
                                    <label class="block text-xs text-gray-500 mb-1">Reason <span class="text-red-500">*</span></label>
                                    <textarea v-model="cautionDeductionReason" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" :disabled="isRefunding" class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">Process</button>
                                    <button type="button" @click="showCautionForm = false" class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Back</button>
                                </div>
                            </form>
                        </template>
                    </div>

                    <!-- Caution already processed -->
                    <div v-else-if="booking.caution_fee_refunded" class="px-3 py-2.5 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                            <CheckCircle class="w-3.5 h-3.5" /> Caution fee processed
                            <span v-if="booking.caution_fee_deduction > 0" class="text-amber-600 dark:text-amber-400 ml-1">· {{ fmt(booking.caution_fee_deduction) }} deducted</span>
                        </p>
                    </div>

                    <!-- Modify booking -->
                    <div v-if="!['cancelled','completed'].includes(booking.status) && can('manage-bookings')" :class="card">
                        <button @click="showModifyForm = !showModifyForm"
                                class="w-full flex items-center justify-between px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-2xl transition-all">
                            <span class="flex items-center gap-1.5"><ChevronRight class="w-3.5 h-3.5" /> Modify Booking</span>
                            <span :class="showModifyForm ? 'rotate-90' : ''" class="transition-transform"><ChevronRight class="w-3 h-3 text-gray-400" /></span>
                        </button>
                        <form v-if="showModifyForm" @submit.prevent="submitModify" class="px-4 pb-4 space-y-3 border-t border-gray-100 dark:border-gray-800 pt-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div><label class="block text-xs text-gray-500 mb-1">Check-in</label><input v-model="modifyForm.check_in" type="date" :class="inputCls(false)" /></div>
                                <div><label class="block text-xs text-gray-500 mb-1">Check-out</label><input v-model="modifyForm.check_out" type="date" :class="inputCls(false)" /></div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Nights</label>
                                <input v-model.number="modifyForm.nights" type="number" min="1" :class="inputCls(false)" />
                                <p class="mt-1 text-[11px] text-gray-400">Change nights to auto-update checkout</p>
                            </div>
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
                            <div><label class="block text-xs text-gray-500 mb-1">Guests</label><input v-model.number="modifyForm.guests" type="number" min="1" :class="inputCls(false)" /></div>
                            <div><label class="block text-xs text-gray-500 mb-1">Guest Name</label><input v-model="modifyForm.guest_name" type="text" :class="inputCls(false)" /></div>
                            <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input v-model="modifyForm.guest_phone" type="tel" :class="inputCls(false)" /></div>
                            <div><label class="block text-xs text-gray-500 mb-1">Email</label><input v-model="modifyForm.guest_email" type="email" :class="inputCls(false)" /></div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Special Requests</label>
                                <textarea v-model="modifyForm.special_requests" rows="2" class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                            </div>
                            <div v-if="Object.keys(modifyForm.errors).length" class="text-xs text-red-500">{{ Object.values(modifyForm.errors)[0] }}</div>
                            <div class="flex gap-2 pt-1">
                                <button type="submit" :disabled="modifyForm.processing" class="flex-1 py-2 text-xs font-medium bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">Save Changes</button>
                                <button type="button" @click="showModifyForm = false" class="px-4 py-2 text-xs border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Cancel -->
                    <div v-if="booking.status !== 'cancelled' && booking.status !== 'completed' && booking.status !== 'checked_in' && can('manage-bookings')">
                        <button @click="showCancelModal = true"
                                class="w-full py-2.5 text-xs text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg bg-white dark:bg-gray-900 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex items-center justify-center gap-1.5">
                            <XCircle class="w-3.5 h-3.5" /> Cancel Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <ConfirmationModal
            :show="showCheckOutModal" :processing="isCheckingOut"
            title="Confirm Guest Checkout?"
            message="This will mark the guest as checked out and complete the booking. This action cannot be undone."
            confirm-text="Yes, Check Out" cancel-text="Not Yet" variant="default"
            @confirm="confirmCheckOut" @close="showCheckOutModal = false" />
        <ConfirmationModal
            :show="showCancelModal" :processing="isCancelling"
            title="Cancel This Booking?"
            message="Are you sure you want to cancel this booking? This will free up the unit and notify the guest. This action cannot be undone."
            confirm-text="Yes, Cancel Booking" cancel-text="Keep Booking" variant="danger"
            @confirm="cancelBooking" @close="showCancelModal = false" />

        <CautionChargesModal :show="showCautionModal" :booking="booking" @close="showCautionModal = false" />
    </ManageLayout>
</template>
