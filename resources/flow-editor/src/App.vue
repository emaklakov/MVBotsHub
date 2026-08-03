<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'

import Sidebar from './components/Sidebar.vue'
import PropertiesPanel from './components/PropertiesPanel.vue'
import TextNode from './components/nodes/TextNode.vue'
import InputNode from './components/nodes/InputNode.vue'
import ButtonNode from './components/nodes/ButtonNode.vue'
import { useFlowApi } from './composables/useFlowApi'
import { useFlowSerializer } from './composables/useFlowSerializer'
import type { Node, Edge } from '@vue-flow/core'

const props = defineProps<{ botId: string; flowId: string }>()

const { getDraft, saveDraft, publish } = useFlowApi(props.botId, props.flowId)
const { toVueFlow, toSchema } = useFlowSerializer()

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const selectedNode = ref<Node | null>(null)
const startBlockId = ref<string | null>(null)

const { findNode, onNodeClick } = useVueFlow()

onNodeClick(({ node }) => {
    selectedNode.value = findNode(node.id) || null
})

const load = async () => {
    const draft = await getDraft()
    const result = toVueFlow(draft.schema)
    nodes.value = result.nodes
    edges.value = result.edges
    startBlockId.value = draft.schema.start_block_id
}

const addBlock = (type: string) => {
    const id = `block_${Date.now()}`
    nodes.value.push({
        id,
        type,
        position: { x: 250, y: 250 },
        data: {
            content: type === 'text' ? { translations: { ru: '', en: '' } } : type === 'button' ? { buttons: [] } : {},
            config: type === 'input' ? { variable: '' } : {},
        },
    })
    if (!startBlockId.value) startBlockId.value = id
}

const updateNode = (nodeId: string, data: any) => {
    const node = nodes.value.find((n) => n.id === nodeId)
    if (node) node.data = data
}

const handleSave = async () => {
    await saveDraft(toSchema(nodes.value, edges.value, startBlockId.value))
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
                    <template #node-text="p"><TextNode v-bind="p" /></template>
                    <template #node-input="p"><InputNode v-bind="p" /></template>
                    <template #node-button="p"><ButtonNode v-bind="p" /></template>
                </VueFlow>
            </div>
            <PropertiesPanel :selected-node="selectedNode" @update="updateNode" />
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
