<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../composables/useFlowSerializer'

const props = defineProps<{ selectedBlock: UiBlock | null }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const updateData = (patch: { content?: any; config?: any }) => {
  if (!props.selectedBlock) return
  emit('update', patch)
}

const translations = computed({
  get: () => props.selectedBlock?.content?.translations || { ru: '', en: '' },
  set: (val) => updateData({ content: { ...props.selectedBlock?.content, translations: val } }),
})

const variable = computed({
  get: () => props.selectedBlock?.config?.variable || '',
  set: (val) => updateData({ config: { ...props.selectedBlock?.config, variable: val } }),
})

const buttons = computed({
  get: () => props.selectedBlock?.content?.buttons?.join('\n') || '',
  set: (val) => updateData({ content: { ...props.selectedBlock?.content, buttons: val.split('\n').filter(Boolean) } }),
})

const isText = computed(() => props.selectedBlock?.type === 'text')
const isInput = computed(() => props.selectedBlock?.type === 'input')
const isButton = computed(() => props.selectedBlock?.type === 'button')
</script>

<template>
  <div class="properties">
    <h3>Properties</h3>
    <div v-if="!selectedBlock" class="empty">Выберите блок</div>
    <div v-else>
      <div class="field"><label>ID блока</label><input :value="selectedBlock.id" disabled /></div>

      <template v-if="isText">
        <div class="field"><label>Text (RU)</label><textarea v-model="translations.ru" rows="3" /></div>
        <div class="field"><label>Text (EN)</label><textarea v-model="translations.en" rows="3" /></div>
      </template>

      <template v-if="isInput">
        <div class="field"><label>Variable</label><input v-model="variable" placeholder="user_name" /></div>
      </template>

      <template v-if="isButton">
        <div class="field"><label>Buttons (one per line)</label><textarea v-model="buttons" rows="4" /></div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.properties { width: 280px; background: #f8fafc; border-left: 1px solid #e2e8f0; padding: 16px; }
.empty { color: #94a3b8; font-style: italic; }
.field { margin-bottom: 12px; }
label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: #64748b; }
input, textarea { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; }
</style>
