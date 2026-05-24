<script setup>
import { ref } from 'vue'
import { Upload, Trash2, FileText, Image, ExternalLink, File } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
    modelType: { type: String, required: true },
    modelId:   { type: Number, required: true },
    initial:   { type: Array,  default: () => [] },
    readonly:  { type: Boolean, default: false },
})

const success = ref(null)

const emit = defineEmits(['updated'])

const documents  = ref([...props.initial])
const uploading  = ref(false)
const dragOver   = ref(false)
const error      = ref(null)

const iconFor = (doc) => {
    if (doc.is_image) return Image
    if (doc.mime_type === 'application/pdf') return FileText
    return File
}

async function handleFiles(files) {
    if (!files?.length) return
    error.value    = null
    uploading.value = true

    const formData = new FormData()
    Array.from(files).forEach(f => formData.append('documents[]', f))

    try {
        const { data } = await axios.post(
            route('manage.documents.store', { type: props.modelType, id: props.modelId }),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        documents.value.push(...data.documents)
        emit('updated', documents.value)
        success.value = data.message
        setTimeout(() => success.value = null, 3000)
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Upload failed. Max 5MB per file, up to 5 files.'
    } finally {
        uploading.value = false
    }
}

function onFileInput(e) { handleFiles(e.target.files); e.target.value = '' }
function onDrop(e)      { dragOver.value = false; handleFiles(e.dataTransfer.files) }

async function deleteDocument(doc) {
    if (!confirm(`Delete "${doc.original_name}"?`)) return
    try {
        await axios.delete(route('manage.documents.destroy', doc.id))
        documents.value = documents.value.filter(d => d.id !== doc.id)
        emit('updated', documents.value)
        success.value = 'Document deleted.'
        setTimeout(() => success.value = null, 3000)
    } catch {
        error.value = 'Failed to delete document.'
    }
}
</script>

<template>
    <div class="space-y-3">

        <!-- Document List -->
        <div v-if="documents.length > 0" class="space-y-2">
            <div
                v-for="doc in documents"
                :key="doc.id"
                class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl group"
            >
                <!-- Icon -->
                <div class="w-9 h-9 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center flex-shrink-0">
                    <component :is="iconFor(doc)" class="w-4 h-4 text-gray-500" />
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ doc.original_name }}</p>
                    <p class="text-xs text-gray-400">{{ doc.formatted_size }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a
                        :href="doc.url"
                        target="_blank"
                        class="p-1.5 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                        title="View"
                    >
                        <ExternalLink class="w-4 h-4" />
                    </a>
                    <button
                        v-if="!readonly"
                        type="button"
                        @click="deleteDocument(doc)"
                        class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                        title="Delete"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else-if="readonly" class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500 italic">
            No documents attached.
        </div>

        <!-- Upload Zone -->
        <div
            v-if="!readonly"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
            :class="[
                'relative border-2 border-dashed rounded-xl p-5 text-center transition-all',
                dragOver
                    ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500'
            ]"
        >
            <input
                type="file"
                accept="image/jpeg,image/jpg,image/png,application/pdf"
                multiple
                :disabled="uploading"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                @change="onFileInput"
            />

            <div v-if="uploading" class="flex flex-col items-center gap-2">
                <div class="w-6 h-6 border-2 border-gray-900 dark:border-white border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-gray-500">Uploading...</p>
            </div>
            <div v-else class="flex flex-col items-center gap-1.5">
                <Upload class="w-6 h-6 text-gray-400" />
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Drop files here or <span class="underline">click to browse</span>
                </p>
                <p class="text-xs text-gray-400">PDF, JPEG or PNG · Max 5MB · Up to 5 files</p>
            </div>
        </div>

        <!-- Success -->
        <p v-if="success" class="text-sm text-green-600 dark:text-green-400">{{ success }}</p>

        <!-- Error -->
        <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    </div>
</template>
