<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import ManageLayout from '@/Layouts/ManageLayout.vue'
import { ArrowLeft, Upload, X } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    buildings:  Array,
    vendors:    Array,
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

const inputClass = "w-full pl-3 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all"
const labelClass = "block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5"
</script>

<template>
    <Head title="New Maintenance Report" />

    <div class="p-6 lg:p-8">
        <div class="max-w-2xl">

            <!-- Back link -->
            <Link :href="route('manage.maintenance.index')"
                  class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors">
                <ArrowLeft class="w-3.5 h-3.5" />
                Back to Maintenance
            </Link>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">New Maintenance Report</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Submit a maintenance or repair request for review</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">

                <!-- Building -->
                <div>
                    <label :class="labelClass">Building *</label>
                    <select v-model="form.building_id" :class="inputClass">
                        <option value="">Select building</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <p v-if="form.errors.building_id" class="mt-1 text-xs text-red-600">{{ form.errors.building_id }}</p>
                </div>

                <!-- Title -->
                <div>
                    <label :class="labelClass">Title *</label>
                    <input v-model="form.title" type="text" placeholder="Brief description of the issue" :class="inputClass" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <!-- Issue type + Location -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label :class="labelClass">Issue Type *</label>
                        <select v-model="form.issue_type" :class="inputClass">
                            <option value="">Select type</option>
                            <option v-for="(label, key) in issueTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.issue_type" class="mt-1 text-xs text-red-600">{{ form.errors.issue_type }}</p>
                    </div>
                    <div>
                        <label :class="labelClass">Location</label>
                        <input v-model="form.location" type="text" placeholder="e.g. Unit 3, Lobby" :class="inputClass" />
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label :class="labelClass">Description *</label>
                    <textarea v-model="form.description" rows="3" placeholder="Describe the issue in detail..." :class="inputClass + ' resize-none'" />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Vendor -->
                <div>
                    <label :class="labelClass">Vendor / Contractor</label>
                    <select v-model="form.vendor_id" :class="inputClass">
                        <option value="">Select vendor (optional)</option>
                        <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }} — {{ v.phone }}</option>
                    </select>
                </div>

                <!-- Cost + Timeline -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label :class="labelClass">Estimated Cost (₦)</label>
                        <input v-model="form.estimated_cost" type="number" min="0" placeholder="0" :class="inputClass" />
                    </div>
                    <div>
                        <label :class="labelClass">Repair Timeline</label>
                        <input v-model="form.repair_timeline" type="date" :class="inputClass" />
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label :class="labelClass">Additional Notes</label>
                    <textarea v-model="form.notes" rows="2" placeholder="Any additional context..." :class="inputClass + ' resize-none'" />
                </div>

                <!-- Photos -->
                <div>
                    <label :class="labelClass">
                        Photos
                        <span class="font-normal text-gray-400">(up to 5)</span>
                    </label>

                    <div v-if="previews.length" class="grid grid-cols-4 gap-2 mb-2">
                        <div v-for="(src, i) in previews" :key="i" class="relative aspect-square">
                            <img :src="src" class="w-full h-full object-cover rounded-lg" />
                            <button type="button" @click="removePhoto(i)"
                                    class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>

                    <label v-if="form.photos.length < 5"
                           class="flex items-center gap-3 px-4 py-3 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-gray-400 dark:hover:border-gray-500 transition-all">
                        <Upload class="w-4 h-4 text-gray-400" />
                        <span class="text-sm text-gray-400">Click to upload photos</span>
                        <input type="file" accept="image/*" multiple class="sr-only" @change="handlePhotos" />
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 px-4 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                        {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                    </button>
                    <Link :href="route('manage.maintenance.index')"
                          class="px-4 py-2.5 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-900 transition-all">
                        Cancel
                    </Link>
                </div>

            </form>
        </div>
    </div>
</template>
