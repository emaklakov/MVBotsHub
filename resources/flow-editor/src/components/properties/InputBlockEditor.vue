<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import type { ChannelProfile } from '@/channels'

// `variables`/`channel` объявлены, но не используются этим редактором —
// сюда их передаёт общий PropertiesPanel одинаково для всех типов блоков
// (см. src/blocks); без явного объявления пропа Vue протащил бы их
// как обычные DOM-атрибуты на корневой div (fallthrough attrs).
const props = defineProps<{ block: UiBlock; variables?: string[]; channel?: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

type InputSubtype = 'input' | 'number' | 'email' | 'phone' | 'date'
const subtype = computed<InputSubtype>(() => (props.block.type as InputSubtype) || 'input')

// Один и тот же компонент обслуживает 'input' и его валидируемые
// варианты из Фазы 2 (number/email/phone/date) — реальная валидация
// формата ответа выполняется в симуляторе (useFlowSimulator.ts,
// validateInputValue), здесь только подсказки и, для number, границы
// допустимого значения.
const QUESTION_PLACEHOLDERS: Record<InputSubtype, { ru: string; en: string }> = {
    input: { ru: 'Как тебя зовут?', en: "What's your name?" },
    number: { ru: 'Сколько тебе лет?', en: 'How old are you?' },
    email: { ru: 'Укажи свой email', en: 'What is your email?' },
    phone: { ru: 'Укажи номер телефона', en: 'What is your phone number?' },
    date: { ru: 'Укажи дату (ГГГГ-ММ-ДД)', en: 'Enter a date (YYYY-MM-DD)' },
}

const FORMAT_HINTS: Partial<Record<InputSubtype, string>> = {
    email: 'Перед тем как диалог продолжится, ответ проверяется на формат email (name@example.com).',
    phone: 'Ответ должен быть похож на номер телефона в международном формате, например +79991234567.',
    date: 'Ответ должен быть в формате ГГГГ-ММ-ДД, например 2000-01-31.',
}

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

// get и set у WritableComputedRef должны совпадать по типу — <input
// type="number"> без модификатора .number всё равно отдаёт строку в
// @input/v-model, поэтому геттер тоже приводим к строке (а не оставляем
// number | ''), иначе строгая типизация TS ругается на несовпадение типов.
const minValue = computed({
    get: () => (props.block.config?.validation?.min !== undefined ? String(props.block.config.validation.min) : ''),
    set: (val: string) => {
        const min = val === '' ? undefined : Number(val)
        emit('update', { config: { ...props.block.config, validation: { ...props.block.config?.validation, min } } })
    },
})

const maxValue = computed({
    get: () => (props.block.config?.validation?.max !== undefined ? String(props.block.config.validation.max) : ''),
    set: (val: string) => {
        const max = val === '' ? undefined : Number(val)
        emit('update', { config: { ...props.block.config, validation: { ...props.block.config?.validation, max } } })
    },
})
</script>

<template>
    <div class="input-editor">
        <div class="field">
            <label>Текст вопроса (RU)</label>
            <textarea
                :value="translations.ru"
                rows="2"
                :placeholder="QUESTION_PLACEHOLDERS[subtype].ru"
                @input="setQuestionText('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Текст вопроса (EN)</label>
            <textarea
                :value="translations.en"
                rows="2"
                :placeholder="QUESTION_PLACEHOLDERS[subtype].en"
                @input="setQuestionText('en', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Переменная</label>
            <input v-model="variable" placeholder="user_name" />
            <p class="field-hint">Ответ пользователя сохранится в эту переменную — её можно вставить в текст других блоков.</p>
            <p v-if="FORMAT_HINTS[subtype]" class="field-hint format-hint">{{ FORMAT_HINTS[subtype] }}</p>
        </div>

        <div v-if="subtype === 'number'" class="field-row">
            <div class="field">
                <label>Мин. значение</label>
                <input v-model="minValue" type="number" placeholder="без ограничения" />
            </div>
            <div class="field">
                <label>Макс. значение</label>
                <input v-model="maxValue" type="number" placeholder="без ограничения" />
            </div>
        </div>

        <div class="field">
            <label>Подсказка (placeholder)</label>
            <input v-model="hint" placeholder="Введите имя…" />
        </div>
    </div>
</template>

<style scoped>
.field { margin-bottom: var(--space-3); }
.field-row { display: flex; gap: var(--space-3); }
.field-row .field { flex: 1; }
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
.format-hint { color: var(--color-accent-text); }
</style>
