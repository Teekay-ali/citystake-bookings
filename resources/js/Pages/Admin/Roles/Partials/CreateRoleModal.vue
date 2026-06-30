<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import { X, Plus } from 'lucide-vue-next'

const props = defineProps({ show: Boolean })
const emit = defineEmits(['close'])

const form = useForm({ name: '' })

watch(() => props.show, (open) => { if (open) { form.reset(); form.clearErrors() } })

function submit() {
    form.post(route('manage.roles.store'), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); emit('close') },
    })
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">New Role</h3>
                <button @click="emit('close')"
                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Role name</label>
                    <input v-model="form.name" type="text" placeholder="e.g. Finance Officer, IT Support"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white transition-all" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    <p class="mt-1 text-xs text-gray-400">Lowercased and hyphenated automatically. You'll set its permissions next.</p>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="emit('close')"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-all">
                        <Plus class="w-3.5 h-3.5" /> Create Role
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
