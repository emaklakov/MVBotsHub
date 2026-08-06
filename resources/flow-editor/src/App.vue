<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/minimap/dist/style.css'

import Sidebar from './components/Sidebar.vue'
import PropertiesPanel from './components/PropertiesPanel.vue'
import GroupNode from './components/group/GroupNode.vue'
import ChatPreview from './components/preview/ChatPreview.vue'
import { useFlowApi } from './composables/useFlowApi'
import { useHistoryStack } from './composables/useHistoryStack'
import {
    useFlowSerializer,
    defaultGroupTitle,
    defaultBlockContent,
    defaultBlockConfig,
    collectVariables,
    type GroupNodeData,
    type UiBlock,
} from './composables/useFlowSerializer'
import type { FlowBlockType } from './types/flow'
import type { Node, Edge, XYPosition } from '@vue-flow/core'

const props = defineProps<{ botId: string; flowId: string }>()

const { getDraft, saveDraft, publish } = useFlowApi(props.botId, props.flowId)
const { toVueFlow, toSchema } = useFlowSerializer()

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const startGroupId = ref<string | null>(null)

const selectedGroupId = ref<string | null>(null)
const selectedBlockId = ref<string | null>(null)

const { onPaneClick, onConnect, addEdges, project, onNodeDragStop, getSelectedNodes, removeNodes } = useVueFlow()
const canvasWrapper = ref<HTMLElement | null>(null)

// Цвет точек фона холста передаётся VueFlow как атрибут SVG fill, а не
// через наш CSS (var(--color-stroke) там не факт что резолвится надёжно
// во всех браузерах) — поэтому просто следим за системной темой напрямую
// и отдаём литеральный oklch, совпадающий по смыслу с --color-stroke.
const prefersDarkMedia = window.matchMedia('(prefers-color-scheme: dark)')
const patternColorFor = (isDark: boolean) => (isDark ? 'oklch(0.85 0 0 / 30%)' : 'oklch(0.25 0 0 / 15%)')
const backgroundPatternColor = ref(patternColorFor(prefersDarkMedia.matches))
const handlePrefersDarkChange = (e: MediaQueryListEvent) => {
    backgroundPatternColor.value = patternColorFor(e.matches)
}
prefersDarkMedia.addEventListener('change', handlePrefersDarkChange)

// --- Undo/redo -------------------------------------------------------
// Снапшот — только то, что реально описывает флоу (без служебных полей,
// которые VueFlow может дописывать в объекты нод/рёбер во время работы).
interface HistorySnapshot {
    nodes: Array<{ id: string; type: string; position: XYPosition; data: GroupNodeData }>
    edges: Array<{ id: string; source: string; target: string; sourceHandle?: string | null }>
    startGroupId: string | null
}

const getSnapshot = (): HistorySnapshot => ({
    nodes: nodes.value.map((n) => ({
        id: n.id,
        type: (n.type as string) ?? 'group',
        position: { x: n.position.x, y: n.position.y },
        data: n.data as GroupNodeData,
    })),
    edges: edges.value.map((e) => ({ id: e.id, source: e.source, target: e.target, sourceHandle: e.sourceHandle })),
    startGroupId: startGroupId.value,
})

const applySnapshot = (snap: HistorySnapshot) => {
    nodes.value = snap.nodes.map((n) => ({ id: n.id, type: n.type, position: { ...n.position }, data: n.data }))
    edges.value = snap.edges.map((e) => ({ ...e }))
    startGroupId.value = snap.startGroupId
}

const history = useHistoryStack(getSnapshot, applySnapshot, { maxEntries: 50, debounceMs: 500 })

// --- Автосейв ---------------------------------------------------------
// Заменяет ручную кнопку "Save Draft": любое изменение планирует сохранение
// с дебаунсом; статус показывается в тулбаре.
type SaveStatus = 'saved' | 'saving' | 'dirty'
const saveStatus = ref<SaveStatus>('saved')
let autosaveTimer: ReturnType<typeof setTimeout> | null = null
let isLoading = true

const persistNow = async () => {
    saveStatus.value = 'saving'
    try {
        await saveDraft(toSchema(nodes.value, edges.value, startGroupId.value))
        saveStatus.value = 'saved'
    } catch (e) {
        console.error('Autosave failed', e)
        saveStatus.value = 'dirty'
    }
}

const scheduleAutosave = () => {
    if (isLoading) return
    saveStatus.value = 'dirty'
    if (autosaveTimer) clearTimeout(autosaveTimer)
    autosaveTimer = setTimeout(() => {
        autosaveTimer = null
        persistNow()
    }, 800)
}

