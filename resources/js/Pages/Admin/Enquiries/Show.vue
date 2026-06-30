<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Mail, Phone, Calendar, Users, ArrowRight, Trash2 } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    enquiry: Object,
})

const statusForm = useForm({
    status:      props.enquiry.status,
    staff_notes: props.enquiry.staff_notes || '',
})

function saveStatus() {
    statusForm.patch(route('manage.enquiries.update-status', props.enquiry.id), { preserveScroll: true })
}

function convert() {
    router.post(route('manage.enquiries.convert', props.enquiry.id))
}

function destroy() {
    if (confirm('Delete this enquiry? This cannot be undone.')) {
        router.delete(route('manage.enquiries.destroy', props.enquiry.id))
    }
}

const fmtDate = (d) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
const fmtAmount = (n) => '₦' + Number(n).toLocaleString('en-NG')
</script>

<template>
    <Head title="Booking Request" />

    <div class="p-4 lg:p-6 max-w-3xl mx-auto w-full">

        <Link :href="route('manage.enquiries.index')"
              class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors mb-6">
            <ArrowLeft class="w-4 h-4" /> Back to requests
        </Link>

        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ enquiry.guest_name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ enquiry.unit_type?.name }} · {{ enquiry.building?.name }}
                </p>
            </div>
            <button v-if="enquiry.status !== 'converted'" @click="convert"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all whitespace-nowrap">
                Create booking <ArrowRight class="w-4 h-4" />
            </button>
            <Link v-else-if="enquiry.converted_booking_id"
                  :href="route('manage.bookings.show', enquiry.converted_booking_id)"
                  class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                View booking
            </Link>
        </div>

        <!-- Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mb-1"><Calendar class="w-3.5 h-3.5" /> Stay</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ fmtDate(enquiry.check_in) }} → {{ fmtDate(enquiry.check_out) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ enquiry.nights }} night{{ enquiry.nights !== 1 ? 's' : '' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mb-1"><Users class="w-3.5 h-3.5" /> Guests</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ enquiry.guests }}</p>
            </div>
            <a :href="`mailto:${enquiry.guest_email}`" class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mb-1"><Mail class="w-3.5 h-3.5" /> Email</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ enquiry.guest_email }}</p>
            </a>
            <a :href="`tel:${enquiry.guest_phone}`" class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 hover:border-gray-300 dark:hover:border-gray-700 transition-all">
                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mb-1"><Phone class="w-3.5 h-3.5" /> Phone</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ enquiry.guest_phone }}</p>
            </a>
        </div>

        <div v-if="enquiry.special_requests" class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-4 mb-5">
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Special requests</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ enquiry.special_requests }}</p>
        </div>

        <!-- Status update -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5 mb-5">
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Update status</p>
            <div class="flex flex-col gap-3">
                <select v-model="statusForm.status"
                        class="px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="converted">Converted</option>
                    <option value="closed">Closed</option>
                </select>
                <textarea v-model="statusForm.staff_notes" rows="3" placeholder="Internal notes (optional)"
                          class="px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"></textarea>
                <div class="flex items-center justify-between">
                    <button @click="saveStatus" :disabled="statusForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-100 rounded-lg shadow-sm transition-all disabled:opacity-50">
                        Save
                    </button>
                    <button @click="destroy" class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-600 transition-all">
                        <Trash2 class="w-4 h-4" /> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
