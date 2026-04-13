<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
    item:      Object,
    buildings: Array,
})

const form = useForm({
    name:                props.item.name,
    category:            props.item.category ?? '',
    unit:                props.item.unit,
    low_stock_threshold: props.item.low_stock_threshold,
    notes:               props.item.notes ?? '',
    is_active:           props.item.is_active,
})

const unitOptions = ['units', 'kg', 'g', 'litres', 'ml', 'packs', 'boxes', 'rolls', 'pairs', 'sets']

function submit() {
    form.put(route('manage.stock.update', props.item.id))
}
</script>

<template>
    <ManageLayout>
        <Head title="Edit Stock Item" />

        <div class="p-6 lg:p-8 max-w-xl">

            <Link :href="route('manage.stock.show', item.id)"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to {{ item.name }}
            </Link>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">Edit Stock Item</h1>

            <form @submit.prevent="submit" class="space-y-5">

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Item Name *</label>
                        <input v-model="form.name" type="text"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <input v-model="form.category" type="text"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Unit *</label>
                        <select v-model="form.unit"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Low Stock Alert At *</label>
                        <input v-model="form.low_stock_threshold" type="number" min="0"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                    <textarea v-model="form.notes" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                </div>

                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-xl">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Active</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Inactive items are hidden from the stock list</p>
                    </div>
                    <button type="button" @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                        <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform" />
                    </button>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="route('manage.stock.show', item.id)"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </ManageLayout>
</template>
