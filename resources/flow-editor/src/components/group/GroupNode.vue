<script setup lang="ts">
import { ref } from 'vue'
import { Handle, Position } from '@vue-flow/core'
import BlockRenderer from '../BlockRenderer.vue'
import type { FlowBlockType, BlockContent, BlockConfig } from '@/types/flow'

interface UiBlock {
    id: string
    type: FlowBlockType
    content?: BlockContent
    config?: BlockConfig
}

const props = defineProps<{
    id: string
    data: { title: string; blocks: UiBlock[] }
    selectedBlockId?: string | null
}>()

const emit = defineEmits<{
    'update-title': [groupId: string, title: string]
    'select-block': [groupId: string, blockId: string]
    'reorder-blocks': [groupId: string, blockIds: string[]]
}>()

// --- Редактирование заголовка группы по двойному клику ---
const editingTitle = ref(false)
const titleDraft = ref(props.data.title)

const startEditTitle = () => {
    titleDraft.value = props.data.title
    editingTitle.value = true
}

const commitTitle = () => {
    editingTitle.value = false
    const value = titleDraft.value.trim()
    if (value && value !== props.data.title) {
        emit('update-title', props.id, value)
    }
}

const cancelEditTitle = () => {
    editingTitle.value = false
}

// --- Drag-переупорядочивание блоков внутри группы ---
// Лёгкая реализация на нативном HTML5 Drag & Drop, без сторонних
// библиотек — этого достаточно для сортировки списка из нескольких
// элементов внутри одной карточки.
const dragIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const onDragStart = (index: number) => {
    dragIndex.value = index
}

const onDragEnter = (index: number) => {
    dragOverIndex.value = index
}

const onDragEnd = () => {
    dragIndex.value = null
    dragOverIndex.value = null
}

const onDrop = (index: number) => {
    if (dragIndex.value === null || dragIndex.value === index) {
        onDragEnd()
        return
    }
    const ids = props.data.blocks.map((b) => b.id)
    const [moved] = ids.splice(dragIndex.value, 1)
    ids.splice(index, 0, moved)
    emit('reorder-blocks', props.id, ids)
    onDragEnd()
}
</script>

<template>
    <div class="group-node">
        <Handle type="target" :position="Position.Top" />

        <div class="group-header" @dblclick="startEditTitle">
            <input
                v-if="editingTitle"
                v-model="titleDraft"
                class="title-input"
                autofocus
                @blur="commitTitle"
                @keyup.enter="commitTitle"
                @keyup.esc="cancelEditTitle"
                @click.stop
            />
            <span v-else class="title-text" title="Двойной клик, чтобы переименовать">{{ data.title }}</span>
        </div>

        <div class="group-body">
            <div
                v-for="(block, index) in data.blocks"
                :key="block.id"
                class="block-wrapper"
                :class="{ 'drag-over': dragOverIndex === index && dragIndex !== index }"
                draggable="true"
                @dragstart="onDragStart(index)"
                @dragenter.prevent="onDragEnter(index)"
                @dragover.prevent
                @drop="onDrop(index)"
                @dragend="onDragEnd"
            >
                <BlockRenderer
                    :block="block"
                    :selected="block.id === selectedBlockId"
                    @select="(blockId) => emit('select-block', id, blockId)"
                />
            </div>

            <div v-if="!data.blocks.length" class="empty-group">Группа пуста</div>
        </div>

        <Handle type="source" :position="Position.Bottom" />
    </div>
</template>

<style scoped>
.group-node {
    background: white;
    border: 2px solid #cbd5e1;
    border-radius: 10px;
    width: 220px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    font-family: system-ui, sans-serif;
}
.group-header {
    background: #f1f5f9;
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #475569;
    cursor: text;
    border-bottom: 1px solid #e2e8f0;
}
.title-text { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.title-input {
    width: 100%;
    border: 1px solid #3b82f6;
    border-radius: 4px;
    font-size: 11px;
    padding: 2px 4px;
    font-weight: 700;
    text-transform: uppercase;
}
.group-body { padding: 6px; display: flex; flex-direction: column; gap: 4px; min-height: 24px; }
.block-wrapper { cursor: grab; border-top: 2px solid transparent; }
.block-wrapper:active { cursor: grabbing; }
.block-wrapper.drag-over { border-top-color: #3b82f6; }
.empty-group { font-size: 11px; color: #94a3b8; font-style: italic; padding: 8px 4px; text-align: center; }
</style>
