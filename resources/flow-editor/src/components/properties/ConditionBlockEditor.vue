<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import type { ConditionOperator } from '@/types/flow'
import type { ChannelProfile } from '@/channels'

// `channel` объявлен, но пока не используется этим редактором — см.
// аналогичный комментарий про ChannelProfile в ButtonsBlockEditor.vue.
const props = defineProps<{ block: UiBlock; variables: string[]; channel?: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { config?: any }] }>()

const variable = computed({
    get: () => props.block.config?.conditionVariable || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, conditionVariable: val } }),
})

const operator = computed({
    get: () => props.block.config?.conditionOperator || '==',
    set: (val: ConditionOperator) => emit('update', { config: { ...props.block.config, conditionOperator: val } }),
})

const value = computed({
    get: () => props.block.config?.conditionValue || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, conditionValue: val } }),
})

const needsValue = computed(() => operator.value !== 'is_set' && operator.value !== 'is_empty')
</script>

<template>
    <div class="condition-editor">
        <div class="field">
            <label>Переменная</label>
            <select v-model="variable">
                <option value="">— выберите переменную —</option>
                <option v-for="v in variables" :key="v" :value="v">{{ v }}</option>
            </select>
            <p v-if="!variables.length" class="field-hint">
                Переменных пока нет — добавьте блок «Вопрос» и задайте ему имя переменной.
            </p>
        </div>

        <div class="field">
            <label>Условие</label>
            <select v-model="operator">
                <option value="==">равно</option>
                <option value="!=">не равно</option>
                <option value="contains">содержит</option>
                <option value="is_set">задана (пользователь ответил)</option>
                <option value="is_empty">пустая (нет ответа)</option>
            </select>
        </div>

        <div v-if="needsValue" class="field">
            <label>Значение</label>
            <input v-model="value" placeholder="ru" />
        </div>

        <p class="branch-hint">
            <span class="dot dot-true" /> True — если условие выполняется.
            <span class="dot dot-false" /> False — если нет.
            Соедините правый (True) и левый (False) выходы группы со следующими группами на холсте.
        </p>
    </div>
</template>

<style scoped>
.field { margin-bottom: var(--space-3); }
label { display: block; font-size: var(--font-size-sm); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: var(--color-text-muted); }
input, select {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
    box-sizing: border-box;
    font-family: inherit;
    background: var(--color-surface);
    color: var(--color-text);
}
.field-hint { margin: 4px 0 0; font-size: var(--font-size-xs); color: var(--color-text-muted); }

.branch-hint { font-size: var(--font-size-xs); color: var(--color-text-muted); line-height: 1.6; }
.dot { display: inline-block; width: 8px; height: 8px; border-radius: var(--radius-pill); margin-right: 2px; }
.dot-true { background: var(--color-success); }
.dot-false { background: var(--color-error); margin-left: 8px; }
</style>
