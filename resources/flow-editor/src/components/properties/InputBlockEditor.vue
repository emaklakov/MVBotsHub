<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'

// `variables` объявлен, но не используется этим редактором — сюда его
// передаёт общий PropertiesPanel одинаково для всех типов блоков
// (см. src/blocks); без явного объявления пропа Vue протащил бы его
// как обычный DOM-атрибут на корневой div (fallthrough attrs).
const props = defineProps<{ block: UiBlock; variables?: string[] }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setQuestionText = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const variable = computed({
    get: () => props.block.config?.variable || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, variable: val } }),
})

const hint = computed({
    get: () => props.block.config?.hint || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, hint: val } }),
})
</script>

<template>
    <div class="input-editor">
        <div class="field">
            <label>Текст вопроса (RU)</label>
            <textarea
                :value="translations.ru"
                rows="2"
                placeholder="Как тебя зовут?"
                @input="setQuestionText('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Текст вопроса (EN)</label>
            <textarea
                :value="translations.en"
                rows="2"
                placeholder="What's your name?"
                @input="setQuestionText('en', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Переменная</label>
            <input v-model="variable" placeholder="user_name" />
            <p class="field-hint">Ответ пользователя сохранится в эту переменную — её можно вставить в текст других блоков.</p>
        </div>
        <div class="field">
            <label>Подсказка (placeholder)</label>
            <input v-model="hint" placeholder="Введите имя…" />
        </div>
    </div>
</template>

<style scoped>
.field { margin-bottom: var(--space-3); }
label { display: block; font-size: var(--font-size-sm); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: var(--color-text-muted); }
input, textarea {
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
textarea { resize: vertical; }
.field-hint { margin: 4px 0 0; font-size: var(--font-size-xs); color: var(--color-text-muted); }
</style>
