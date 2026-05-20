<script setup>
import { ref } from 'vue';
import { Upload, Trash2, Star, GripVertical, ImageOff } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    modelType: { type: String, required: true }, // 'building' or 'unit-type'
    modelId:   { type: Number, required: true },
    initial:   { type: Array,  default: () => [] },
    inheritedFrom: { type: String, default: null }, // building name if inheriting
});

const emit = defineEmits(['updated']);

const images     = ref([...props.initial]);
const uploading  = ref(false);
const dragOver   = ref(false);
const draggingId = ref(null);
const error      = ref(null);

// ── Upload ────────────────────────────────────────────────────────────────────
async function handleFiles(files) {
    if (!files?.length) return;
    error.value   = null;
    uploading.value = true;

    const formData = new FormData();
    Array.from(files).forEach(f => formData.append('images[]', f));

    try {
        const { data } = await axios.post(
            route('manage.images.store', { type: props.modelType, id: props.modelId }),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        images.value.push(...data.images);
        emit('updated', images.value);
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Upload failed. Check file size (max 5MB each).';
    } finally {
        uploading.value = false;
    }
}

function onFileInput(e)    { handleFiles(e.target.files); e.target.value = ''; }
function onDrop(e)         { dragOver.value = false; handleFiles(e.dataTransfer.files); }

// ── Delete ────────────────────────────────────────────────────────────────────
async function deleteImage(image) {
    if (!confirm('Delete this image?')) return;
    try {
        await axios.delete(route('manage.images.destroy', image.id));
        images.value = images.value.filter(i => i.id !== image.id);
        // If we deleted the primary, mark first as primary locally
        if (image.is_primary && images.value.length) {
            images.value[0].is_primary = true;
        }
        emit('updated', images.value);
    } catch {
        error.value = 'Failed to delete image.';
    }
}

// ── Set Primary ───────────────────────────────────────────────────────────────
async function setPrimary(image) {
    if (image.is_primary) return;
    try {
        await axios.patch(route('manage.images.primary', image.id));
        images.value.forEach(i => i.is_primary = (i.id === image.id));
        emit('updated', images.value);
    } catch {
        error.value = 'Failed to set primary image.';
    }
}

// ── Drag-to-reorder ───────────────────────────────────────────────────────────
function dragStart(id)  { draggingId.value = id; }
function dragEnter(id)  {
    if (draggingId.value === id) return;
    const from = images.value.findIndex(i => i.id === draggingId.value);
    const to   = images.value.findIndex(i => i.id === id);
    if (from === -1 || to === -1) return;
    const arr = [...images.value];
    arr.splice(to, 0, arr.splice(from, 1)[0]);
    images.value = arr;
}
async function dragEnd() {
    draggingId.value = null;
    const order = images.value.map((img, idx) => ({ id: img.id, sort_order: idx + 1 }));
    try {
        await axios.patch(route('manage.images.reorder'), { order });
        emit('updated', images.value);
    } catch {
        error.value = 'Failed to save order.';
    }
}
</script>

<template>
    <div class="space-y-4">

        <!-- Inherited notice -->
        <div v-if="images.length === 0 && inheritedFrom"
             class="flex items-center gap-2 px-4 py-3 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-xl text-sm text-blue-700 dark:text-blue-300">
            <ImageOff class="w-4 h-4 flex-shrink-0" />
            No images uploaded yet. Guests will see <strong class="mx-1">{{ inheritedFrom }}</strong> building images as a fallback.
        </div>

        <!-- Image Grid -->
        <div v-if="images.length > 0"
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div
                v-for="image in images"
                :key="image.id"
                draggable="true"
                @dragstart="dragStart(image.id)"
                @dragenter.prevent="dragEnter(image.id)"
                @dragend="dragEnd"
                @dragover.prevent
                :class="[
                    'relative group rounded-xl overflow-hidden border-2 transition-all cursor-grab active:cursor-grabbing',
                    image.is_primary
                        ? 'border-amber-400 dark:border-amber-500'
                        : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700',
                    draggingId === image.id ? 'opacity-50 scale-95' : ''
                ]">

                <img :src="image.url ?? image.image_path"
                     :alt="'Property image'"
                     class="w-full aspect-[4/3] object-cover" />

                <!-- Primary badge -->
                <div v-if="image.is_primary"
                     class="absolute top-2 left-2 flex items-center gap-1 px-2 py-0.5 bg-amber-400 text-amber-900 text-xs font-semibold rounded-full">
                    <Star class="w-3 h-3" /> Primary
                </div>

                <!-- Drag handle -->
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <GripVertical class="w-4 h-4 text-white drop-shadow" />
                </div>

                <!-- Actions overlay -->
                <div class="absolute inset-x-0 bottom-0 flex gap-1 p-2 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        v-if="!image.is_primary"
                        type="button"
                        @click="setPrimary(image)"
                        class="flex-1 flex items-center justify-center gap-1 py-1.5 bg-amber-400/90 hover:bg-amber-400 text-amber-900 text-xs font-medium rounded-lg transition-colors">
                        <Star class="w-3 h-3" /> Set Primary
                    </button>
                    <button
                        type="button"
                        @click="deleteImage(image)"
                        class="flex items-center justify-center p-1.5 bg-red-500/90 hover:bg-red-500 text-white rounded-lg transition-colors">
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload Zone -->
        <div
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
            :class="[
                'relative border-2 border-dashed rounded-2xl p-8 text-center transition-all',
                dragOver
                    ? 'border-gray-900 dark:border-white bg-gray-50 dark:bg-gray-900'
                    : 'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600'
            ]">

            <input
                type="file"
                accept="image/jpeg,image/jpg,image/png,image/webp"
                multiple
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                @change="onFileInput"
                :disabled="uploading" />

            <div v-if="uploading" class="flex flex-col items-center gap-2">
                <div class="w-8 h-8 border-2 border-gray-900 dark:border-white border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Uploading...</p>
            </div>
            <div v-else class="flex flex-col items-center gap-2">
                <Upload class="w-8 h-8 text-gray-400" />
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Drop images here or <span class="underline">click to browse</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    JPEG, PNG or WebP · Max 5MB per image · Multiple allowed
                </p>
            </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

        <!-- Hint -->
        <p class="text-xs text-gray-500 dark:text-gray-500">
            Drag images to reorder · Click an image to set it as primary (shown as cover photo)
        </p>
    </div>
</template>
