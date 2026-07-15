<script setup>
import ManageLayout from '@/Layouts/ManageLayout.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ShieldCheck, ExternalLink } from 'lucide-vue-next'

defineOptions({ layout: ManageLayout })

const props = defineProps({
    building: Object,
    policy:   Object,
})

const form = useForm({
    body: props.policy?.body ?? '',
})

const updated = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''

function save() {
    form.put(route('manage.properties.policy.update', props.building.id), { preserveScroll: true })
}
</script>

<template>
    <Head :title="`Policy - ${building.name}`" />

    <div class="p-4 lg:p-6">
        <!-- Header (sticky) -->
        <div class="sticky top-0 z-20 -mx-4 lg:-mx-6 -mt-4 lg:-mt-6 px-4 lg:px-6 py-3 mb-6 flex items-center gap-3 bg-white/90 dark:bg-gray-950/90 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            <Link :href="route('manage.properties.index')"
                  class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                <ArrowLeft class="w-4 h-4" />
            </Link>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate flex items-center gap-2">
                    <ShieldCheck class="w-4 h-4 text-gray-400" /> Property Policy
                </h1>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                    {{ building.name }}
                    <template v-if="policy"> · v{{ policy.version }} · updated {{ updated(policy.updated_at) }}</template>
                    <template v-else> · not published yet</template>
                </p>
            </div>
            <a :href="route('properties.policy', building.slug)" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shrink-0">
                <ExternalLink class="w-3.5 h-3.5" /> Preview
            </a>
        </div>

        <div class="max-w-3xl">
            <div class="bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 rounded-2xl shadow-sm shadow-gray-200/50 dark:shadow-none p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    This is what guests see when they click <span class="font-medium text-gray-700 dark:text-gray-300">“Read our property policy”</span> in their booking confirmation email. Saving publishes a new version - bookings keep pointing at the version that applied when they were created.
                </p>
                <RichTextEditor v-model="form.body" />
                <p v-if="form.errors.body" class="mt-2 text-xs text-red-600">{{ form.errors.body }}</p>

                <div class="flex items-center justify-end gap-2 mt-4">
                    <button @click="save" :disabled="form.processing || !form.body"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                        {{ form.processing ? 'Saving…' : (policy ? 'Publish new version' : 'Publish policy') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
