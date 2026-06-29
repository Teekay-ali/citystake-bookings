<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import {
    Bold, Italic, List, ListOrdered, Heading3, Link as LinkIcon, Undo, Redo,
} from 'lucide-vue-next'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Describe what changed and why it matters...' },
})
const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [3] } }),
        Link.configure({ openOnClick: false, autolink: true }),
    ],
    editorProps: {
        attributes: {
            class: 'rt-content focus:outline-none min-h-[140px] px-3 py-2.5',
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML()
        emit('update:modelValue', html === '<p></p>' ? '' : html)
    },
})

// Keep editor in sync when the parent resets the form (e.g. after save)
watch(() => props.modelValue, (val) => {
    if (editor.value && val !== editor.value.getHTML()) {
        editor.value.commands.setContent(val || '', false)
    }
})

onBeforeUnmount(() => editor.value?.destroy())

function setLink() {
    const prev = editor.value.getAttributes('link').href
    const url = window.prompt('Link URL', prev ?? 'https://')
    if (url === null) return
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
        return
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const btn = (active) => [
    'p-1.5 rounded-md transition-colors',
    active
        ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white'
        : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
]
</script>

<template>
    <div class="border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 overflow-hidden focus-within:ring-2 focus-within:ring-gray-900 dark:focus-within:ring-white transition-all">
        <!-- Toolbar -->
        <div v-if="editor" class="flex items-center gap-0.5 px-2 py-1.5 border-b border-gray-100 dark:border-gray-800">
            <button type="button" :class="btn(editor.isActive('bold'))" @click="editor.chain().focus().toggleBold().run()" title="Bold"><Bold class="w-4 h-4" /></button>
            <button type="button" :class="btn(editor.isActive('italic'))" @click="editor.chain().focus().toggleItalic().run()" title="Italic"><Italic class="w-4 h-4" /></button>
            <button type="button" :class="btn(editor.isActive('heading', { level: 3 }))" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading"><Heading3 class="w-4 h-4" /></button>
            <span class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1" />
            <button type="button" :class="btn(editor.isActive('bulletList'))" @click="editor.chain().focus().toggleBulletList().run()" title="Bullet list"><List class="w-4 h-4" /></button>
            <button type="button" :class="btn(editor.isActive('orderedList'))" @click="editor.chain().focus().toggleOrderedList().run()" title="Numbered list"><ListOrdered class="w-4 h-4" /></button>
            <button type="button" :class="btn(editor.isActive('link'))" @click="setLink" title="Link"><LinkIcon class="w-4 h-4" /></button>
            <span class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1" />
            <button type="button" :class="btn(false)" @click="editor.chain().focus().undo().run()" title="Undo"><Undo class="w-4 h-4" /></button>
            <button type="button" :class="btn(false)" @click="editor.chain().focus().redo().run()" title="Redo"><Redo class="w-4 h-4" /></button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