/** Единая точка входа для любой значимой правки флоу: пишет шаг в
 * undo-историю и планирует автосохранение. debounceHistory — для
 * непрерывных изменений (ввод текста), чтобы не плодить шаг отмены
 * на каждый символ. */
const recordChange = (debounceHistory = false) => {
    history.commit(debounceHistory)
    scheduleAutosave()
}

const handleUndo = () => {
    history.undo()
    scheduleAutosave()
}

const handleRedo = () => {
    history.redo()
    scheduleAutosave()
}

// --- Выбор / клики -----------------------------------------------------
onPaneClick(() => {
    selectedGroupId.value = null
    selectedBlockId.value = null
})

// Ручное соединение хендлов на холсте (drag от Handle к Handle) само по
// себе не добавляет ребро в модель — VueFlow только эмитит событие connect,
// применить его нужно явно.
onConnect((connection) => {
    addEdges([connection])
    recordChange()
})

// Перемещение группы мышью — фиксируем шаг истории только по окончании
// перетаскивания, а не на каждый кадр движения.
onNodeDragStop(() => {
    recordChange()
})

const selectedBlock = computed(() => {
    if (!selectedGroupId.value || !selectedBlockId.value) return null
    const node = nodes.value.find((n) => n.id === selectedGroupId.value)
    const data = node?.data as GroupNodeData | undefined
    return data?.blocks.find((b) => b.id === selectedBlockId.value) || null
})

/** Все переменные, которые где-либо в флоу собираются в input-блоках —
 * предлагаются для вставки в текст через TextBlockEditor. */
const knownVariables = computed(() => collectVariables(nodes.value))

// --- Тест-режим (превью диалога) ---------------------------------------
// Схема для превью считается лениво — только пока панель открыта, чтобы
// не пересчитывать её на каждое изменение блока без необходимости.
// Открытие панели — это снимок текущего состояния: правки в редакторе
// во время открытого превью не подтягиваются на лету (см. ChatPreview.vue).
const showPreview = ref(false)
const previewSchema = computed(() => (showPreview.value ? toSchema(nodes.value, edges.value, startGroupId.value) : null))
const togglePreview = () => {
    showPreview.value = !showPreview.value
}

const load = async () => {
    const draft = await getDraft()
    const result = toVueFlow(draft?.schema)
    nodes.value = result.nodes
    edges.value = result.edges
    startGroupId.value = draft?.schema?.start_group_id ?? null

    history.reset()
    isLoading = false
}

/** Создаёт новую группу с одним блоком заданного типа в указанной позиции холста. */
const createGroupWithBlock = (type: FlowBlockType, position: XYPosition): string => {
    const groupId = `group_${Date.now()}`
    const blockId = `block_${Date.now()}`

    nodes.value.push({
        id: groupId,
        type: 'group',
        position,
        data: {
            title: defaultGroupTitle(type),
            blocks: [{ id: blockId, type, content: defaultBlockContent(type), config: defaultBlockConfig(type) }],
        } satisfies GroupNodeData,
    })
    if (!startGroupId.value) startGroupId.value = groupId
    recordChange()
    return groupId
}

// Клик по блоку в библиотеке (Sidebar) — старый способ добавления,
// оставлен как доступная с клавиатуры/тача альтернатива drag'у.
const addBlock = (type: string) => {
    createGroupWithBlock(type as FlowBlockType, { x: 250, y: 250 })
}

const findGroupData = (groupId: string): GroupNodeData | undefined => {
    const node = nodes.value.find((n) => n.id === groupId)
    return node?.data as GroupNodeData | undefined
}

const updateGroupTitle = (groupId: string, title: string) => {
    const data = findGroupData(groupId)
    if (!data) return
    data.title = title
    recordChange()
}

const selectBlock = (groupId: string, blockId: string) => {
    selectedGroupId.value = groupId
    selectedBlockId.value = blockId
}

const reorderBlocks = (groupId: string, blockIds: string[]) => {
    const data = findGroupData(groupId)
    if (!data) return
    const byId = new Map(data.blocks.map((b) => [b.id, b]))
    data.blocks = blockIds.map((id) => byId.get(id)!).filter(Boolean)
    recordChange()
}

/** Блок из библиотеки перетащен прямо в существующую группу — вставляем на нужную позицию. */
const insertBlockIntoGroup = (groupId: string, type: FlowBlockType, index: number) => {
    const data = findGroupData(groupId)
    if (!data) return
    const blockId = `block_${Date.now()}`
    data.blocks.splice(index, 0, {
        id: blockId,
        type,
        content: defaultBlockContent(type),
        config: defaultBlockConfig(type),
    })
    recordChange()
}

