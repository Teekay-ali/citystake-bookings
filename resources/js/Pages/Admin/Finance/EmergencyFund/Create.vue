<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, ShieldAlert, AlertTriangle, Upload, FileText } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildingFunds: Array,
})

const form = useForm({
    building_id:         props.buildingFunds.length === 1 ? props.buildingFunds[0].id : '',
    amount:              '',
    reason:              '',
    urgency_description: '',
    evidence:            null,
})

const evidencePreview = ref(null)

const selectedFund = computed(() =>
    props.buildingFunds.find(b => b.id == form.building_id) ?? null
)

const formatPrice = (v) => new Intl.NumberFormat('en-NG', {
    style: 'currency', currency: 'NGN', minimumFractionDigits: 0,
}).format(v || 0)

const amountExceedsBalance = computed(() =>
    form.amount && selectedFund.value && parseFloat(form.amount) > selectedFund.value.remaining
)

function onEvidenceChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.evidence = file
    if (evidencePreview.value) URL.revokeObjectURL(evidencePreview.value)
    evidencePreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : null
}

onUnmounted(() => {
    if (evidencePreview.value) URL.revokeObjectURL(evidencePreview.value)
})

function submit() {
    form.post(route('manage.emergency-fund.store'), { forceFormData: true })
}
</script>

<template>
    <Head title="New Emergency Fund Request" />

    <div class="p-6 lg:p-8 max-w-2xl">

        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <Link
                :href="route('manage.emergency-fund.index')"
                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-light text-gray-900 dark:text-white flex items-center gap-2">
                    <ShieldAlert class="w-6 h-6 text-red-500" />
                    Emergency Fund Request
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Requires manager then CEO approval</p>
            </div>
        </div>

        <!-- Fund Balance (if building selected) -->
        <div v-if="selectedFund" class="mb-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ selectedFund.name }} - {{ new Date().toLocaleDateString('en-NG', { month: 'long', year: 'numeric' }) }}
                    </p>
                    <span :class="[
                        'text-xs font-medium px-2.5 py-1 rounded-full border',
                        selectedFund.remaining <= 0 ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800' :
                        selectedFund.remaining < selectedFund.limit * 0.2 ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' :
                        'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800'
                    ]">
                        {{ formatPrice(selectedFund.remaining) }} remaining
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div
                        :style="{ width: Math.min(100, Math.round((selectedFund.used / selectedFund.limit) * 100)) + '%' }"
                        :class="[
                            'h-full rounded-full transition-all duration-500',
                            selectedFund.remaining <= 0 ? 'bg-red-500' :
                            selectedFund.remaining < selectedFund.limit * 0.2 ? 'bg-amber-500' : 'bg-green-500'
                        ]"
                    />
                </div>

                <div class="flex justify-between mt-2 text-xs text-gray-400">
                    <span>Used: {{ formatPrice(selectedFund.used) }}</span>
                    <span>Limit: {{ formatPrice(selectedFund.limit) }}</span>
                </div>
            </div>

            <!-- Exhausted warning -->
            <div v-if="selectedFund.remaining <= 0"
                 class="mt-3 flex items-start gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-400">
                <AlertTriangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                The emergency fund for {{ selectedFund.name }} is fully exhausted for this month. No new requests can be submitted.
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">

            <!-- Building -->
            <div v-if="buildingFunds.length > 1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Building</label>
                <select
                    v-model="form.building_id"
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                >
                    <option value="">Select building</option>
                    <option v-for="b in buildingFunds" :key="b.id" :value="b.id">
                        {{ b.name }} ({{ formatPrice(b.remaining) }} remaining)
                    </option>
                </select>
                <p v-if="form.errors.building_id" class="mt-1 text-sm text-red-600">{{ form.errors.building_id }}</p>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount Requested (₦)</label>
                <input
                    v-model="form.amount"
                    type="number"
                    min="1"
                    :max="selectedFund?.remaining ?? undefined"
                    placeholder="0"
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                    :class="amountExceedsBalance ? 'border-red-400 dark:border-red-600' : ''"
                />
                <p v-if="form.amount && !amountExceedsBalance && selectedFund" class="mt-1 text-xs text-gray-500">
                    {{ formatPrice(form.amount) }} - {{ formatPrice(selectedFund.remaining - parseFloat(form.amount || 0)) }} will remain after this request
                </p>
                <p v-if="amountExceedsBalance" class="mt-1 text-sm text-red-600">
                    Amount exceeds available balance of {{ formatPrice(selectedFund?.remaining) }}
                </p>
                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
            </div>

            <!-- Reason -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason</label>
                <input
                    v-model="form.reason"
                    type="text"
                    placeholder="Brief description of what funds are needed for"
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white"
                />
                <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</p>
            </div>

            <!-- Why urgent -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Why Is This Urgent?
                </label>
                <textarea
                    v-model="form.urgency_description"
                    rows="4"
                    placeholder="Explain the urgency and why this cannot wait for a regular payment approval..."
                    class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none"
                />
                <p v-if="form.errors.urgency_description" class="mt-1 text-sm text-red-600">{{ form.errors.urgency_description }}</p>
            </div>

            <!-- Evidence -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Supporting Evidence
                    <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    <input
                        type="file"
                        accept="image/jpeg,image/jpg,image/png,application/pdf"
                        @change="onEvidenceChange"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    <div v-if="evidencePreview">
                        <img :src="evidencePreview" class="max-h-32 mx-auto rounded-lg object-contain mb-2" />
                        <p class="text-xs text-gray-500">Click to change</p>
                    </div>
                    <div v-else-if="form.evidence">
                        <FileText class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ form.evidence.name }}</p>
                        <p class="text-xs text-gray-400 mt-1">Click to change</p>
                    </div>
                    <div v-else>
                        <Upload class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-600 dark:text-gray-400">Attach a photo, receipt or supporting document</p>
                        <p class="text-xs text-gray-400 mt-1">JPEG, PNG or PDF · Max 5MB</p>
                    </div>
                </div>
                <p v-if="form.errors.evidence" class="mt-1 text-sm text-red-600">{{ form.errors.evidence }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-900">
                <Link
                    :href="route('manage.emergency-fund.index')"
                    class="px-6 py-3 border-2 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-full hover:border-gray-300 transition-all"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || amountExceedsBalance || (selectedFund && selectedFund.remaining <= 0)"
                    class="flex items-center gap-2 px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-full hover:bg-gray-700 dark:hover:bg-gray-100 transition-all disabled:opacity-50"
                >
                    <ShieldAlert class="w-4 h-4" />
                    {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                </button>
            </div>
        </form>
    </div>
</template>
