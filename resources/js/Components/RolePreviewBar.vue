<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import { Eye, X, Building2 } from 'lucide-vue-next'

const page = usePage()

const preview  = computed(() => page.props.preview)          // { role, building } | null
const canOpen  = computed(() => !!page.props.canPreviewRoles)
const buildings = computed(() => page.props.previewBuildings ?? [])

const show  = ref(false)
const roles = ref([])
const form  = ref({ role: '', building_id: '' })

async function openPicker() {
    show.value = true
    if (roles.value.length === 0) {
        try {
            roles.value = await (await fetch(route('manage.preview.options'))).json()
        } catch { roles.value = [] }
    }
    form.value.role = preview.value?.role ?? ''
}

function start() {
    if (!form.value.role) return
    router.post(route('manage.preview.store'), {
        role: form.value.role,
        building_id: form.value.building_id || null,
    }, { onSuccess: () => { show.value = false } })
}

function exit() {
    router.post(route('manage.preview.exit'))
}

// Friendly display names for known role slugs; fall back to title-casing.
const roleLabels = {
    'super-admin':         'Super Admin',
    'ceo':                 'CEO',
    'manager':             'Manager',
    'accountant':          'Accountant',
    'head-of-procurement': 'Procurement Officer',
    'quality-control':     'Quality Control',
    'receptionist':        'Receptionist',
    'staff':               'Staff',
}
const cap = (s) => roleLabels[s] ?? (s ?? '').replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

defineExpose({ openPicker })
</script>

<template>
    <!-- Banner -->
    <div v-if="preview"
         class="shrink-0 flex items-center justify-center gap-2.5 bg-indigo-600 text-white text-xs sm:text-sm font-medium py-2 px-4">
        <Eye class="w-4 h-4 shrink-0" />
        <span class="truncate">
            Viewing as <strong>{{ cap(preview.role) }}</strong>
            <span v-if="preview.building"> @ {{ preview.building }}</span>
            <span class="hidden sm:inline opacity-80"> · read-only</span>
        </span>
        <button @click="openPicker" class="hidden sm:inline shrink-0 underline underline-offset-2 opacity-90 hover:opacity-100">Change</button>
        <button @click="exit"
                class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/15 hover:bg-white/25 transition-colors">
            <X class="w-3 h-3" /> Exit
        </button>
    </div>

    <!-- Picker -->
    <Modal :show="show" max-width="sm" @close="show = false">
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <Eye class="w-4 h-4 text-gray-400" /> View as role
                </h3>
                <button @click="show = false" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Browse the app exactly as another role sees it. Preview is read-only - you can look, but not change anything.
            </p>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Role</label>
                    <select v-model="form.role"
                            class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                        <option value="" disabled>Choose a role…</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ cap(r) }}</option>
                    </select>
                </div>

                <div v-if="buildings.length">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <Building2 class="w-3.5 h-3.5" /> Building <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <select v-model="form.building_id"
                            class="w-full px-3 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-900 dark:focus:ring-white">
                        <option value="">All buildings</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">Pick a building to see the scoped view staff at that property get.</p>
                </div>
            </div>

            <button @click="start" :disabled="!form.role"
                    class="mt-5 w-full py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:opacity-90 transition-all disabled:opacity-40">
                Start preview
            </button>
        </div>
    </Modal>
</template>
