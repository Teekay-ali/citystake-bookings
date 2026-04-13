<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeft,
    Building2,
    MapPin,
    FileText,
    Sparkles,
    ToggleLeft,
    ToggleRight,
    Trash2
} from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    building: Object,
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const form = useForm({
    name: props.building.name,
    address: props.building.address,
    city: props.building.city,
    description: props.building.description || '',
    amenities: props.building.amenities || [],
    is_active: props.building.is_active,
});

const amenitiesList = [
    '24/7 Security',
    'Backup Generator',
    'Swimming Pool',
    'Gym/Fitness Center',
    'Parking Space',
    'Elevator',
    'CCTV Surveillance',
    'Concierge Service',
    'High-Speed Internet',
    'Water Supply',
    'Landscaped Gardens',
    'Children\'s Playground',
];

const toggleAmenity = (amenity) => {
    const index = form.amenities.indexOf(amenity);
    if (index > -1) {
        form.amenities.splice(index, 1);
    } else {
        form.amenities.push(amenity);
    }
};

const submit = () => {
    form.put(route('manage.properties.update', props.building.id), {
        onSuccess: () => {
            toast.success('Property updated successfully!');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                toast.error(firstError);
            }
        },
    });
};

const deleteBuilding = () => {
    isDeleting.value = true;
    form.delete(route('manage.properties.destroy', props.building.id), {
        onSuccess: () => {
            toast.success('Property deleted successfully!');
        },
        onError: (errors) => {
            toast.error(errors.error || 'Failed to delete property');
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <ManageLayout>
        <Head :title="`Edit ${building.name} - Admin`" />

        <div class="bg-white dark:bg-gray-950 min-h-screen py-16">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <!-- Back Button -->
                <div class="mb-8">
                    <Link
                        :href="route('manage.properties.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to properties
                    </Link>
                </div>

                <!-- Header -->
                <div class="flex items-start justify-between mb-12">
                    <div>
                        <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                            Edit Property
                        </h1>
                        <p class="text-lg text-gray-600 dark:text-gray-400">
                            Update {{ building.name }} details
                        </p>
                    </div>

                    <button
                        @click="showDeleteModal = true"
                        class="px-4 py-2 border-2 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 text-red-600 dark:text-red-400 font-medium rounded-xl transition-all flex items-center"
                    >
                        <Trash2 class="w-4 h-4 mr-2" />
                        Delete Property
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Building2 class="w-5 h-5 mr-2" />
                            Basic Information
                        </h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Property Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g., CityStake Asokoro"
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                        form.errors.name
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                />
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        City <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.city"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.city
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    >
                                        <option value="Abuja">Abuja</option>
                                        <option value="Lagos">Lagos</option>
                                        <option value="Port Harcourt">Port Harcourt</option>
                                        <option value="Kano">Kano</option>
                                    </select>
                                    <p v-if="form.errors.city" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.city }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Status
                                    </label>
                                    <button
                                        type="button"
                                        @click="form.is_active = !form.is_active"
                                        :class="[
                                            'w-full px-4 py-3 rounded-xl text-left flex items-center justify-between transition-all',
                                            form.is_active
                                                ? 'bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800'
                                                : 'bg-gray-100 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-800'
                                        ]"
                                    >
                                        <span :class="form.is_active ? 'text-green-700 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'" class="font-medium">
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <component :is="form.is_active ? ToggleRight : ToggleLeft" :class="form.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400'" class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Full Address <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    required
                                    placeholder="e.g., Plot 123, Diplomatic Drive, Asokoro"
                                    :class="[
                                        'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all resize-none',
                                        form.errors.address
                                            ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                            : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                    ]"
                                ></textarea>
                                <p v-if="form.errors.address" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.address }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <FileText class="w-5 h-5 mr-2" />
                            Description
                        </h2>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Property Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="5"
                                placeholder="Describe the property, its location, and what makes it special..."
                                class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all resize-none"
                            ></textarea>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Optional - Provide details about the property
                            </p>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Sparkles class="w-5 h-5 mr-2" />
                            Building Amenities
                        </h2>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <button
                                v-for="amenity in amenitiesList"
                                :key="amenity"
                                type="button"
                                @click="toggleAmenity(amenity)"
                                :class="[
                                    'px-4 py-3 rounded-xl text-sm font-medium transition-all text-left',
                                    form.amenities.includes(amenity)
                                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                        : 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800'
                                ]"
                            >
                                {{ amenity }}
                            </button>
                        </div>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            Select all amenities available at this property
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-6">
                        <Link
                            :href="route('manage.properties.index')"
                            class="px-6 py-3 border-2 border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-full transition-all"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-3 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-100 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white dark:text-gray-900 font-medium rounded-full transition-all disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>

                <!-- Delete Confirmation Modal -->
                <ConfirmationModal
                    :show="showDeleteModal"
                    :processing="isDeleting"
                    title="Delete Property?"
                    message="Are you sure you want to delete this property? This action cannot be undone. You can only delete properties with no existing bookings."
                    confirm-text="Yes, Delete Property"
                    cancel-text="Cancel"
                    variant="danger"
                    @confirm="deleteBuilding"
                    @close="showDeleteModal = false"
                />
            </div>
        </div>
    </ManageLayout>
</template>