const updateSelectedBlock = (patch: { content?: any; config?: any }) => {
    if (!selectedGroupId.value || !selectedBlockId.value) return
    const data = findGroupData(selectedGroupId.value)
    const block = data?.blocks.find((b) => b.id === selectedBlockId.value)
    if (!block) return
    if (patch.content !== undefined) block.content = patch.content
    if (patch.config !== undefined) block.config = patch.config
    // Правки в панели свойств обычно означают печать текста — дебаунсим
    // шаг истории, иначе каждый символ стал бы отдельным undo-шагом.
    recordChange(true)
}

// --- Drag&drop из библиотеки на пустой холст ---------------------------
const onCanvasDragOver = (event: DragEvent) => {
    if (event.dataTransfer?.types.includes('application/x-flow-block-type')) {
        event.preventDefault()
    }
}

const onCanvasDrop = (event: DragEvent) => {
    const type = event.dataTransfer?.getData('application/x-flow-block-type')
    if (!type || !canvasWrapper.value) return

    const bounds = canvasWrapper.value.getBoundingClientRect()
    const position = project({ x: event.clientX - bounds.left, y: event.clientY - bounds.top })
    createGroupWithBlock(type as FlowBlockType, position)
}

// --- Дублирование группы (Ctrl/Cmd+D) и удаление с подтверждением (Delete/Backspace) ---
// Встроенное удаление VueFlow по клавише отключено на самом <VueFlow>
// (:delete-key-code="null") — обрабатываем это сами, чтобы спросить
// подтверждение, если в группе есть блоки.
const isEditableElementFocused = (): boolean => {
    const active = document.activeElement
    return active instanceof HTMLElement && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)
}

