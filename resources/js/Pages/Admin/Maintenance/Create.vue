<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Upload, X } from 'lucide-vue-next'

const props = defineProps({
    buildings: Array,
    vendors: Array,
    issueTypes: Object,
})

const form = useForm({
    building_id:     props.buildings.length === 1 ? props.buildings[0].id : '',
    vendor_id:       '',
    title:           '',
    issue_type:      '',
    description:     '',
    location:        '',
    estimated_cost:  '',
    repair_timeline: '',
    notes:           '',
    photos:          [],
})

const previews = ref([])

function handlePhotos(e) {
    const files = Array.from(e.target.files)
    if (files.length + form.photos.length > 5) {
        alert('Maximum 5 photos allowed.')
        return
    }
    form.photos = [...form.photos, ...files]
    files.forEach(file => {
        const reader = new FileReader()
        reader.onload = e => previews.value.push(e.target.result)
        reader.readAsDataURL(file)
    })
}

function removePhoto(index) {
    form.photos.splice(index, 1)
    previews.value.splice(index, 1)
}

function submit() {
    form.post(route('manage.maintenance.store'), { forceFormData: true })
}
</script>

<template>
    <ManageLayout>
        <Head title="New Maintenance Report" />

        <div class="p-6 lg:p-8 max-w-2xl">

            <Link :href="route('manage.maintenance.index')"
                  class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white mb-6 transition-all">
                <ArrowLeft class="w-4 h-4" /> Back to Maintenance
            </Link>

            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">New Maintenance Report</h1>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Building -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Building *</label>
                    <select v-model="form.building_id"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                        <option value="">Select building</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                </div>

                <!-- Title + Issue Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title *</label>
                    <input v-model="form.title" type="text" placeholder="Brief description"
                           class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Issue Type *</label>
                        <select v-model="form.issue_type"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                            <option value="">Select type</option>
                            <option v-for="(label, key) in issueTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.issue_type" class="mt-1 text-xs text-red-600">{{ form.errors.issue_type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Location</label>
                        <input v-model="form.location" type="text" placeholder="e.g. Unit 3, Lobby"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description *</label>
                    <textarea v-model="form.description" rows="3" placeholder="Describe the issue..."
                              class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Vendor + Cost + Timeline -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Vendor / Contractor</label>
                    <select v-model="form.vendor_id"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                        <option value="">Select vendor (optional)</option>
                        <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }} — {{ v.phone }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Estimated Cost (₦)</label>
                        <input v-model="form.estimated_cost" type="number" min="0" placeholder="0"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Repair Timeline</label>
                        <input v-model="form.repair_timeline" type="date"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white" />
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Additional Notes</label>
                    <textarea v-model="form.notes" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white resize-none" />
                </div>

                <!-- Photos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Photos <span class="text-gray-400 font-normal">(up to 5)</span>
                    </label>
                    <div v-if="previews.length" class="grid grid-cols-3 gap-2 mb-3">
                        <div v-for="(src, i) in previews" :key="i" class="relative aspect-square">
                            <img :src="src" class="w-full h-full object-cover rounded-xl" />
                            <button type="button" @click="removePhoto(i)"
                                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center">
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                    <label v-if="form.photos.length < 5"
                           class="flex items-center gap-3 px-4 py-3 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-gray-400 transition-all">
                        <Upload class="w-4 h-4 text-gray-400" />
                        <span class="text-sm text-gray-500">Click to upload photos</span>
                        <input type="file" accept="image/*" multiple class="sr-only" @change="handlePhotos" />
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-medium hover:opacity-90 disabled:opacity-50 transition-all text-sm">
                        {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                    </button>
                    <Link :href="route('manage.maintenance.index')"
                          class="px-6 py-3 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </ManageLayout>
</template>
