<script setup lang="ts">
import { ref, computed } from 'vue'
import { Handle, Position } from '@vue-flow/core'
import BlockRenderer from '../BlockRenderer.vue'
import { getBlockOutputs } from '@/blocks'
import type { FlowBlockType, BlockContent, BlockConfig } from '@/types/flow'

interface UiBlock {
    id: string
    type: FlowBlockType
    content?: BlockContent
    config?: BlockConfig
}

const LIBRARY_DRAG_TYPE = 'application/x-flow-block-type'

const props = defineProps<{
    id: string
    data: { title: string; blocks: UiBlock[] }
    selectedBlockId?: string | null
    /** Пробрасывается VueFlow через slot-пропсы кастомной ноды (v-bind="p"
     * в App.vue) — используется только для визуальной рамки выбранной
     * группы (нужна с Фазы 6, где выбор группы влияет на Delete/Ctrl+D). */
    selected?: boolean
}>()

const emit = defineEmits<{
    'update-title': [groupId: string, title: string]
    'select-block': [groupId: string, blockId: string]
    'reorder-blocks': [groupId: string, blockIds: string[]]
    /** Блок нового типа перетащен из библиотеки блоков и должен быть
     * вставлен в эту группу на позицию index. */
    'insert-block': [groupId: string, blockType: FlowBlockType, index: number]
}>()

// Число и подписи выходов группы определяются ПОСЛЕДНИМ блоком —
// именно от него логически идёт переход дальше по флоу — и берутся из
// реестра блоков (src/blocks), а не захардкожены под конкретный тип.
// Обычный блок (в т.ч. пустая группа) даёт один выход снизу группы;
// condition — два (True/False); в будущем так же заведётся любой другой
// блок с несколькими выходами, без правок этого компонента.
const lastBlock = computed(() => props.data.blocks[props.data.blocks.length - 1])
const outputs = computed(() => getBlockOutputs(lastBlock.value?.type, lastBlock.value?.config))

/** Равномерно распределяет N>1 выходов по ширине группы, оставляя отступы
 * по краям (первый выход — не у самого левого края, последний — не у
 * самого правого). */
const outputLeft = (index: number, total: number): string => `${((index + 1) / (total + 1)) * 100}%`

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

// --- Приём блоков ---
// Два разных источника drag'а различаются по наличию нашего кастомного
// MIME-типа в dataTransfer:
//  - из библиотеки блоков (Sidebar.vue)  -> LIBRARY_DRAG_TYPE присутствует -> вставка нового блока
//  - изнутри этой же группы (сортировка) -> LIBRARY_DRAG_TYPE отсутствует -> переупорядочивание существующих
const dragIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const isLibraryDrag = (event: DragEvent) => Boolean(event.dataTransfer?.types.includes(LIBRARY_DRAG_TYPE))

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

const onDrop = (index: number, event: DragEvent) => {
    if (isLibraryDrag(event)) {
        const blockType = event.dataTransfer!.getData(LIBRARY_DRAG_TYPE) as FlowBlockType
        emit('insert-block', props.id, blockType, index)
        onDragEnd()
        return
    }

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

// Зона в самом низу группы — позволяет вставить блок из библиотеки
// в конец списка (в т.ч. когда группа изначально пуста).
const onDropAtEnd = (event: DragEvent) => {
    if (!isLibraryDrag(event)) {
        onDragEnd()
        return
    }
    const blockType = event.dataTransfer!.getData(LIBRARY_DRAG_TYPE) as FlowBlockType
    emit('insert-block', props.id, blockType, props.data.blocks.length)
    onDragEnd()
}

// Ловит drop, случайно попавший на паддинг/заголовок группы, а не на
// конкретную зону (блок или drop-tail) — чтобы событие не всплыло на
// холст и не создало там отдельную новую группу поверх текущей.
const swallowDrop = () => {}
</script>

<template>
    <div class="group-node" :class="{ selected }" @dragover.prevent @drop.stop.prevent="swallowDrop">
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
                @drop.stop="onDrop(index, $event)"
                @dragend="onDragEnd"
            >
                <BlockRenderer
                    :block="block"
                    :selected="block.id === selectedBlockId"
                    @select="(blockId) => emit('select-block', id, blockId)"
                />
            </div>

            <div v-if="!data.blocks.length" class="empty-group">Группа пуста</div>

            <!-- Зона вставки в конец списка (или в пустую группу) -->
            <div
                class="drop-tail"
                :class="{ 'drag-over': dragOverIndex === data.blocks.length }"
                @dragenter.prevent="onDragEnter(data.blocks.length)"
                @dragover.prevent
                @drop.stop="onDropAtEnd"
                @dragend="onDragEnd"
            />
        </div>

        <Handle v-if="outputs.length <= 1" type="source" :position="Position.Bottom" />

        <template v-else>
            <Handle
                v-for="(output, index) in outputs"
                :key="output.handle ?? index"
                :id="output.handle ?? undefined"
                type="source"
                :position="Position.Bottom"
                :class="output.tone ? `handle-tone-${output.tone}` : undefined"
                :style="{ left: outputLeft(index, outputs.length) }"
            />
            <div class="output-labels">
                <span
                    v-for="(output, index) in outputs"
                    :key="'label-' + (output.handle ?? index)"
                    class="output-label"
                    :class="output.tone ? `label-tone-${output.tone}` : undefined"
                    :style="{ left: outputLeft(index, outputs.length) }"
                >{{ output.label }}</span>
            </div>
        </template>
    </div>
</template>

<style scoped>
.group-node {
    background: var(--color-surface);
    border: 2px solid var(--color-stroke);
    border-radius: var(--radius-lg);
    width: 220px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    font-family: var(--font-sans);
    transition: border-color 0.12s ease, box-shadow 0.12s ease;
}
.group-node.selected {
    border-color: var(--color-accent);
    box-shadow: var(--shadow-md);
}
.group-header {
    background: var(--color-surface-50);
    padding: 6px 10px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--color-text-muted);
    cursor: text;
    border-bottom: 1px solid var(--color-stroke);
}
.title-text { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.title-input {
    width: 100%;
    border: 1px solid var(--color-accent);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    padding: 2px 4px;
    font-weight: 700;
    text-transform: uppercase;
    background: var(--color-surface);
    color: var(--color-text);
}
.group-body { padding: var(--space-2); display: flex; flex-direction: column; gap: var(--space-1); min-height: 24px; }
.block-wrapper { cursor: grab; border-top: 2px solid transparent; }
.block-wrapper:active { cursor: grabbing; }
.block-wrapper.drag-over { border-top-color: var(--color-accent); }
.empty-group { font-size: var(--font-size-xs); color: var(--color-text-muted); font-style: italic; padding: 8px 4px; text-align: center; }
.drop-tail { height: 8px; border-radius: var(--radius-sm); }
.drop-tail.drag-over { background: color-mix(in oklch, var(--color-accent) 15%, transparent); outline: 2px dashed var(--color-accent); }

.output-labels {
    position: relative;
    height: 14px;
    margin-top: 2px;
}
.output-label {
    position: absolute;
    transform: translateX(-50%);
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
    color: var(--color-text-muted);
}
.output-label.label-tone-success { color: var(--color-success-text); }
.output-label.label-tone-error { color: var(--color-error-text); }
/* Handle — компонент @vue-flow/core, класс передаётся на его корневой
 * элемент через fallthrough — scoped-стили родителя достают до корня
 * дочернего компонента, как и раньше с .handle-false/.handle-true. */
.handle-tone-success { background: var(--color-success) !important; }
.handle-tone-error { background: var(--color-error) !important; }
</style>
