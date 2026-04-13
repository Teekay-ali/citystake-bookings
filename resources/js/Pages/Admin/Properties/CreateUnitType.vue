<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Home,
    Users,
    DollarSign,
    Sparkles,
    FileText,
    ToggleLeft,
    ToggleRight,
    Bed
} from 'lucide-vue-next';

const props = defineProps({
    building: Object,
});


const form = useForm({
    name: '',
    bedroom_type: '2-bed',
    max_guests: 4,
    base_price_per_night: '',
    cleaning_fee: '',
    service_charge_percent: 10,
    description: '',
    specific_amenities: [],
    is_active: true,
});

const bedroomTypes = [
    { value: '2-bed', label: '2-Bedroom', defaultGuests: 4 },
    { value: '3-bed', label: '3-Bedroom', defaultGuests: 6 },
    { value: '4-bed', label: '4-Bedroom', defaultGuests: 8 },
];

const specificAmenitiesList = [
    'King Size Bed',
    'Queen Size Bed',
    'En-suite Bathroom',
    'Walk-in Closet',
    'Balcony/Terrace',
    'City View',
    'Garden View',
    'Fully Equipped Kitchen',
    'Microwave',
    'Refrigerator',
    'Washing Machine',
    'Dryer',
    'Air Conditioning',
    'Heating',
    'Smart TV',
    'Work Desk',
    'Dining Area',
    'Living Room',
];

const toggleAmenity = (amenity) => {
    const index = form.specific_amenities.indexOf(amenity);
    if (index > -1) {
        form.specific_amenities.splice(index, 1);
    } else {
        form.specific_amenities.push(amenity);
    }
};

// Auto-update max guests when bedroom type changes
const updateMaxGuests = () => {
    const selected = bedroomTypes.find(bt => bt.value === form.bedroom_type);
    if (selected) {
        form.max_guests = selected.defaultGuests;
    }
};

const submit = () => {
    form.post(route('manage.unit-types.store', props.building.id), {
        onSuccess: () => {
            toast.success('Unit type created successfully!');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                toast.error(firstError);
            }
        },
    });
};
</script>

<template>
    <ManageLayout>
        <Head :title="`Add Unit Type - ${building.name}`" />

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
                <div class="mb-12">
                    <h1 class="text-4xl font-light tracking-tight text-gray-900 dark:text-white mb-2">
                        Add Unit Type
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Add a new unit type to {{ building.name }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Home class="w-5 h-5 mr-2" />
                            Basic Information
                        </h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Unit Type Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g., Deluxe 2-Bedroom Apartment"
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

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Bedroom Type <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.bedroom_type"
                                        @change="updateMaxGuests"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.bedroom_type
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    >
                                        <option v-for="type in bedroomTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.bedroom_type" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.bedroom_type }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Max Guests <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.max_guests"
                                        type="number"
                                        min="1"
                                        max="20"
                                        required
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.max_guests
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    />
                                    <p v-if="form.errors.max_guests" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.max_guests }}
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
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <DollarSign class="w-5 h-5 mr-2" />
                            Pricing
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Base Price (per night) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">₦</span>
                                    <input
                                        v-model.number="form.base_price_per_night"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        required
                                        placeholder="50000"
                                        :class="[
                                            'w-full pl-8 pr-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.base_price_per_night
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    />
                                </div>
                                <p v-if="form.errors.base_price_per_night" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.base_price_per_night }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Cleaning Fee <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">₦</span>
                                    <input
                                        v-model.number="form.cleaning_fee"
                                        type="number"
                                        min="0"
                                        step="500"
                                        required
                                        placeholder="5000"
                                        :class="[
                                            'w-full pl-8 pr-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.cleaning_fee
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    />
                                </div>
                                <p v-if="form.errors.cleaning_fee" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.cleaning_fee }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Service Charge (%) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model.number="form.service_charge_percent"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        required
                                        placeholder="10"
                                        :class="[
                                            'w-full px-4 py-3 bg-white dark:bg-gray-950 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 transition-all',
                                            form.errors.service_charge_percent
                                                ? 'border-2 border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'border border-gray-200 dark:border-gray-800 focus:ring-gray-900 dark:focus:ring-white'
                                        ]"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">%</span>
                                </div>
                                <p v-if="form.errors.service_charge_percent" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.service_charge_percent }}
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
                                Unit Type Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                placeholder="Describe this unit type, its features, and what makes it special..."
                                class="w-full px-4 py-3 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all resize-none"
                            ></textarea>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Optional - Highlight key features and benefits
                            </p>
                        </div>
                    </div>

                    <!-- Specific Amenities -->
                    <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
                        <h2 class="text-xl font-medium text-gray-900 dark:text-white mb-6 flex items-center">
                            <Sparkles class="w-5 h-5 mr-2" />
                            Unit-Specific Amenities
                        </h2>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <button
                                v-for="amenity in specificAmenitiesList"
                                :key="amenity"
                                type="button"
                                @click="toggleAmenity(amenity)"
                                :class="[
                                    'px-4 py-3 rounded-xl text-sm font-medium transition-all text-left',
                                    form.specific_amenities.includes(amenity)
                                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                        : 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800'
                                ]"
                            >
                                {{ amenity }}
                            </button>
                        </div>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                            Select amenities specific to this unit type (building amenities are inherited)
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
                            <span v-if="form.processing">Creating...</span>
                            <span v-else>Create Unit Type</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ManageLayout>
</template>
