<script setup lang="ts">
import type { UiBlock } from '../composables/useFlowSerializer'
import TextBlockEditor from './properties/TextBlockEditor.vue'
import InputBlockEditor from './properties/InputBlockEditor.vue'
import ButtonsBlockEditor from './properties/ButtonsBlockEditor.vue'

const props = defineProps<{ selectedBlock: UiBlock | null; variables: string[] }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const forward = (patch: { content?: any; config?: any }) => {
    if (!props.selectedBlock) return
    emit('update', patch)
}
</script>

<template>
    <div class="properties">
        <h3>Properties</h3>

        <div v-if="!selectedBlock" class="empty">Выберите блок</div>

        <div v-else>
            <div class="field meta">
                <label>ID блока</label>
                <input :value="selectedBlock.id" disabled />
            </div>

            <TextBlockEditor
                v-if="selectedBlock.type === 'text'"
                :block="selectedBlock"
                :variables="variables"
                @update="forward"
            />
            <InputBlockEditor v-else-if="selectedBlock.type === 'input'" :block="selectedBlock" @update="forward" />
            <ButtonsBlockEditor v-else-if="selectedBlock.type === 'button'" :block="selectedBlock" @update="forward" />
        </div>
    </div>
</template>

<style scoped>
.properties { width: 300px; background: #f8fafc; border-left: 1px solid #e2e8f0; padding: 16px; overflow-y: auto; }
.empty { color: #94a3b8; font-style: italic; }
.field.meta { margin-bottom: 16px; }
.field.meta label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: #64748b; }
.field.meta input { width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; box-sizing: border-box; background: #f1f5f9; color: #94a3b8; }
</style>
