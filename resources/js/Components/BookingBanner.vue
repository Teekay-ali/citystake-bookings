<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Clock, CheckCircle, Calendar, ArrowRight, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
    booking: Object, // null-safe — component handles its own null guard
})

const formatDate = (d) =>
    new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })

const nights = computed(() => {
    if (!props.booking) return 0
    const a = new Date(props.booking.check_in)
    const b = new Date(props.booking.check_out)
    return Math.round((b - a) / 86400000)
})

const config = computed(() => {
    if (!props.booking) return null
    const s = props.booking.status
    const p = props.booking.payment_status

    if (p === 'pending' || s === 'pending') {
        return {
            bar: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
            icon: Clock,
            iconClass: 'text-amber-500',
            label: 'Booking pending payment',
            sub: `Complete payment to confirm your stay · ${formatDate(props.booking.check_in)} – ${formatDate(props.booking.check_out)} · ${nights.value} night${nights.value !== 1 ? 's' : ''}`,
            cta: 'Complete Payment',
            ctaHref: route('bookings.payment', props.booking.booking_reference),
            ctaClass: 'bg-amber-500 hover:bg-amber-600 text-white',
            secondaryHref: route('bookings.show', props.booking.id),
            secondaryLabel: 'View booking',
        }
    }
    if (s === 'confirmed') {
        return {
            bar: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
            icon: Calendar,
            iconClass: 'text-blue-500',
            label: 'Booking confirmed',
            sub: `${formatDate(props.booking.check_in)} – ${formatDate(props.booking.check_out)} · ${nights.value} night${nights.value !== 1 ? 's' : ''}`,
            cta: 'View booking',
            ctaHref: route('bookings.show', props.booking.id),
            ctaClass: 'bg-blue-600 hover:bg-blue-700 text-white',
            secondaryHref: null,
            secondaryLabel: null,
        }
    }
    if (s === 'checked_in') {
        return {
            bar: 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800',
            icon: CheckCircle,
            iconClass: 'text-emerald-500',
            label: 'You\'re currently checked in',
            sub: `Checked in ${formatDate(props.booking.check_in)} · Checkout ${formatDate(props.booking.check_out)}`,
            cta: 'View booking',
            ctaHref: route('bookings.show', props.booking.id),
            ctaClass: 'bg-emerald-600 hover:bg-emerald-700 text-white',
            secondaryHref: null,
            secondaryLabel: null,
        }
    }
    return null
})
</script>

<template>
    <div v-if="booking && config"
         :class="['border rounded-2xl px-5 py-4 flex items-center gap-4', config.bar]">

        <!-- Icon -->
        <component :is="config.icon" :class="['w-5 h-5 flex-shrink-0', config.iconClass]" />

        <!-- Text -->
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ config.label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ config.sub }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <Link v-if="config.secondaryHref"
                  :href="config.secondaryHref"
                  class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline underline-offset-2 transition-colors hidden sm:block">
                {{ config.secondaryLabel }}
            </Link>
            <Link :href="config.ctaHref"
                  :class="['inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold transition-colors whitespace-nowrap', config.ctaClass]">
                {{ config.cta }}
                <ArrowRight class="w-3.5 h-3.5" />
            </Link>
        </div>
    </div>
</template>
