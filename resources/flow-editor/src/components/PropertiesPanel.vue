<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../composables/useFlowSerializer'
import { getBlockDefinition } from '@/blocks'

const props = defineProps<{ selectedBlock: UiBlock | null; variables: string[] }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const forward = (patch: { content?: any; config?: any }) => {
    if (!props.selectedBlock) return
    emit('update', patch)
}

// Редактор свойств берётся из реестра блоков — этот компонент больше не
// перечисляет типы блоков сам. Общий набор пропсов (block, variables)
// передаётся всем редакторам; те, что variables не объявляют (Input,
// Buttons), просто её не используют — лишний проп не мешает.
const editorComponent = computed(() => {
    if (!props.selectedBlock) return null
    return getBlockDefinition(props.selectedBlock.type).editorComponent ?? null
})
</script>

<template>
    <div class="properties">
        <h3>Характеристики</h3>

        <div v-if="!selectedBlock" class="empty">Выберите блок</div>

        <div v-else>
            <div class="field meta">
                <label>ID блока</label>
                <input :value="selectedBlock.id" disabled />
            </div>

            <component
                :is="editorComponent"
                v-if="editorComponent"
                :block="selectedBlock"
                :variables="variables"
                @update="forward"
            />
        </div>
    </div>
</template>

<style scoped>
.properties { width: 300px; background: var(--color-surface-50); border-left: 1px solid var(--color-stroke); padding: var(--space-4); overflow-y: auto; }
.empty { color: var(--color-text-muted); font-style: italic; }
.field.meta { margin-bottom: var(--space-4); }
.field.meta label { display: block; font-size: var(--font-size-sm); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: var(--color-text-muted); }
.field.meta input {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    box-sizing: border-box;
    background: var(--color-surface-100);
    color: var(--color-text-muted);
}
</style>
