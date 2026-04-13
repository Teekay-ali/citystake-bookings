<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    buildings: Array,
    vendors:   Array,
})

const useVendorDirectory = ref(true)

const form = useForm({
    building_id:    props.buildings.length === 1 ? props.buildings[0].id : '',
    title:          '',
    justification:  '',
    notes:          '',
    vendor_id:      '',
    supplier_name:  '',
    supplier_phone: '',
    supplier_email: '',
    items: [
        { name: '', description: '', quantity: 1, unit_price: '' }
    ],
})

function addItem() {
    form.items.push({ name: '', description: '', quantity: 1, unit_price: '' })
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
    }
}

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantity) * Number(item.unit_price) || 0)
    }, 0)
})

function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

function submit() {
    form.post(route('manage.procurement.store'))
}
</script>

<template>
    <ManageLayout>
        <Head title="New Procurement Request" />

        <div class="p-6 lg:p-8 max-w-3xl">

            <Link :href="route('manage.procurement.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Procurement
            </Link>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">New Procurement Request</h1>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Request Details</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Building *</label>
                        <select v-model="form.building_id"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select building</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Request Title *</label>
                        <input v-model="form.title" type="text" placeholder="e.g. Office supplies for Q2"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Justification</label>
                        <textarea v-model="form.justification" rows="2" placeholder="Why is this purchase needed?"
                                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Items</h2>
                        <button type="button" @click="addItem"
                                class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all">
                            <Plus class="w-4 h-4" /> Add Item
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index"
                             class="border border-gray-100 dark:border-gray-800 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Item {{ index + 1 }}</p>
                                <button type="button" @click="removeItem(index)"
                                        v-if="form.items.length > 1"
                                        class="text-red-400 hover:text-red-600 transition-all">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Item Name *</label>
                                    <input v-model="item.name" type="text" placeholder="e.g. Printer paper A4"
                                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                    <p v-if="form.errors[`items.${index}.name`]" class="mt-1 text-xs text-red-600">
                                        {{ form.errors[`items.${index}.name`] }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Quantity *</label>
                                    <input v-model="item.quantity" type="number" min="1"
                                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Unit Price (₦) *</label>
                                    <input v-model="item.unit_price" type="number" min="0" step="0.01"
                                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Description</label>
                                    <input v-model="item.description" type="text" placeholder="Optional details"
                                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                                </div>
                            </div>

                            <!-- Line total -->
                            <div class="flex justify-end mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Line total:
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ formatAmount(Number(item.quantity) * Number(item.unit_price) || 0) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Grand total -->
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatAmount(totalAmount) }}</span>
                    </div>
                    <p v-if="form.errors.items" class="mt-1 text-xs text-red-600">{{ form.errors.items }}</p>
                </div>

                <!-- Supplier -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Supplier</h2>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="useVendorDirectory = true"
                                    :class="useVendorDirectory ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400'"
                                    class="text-sm transition-all">From Directory</button>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button" @click="useVendorDirectory = false"
                                    :class="!useVendorDirectory ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400'"
                                    class="text-sm transition-all">Manual Entry</button>
                        </div>
                    </div>

                    <!-- From directory -->
                    <div v-if="useVendorDirectory">
                        <select v-model="form.vendor_id"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select supplier (optional)</option>
                            <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }} — {{ v.phone }}</option>
                        </select>
                    </div>

                    <!-- Manual -->
                    <div v-else class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Supplier Name</label>
                            <input v-model="form.supplier_name" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Phone</label>
                            <input v-model="form.supplier_phone" type="text"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Email</label>
                            <input v-model="form.supplier_email" type="email"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Additional Notes</label>
                    <textarea v-model="form.notes" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </button>
                    <Link :href="route('manage.procurement.index')"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </ManageLayout>
</template>