const duplicateGroup = (groupId: string): string | null => {
    const original = nodes.value.find((n) => n.id === groupId)
    if (!original) return null
    const data = original.data as GroupNodeData

    const newBlocks: UiBlock[] = data.blocks.map((b) => ({
        ...b,
        id: `block_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
        content: b.content ? JSON.parse(JSON.stringify(b.content)) : b.content,
        config: b.config ? JSON.parse(JSON.stringify(b.config)) : b.config,
    }))

    const newGroupId = `group_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`
    nodes.value.push({
        id: newGroupId,
        type: 'group',
        position: { x: original.position.x + 40, y: original.position.y + 40 },
        data: { title: `${data.title} (копия)`, blocks: newBlocks } satisfies GroupNodeData,
    })
    return newGroupId
}

/** Цели для удаления/дублирования: обычно это выбор VueFlow (клик по
 * заголовку/фону группы), но клик по конкретному блоку (BlockRenderer)
 * останавливает всплытие ради панели свойств и не долетает до VueFlow —
 * в этом случае используем selectedGroupId как запасной вариант, чтобы
 * Delete/Ctrl+D работали сразу после выбора блока, а не только группы. */
const resolveDeletionTargets = (): Node[] => {
    const fromVueFlow = getSelectedNodes.value
    if (fromVueFlow.length) return fromVueFlow
    if (!selectedGroupId.value) return []
    const node = nodes.value.find((n) => n.id === selectedGroupId.value)
    return node ? [node] : []
}

const handleDeleteSelected = () => {
    const selected = resolveDeletionTargets()
    if (!selected.length) return

    const hasBlocks = selected.some((n) => ((n.data as GroupNodeData | undefined)?.blocks?.length ?? 0) > 0)
    if (hasBlocks) {
        const label = selected.length > 1 ? 'выбранные группы' : 'выбранную группу'
        if (!confirm(`Удалить ${label} со всем содержимым? Это действие можно отменить через Ctrl+Z.`)) {
            return
        }
    }

    const removedIds = new Set(selected.map((n) => n.id))
    removeNodes(selected)
    if (selectedGroupId.value && removedIds.has(selectedGroupId.value)) {
        selectedGroupId.value = null
        selectedBlockId.value = null
    }
    recordChange()
}

const handleDuplicateSelected = () => {
    const selected = resolveDeletionTargets()
    if (!selected.length) return
    selected.forEach((n) => duplicateGroup(n.id))
    recordChange()
}

const handleGlobalKeydown = (event: KeyboardEvent) => {
    if (isEditableElementFocused()) return

    const key = event.key.toLowerCase()
    const isUndoRedoModifier = event.ctrlKey || event.metaKey

    if (isUndoRedoModifier && key === 'z' && event.shiftKey) {
        event.preventDefault()
        handleRedo()
        return
    }
    if (isUndoRedoModifier && key === 'z') {
        event.preventDefault()
        handleUndo()
        return
    }
    if (isUndoRedoModifier && key === 'y') {
        event.preventDefault()
        handleRedo()
        return
    }
    if (isUndoRedoModifier && key === 'd') {
        event.preventDefault()
        handleDuplicateSelected()
        return
    }
    if (key === 'delete' || key === 'backspace') {
        handleDeleteSelected()
    }
}

const handlePublish = async () => {
    if (autosaveTimer) {
        clearTimeout(autosaveTimer)
        autosaveTimer = null
    }
    await persistNow()
    await publish()
    alert('Опубликовано!')
}

onMounted(() => {
    load()
    window.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown)
    prefersDarkMedia.removeEventListener('change', handlePrefersDarkChange)
    if (autosaveTimer) clearTimeout(autosaveTimer)
})
</script>

<template>
    <div class="flow-editor">
        <div class="toolbar">
            <div class="history-controls">
                <button title="Отменить (Ctrl+Z)" :disabled="!history.canUndo.value" @click="handleUndo">↶ Отменить (Ctrl+Z)</button>
                <button title="Повторить (Ctrl+Shift+Z)" :disabled="!history.canRedo.value" @click="handleRedo">↷ Повторить (Ctrl+Shift+Z)</button>
            </div>
            <div class="save-status">
                <span v-if="saveStatus === 'saved'" class="status-saved">✓ Сохранено</span>
                <span v-else-if="saveStatus === 'saving'" class="status-saving">Сохраняем…</span>
                <span v-else class="status-dirty">Есть несохранённые изменения</span>
            </div>
            <div class="actions">
                <button @click="togglePreview">{{ showPreview ? '✕ Закрыть тест' : '▶ Тест' }}</button>
                <button class="primary" @click="handlePublish">Опубликовать</button>
            </div>
        </div>
        <div class="workspace">
            <Sidebar @add="addBlock" />
            <div ref="canvasWrapper" class="canvas-wrapper" @dragover="onCanvasDragOver" @drop="onCanvasDrop">
                <VueFlow v-model:nodes="nodes" v-model:edges="edges" fit-view-on-init :delete-key-code="null">
                    <Background :pattern-color="backgroundPatternColor" :gap="16" />
                    <Controls />
                    <MiniMap pannable zoomable />
                    <template #node-group="p">
                        <GroupNode
                            v-bind="p"
                            :selected-block-id="selectedGroupId === p.id ? selectedBlockId : null"
                            @update-title="updateGroupTitle"
                            @select-block="selectBlock"
                            @reorder-blocks="reorderBlocks"
                            @insert-block="insertBlockIntoGroup"
                        />
                    </template>
                </VueFlow>
            </div>
            <PropertiesPanel :selected-block="selectedBlock" :variables="knownVariables" @update="updateSelectedBlock" />
        </div>

        <ChatPreview v-if="showPreview && previewSchema" :schema="previewSchema" @close="showPreview = false" />
    </div>
</template>

<style>
.flow-editor { display: flex; flex-direction: column; height: 100vh; font-family: var(--font-sans); background: var(--color-body); }
.toolbar { height: 56px; background: var(--color-surface); border-bottom: 1px solid var(--color-stroke); display: flex; align-items: center; gap: var(--space-4); }
.history-controls { display: flex; gap: var(--space-1); }
.history-controls button {
    padding: 6px 10px;
    border: 1px solid var(--color-stroke);
    background: var(--color-surface);
    color: var(--color-text);
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: var(--font-size-base);
}
.history-controls button:hover:not(:disabled) { background: var(--color-surface-50); }
.history-controls button:disabled { opacity: 0.4; cursor: default; }
.save-status { flex: 1; font-size: var(--font-size-sm); }
.status-saved { color: var(--color-success-text); }
.status-saving { color: var(--color-text-muted); }
.status-dirty { color: var(--color-warning-text); }
.actions button {
    margin-left: var(--space-2);
    padding: 8px 16px;
    border: 1px solid var(--color-stroke);
    background: var(--color-surface);
    color: var(--color-text);
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: var(--font-size-md);
}
.actions button:hover { background: var(--color-surface-50); }
.actions button.primary { background: var(--color-primary); color: var(--color-primary-text); border-color: var(--color-primary); }
.actions button.primary:hover { opacity: 0.9; }
.workspace { display: flex; flex: 1; overflow: hidden; }
.canvas-wrapper { flex: 1; position: relative; background: var(--color-body); }
.vue-flow { height: 100%; }
</style>
