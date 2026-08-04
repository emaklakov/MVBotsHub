<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'

import Sidebar from './components/Sidebar.vue'
import PropertiesPanel from './components/PropertiesPanel.vue'
import GroupNode from './components/group/GroupNode.vue'
import { useFlowApi } from './composables/useFlowApi'
import { useFlowSerializer, defaultGroupTitle, type GroupNodeData } from './composables/useFlowSerializer'
import type { FlowBlockType } from './types/flow'
import type { Node, Edge } from '@vue-flow/core'

const props = defineProps<{ botId: string; flowId: string }>()

const { getDraft, saveDraft, publish } = useFlowApi(props.botId, props.flowId)
const { toVueFlow, toSchema } = useFlowSerializer()

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const startGroupId = ref<string | null>(null)

const selectedGroupId = ref<string | null>(null)
const selectedBlockId = ref<string | null>(null)

const { onPaneClick } = useVueFlow()

// Клик по пустому месту холста снимает выбор блока.
onPaneClick(() => {
    selectedGroupId.value = null
    selectedBlockId.value = null
})

const selectedBlock = computed(() => {
    if (!selectedGroupId.value || !selectedBlockId.value) return null
    const node = nodes.value.find((n) => n.id === selectedGroupId.value)
    const data = node?.data as GroupNodeData | undefined
    return data?.blocks.find((b) => b.id === selectedBlockId.value) || null
})

const load = async () => {
    const draft = await getDraft()
    const result = toVueFlow(draft?.schema)
    nodes.value = result.nodes
    edges.value = result.edges
    startGroupId.value = draft?.schema?.start_group_id ?? null
}

const addBlock = (type: string) => {
    const groupId = `group_${Date.now()}`
    const blockId = `block_${Date.now()}`
    const blockType = type as FlowBlockType

    nodes.value.push({
        id: groupId,
        type: 'group',
        position: { x: 250, y: 250 },
        data: {
            title: defaultGroupTitle(blockType),
            blocks: [
                {
                    id: blockId,
                    type: blockType,
                    content: blockType === 'text' ? { translations: { ru: '', en: '' } } : blockType === 'button' ? { buttons: [] } : {},
                    config: blockType === 'input' ? { variable: '' } : {},
                },
            ],
        } satisfies GroupNodeData,
    })
    if (!startGroupId.value) startGroupId.value = groupId
}

const findGroupData = (groupId: string): GroupNodeData | undefined => {
    const node = nodes.value.find((n) => n.id === groupId)
    return node?.data as GroupNodeData | undefined
}

const updateGroupTitle = (groupId: string, title: string) => {
    const data = findGroupData(groupId)
    if (data) data.title = title
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
}

const updateSelectedBlock = (patch: { content?: any; config?: any }) => {
    if (!selectedGroupId.value || !selectedBlockId.value) return
    const data = findGroupData(selectedGroupId.value)
    const block = data?.blocks.find((b) => b.id === selectedBlockId.value)
    if (!block) return
    if (patch.content !== undefined) block.content = patch.content
    if (patch.config !== undefined) block.config = patch.config
}

const handleSave = async () => {
    await saveDraft(toSchema(nodes.value, edges.value, startGroupId.value))
    alert('Draft saved')
}

const handlePublish = async () => {
    await handleSave()
    await publish()
    alert('Published!')
}

onMounted(load)
</script>

<template>
    <div class="flow-editor">
        <div class="toolbar">
            <h2>Flow Editor</h2>
            <div class="actions">
                <button @click="handleSave">Save Draft</button>
                <button class="primary" @click="handlePublish">Publish</button>
            </div>
        </div>
        <div class="workspace">
            <Sidebar @add="addBlock" />
            <div class="canvas-wrapper">
                <VueFlow v-model:nodes="nodes" v-model:edges="edges" fit-view-on-init>
                    <Background pattern-color="#aaa" :gap="16" />
                    <Controls />
                    <template #node-group="p">
                        <GroupNode
                            v-bind="p"
                            :selected-block-id="selectedGroupId === p.id ? selectedBlockId : null"
                            @update-title="updateGroupTitle"
                            @select-block="selectBlock"
                            @reorder-blocks="reorderBlocks"
                        />
                    </template>
                </VueFlow>
            </div>
            <PropertiesPanel :selected-block="selectedBlock" @update="updateSelectedBlock" />
        </div>
    </div>
</template>

<style>
.flow-editor { display: flex; flex-direction: column; height: 100vh; font-family: system-ui, sans-serif; }
.toolbar { height: 56px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; }
.toolbar h2 { margin: 0; font-size: 18px; }
.actions button { margin-left: 8px; padding: 8px 16px; border: 1px solid #cbd5e1; background: white; border-radius: 6px; cursor: pointer; font-size: 14px; }
.actions button.primary { background: #3b82f6; color: white; border-color: #3b82f6; }
.workspace { display: flex; flex: 1; overflow: hidden; }
.canvas-wrapper { flex: 1; position: relative; }
.vue-flow { height: 100%; }
</style>
