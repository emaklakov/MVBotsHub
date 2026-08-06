<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'

const props = defineProps<{ block: UiBlock; variables: string[] }>()
const emit = defineEmits<{ update: [patch: { content?: any }] }>()

type Lang = 'ru' | 'en'

const activeLang = ref<Lang>('ru')
const textareaEl = ref<HTMLTextAreaElement | null>(null)

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setText = (lang: Lang, value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const currentText = computed({
    get: () => translations.value[activeLang.value] || '',
    set: (val: string) => setText(activeLang.value, val),
})

/** Обёртка `{{name}}` вынесена в функцию: буквальные `{{`/`}}` прямо
 * в mustache-выражении шаблона ломают парсер Vue-компилятора. */
const wrapVariable = (name: string) => `{{${name}}}`

/**
 * Вставляет `{{name}}` в текущую позицию курсора активного textarea,
 * а не просто дописывает в конец — это важно, когда переменную нужно
 * вставить в середину уже написанного текста.
 */
const insertVariable = (name: string) => {
    const textarea = textareaEl.value
    const current = currentText.value
    const token = `{{${name}}}`

    if (!textarea) {
        currentText.value = current + token
        return
    }

    const start = textarea.selectionStart ?? current.length
    const end = textarea.selectionEnd ?? current.length
    currentText.value = current.slice(0, start) + token + current.slice(end)

    nextTick(() => {
        textarea.focus()
        const cursor = start + token.length
        textarea.setSelectionRange(cursor, cursor)
    })
}
</script>

<template>
    <div class="text-editor">
        <div class="lang-tabs">
            <button type="button" :class="{ active: activeLang === 'ru' }" @click="activeLang = 'ru'">RU</button>
            <button type="button" :class="{ active: activeLang === 'en' }" @click="activeLang = 'en'">EN</button>
        </div>

        <div class="field">
            <label>Текст сообщения ({{ activeLang.toUpperCase() }})</label>
            <textarea ref="textareaEl" v-model="currentText" rows="5" placeholder="Введите текст…" />
        </div>

        <div class="field">
            <label>Переменные</label>
            <div v-if="variables.length" class="variables">
                <button v-for="v in variables" :key="v" type="button" class="var-chip" @click="insertVariable(v)">
                    {{ wrapVariable(v) }}
                </button>
            </div>
            <div v-else class="variables-empty">
                Нет доступных переменных — добавьте блок «Вопрос» и задайте ему имя переменной.
            </div>
        </div>
    </div>
</template>

<style scoped>
.field { margin-bottom: var(--space-3); }
label { display: block; font-size: var(--font-size-sm); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: var(--color-text-muted); }
textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
    box-sizing: border-box;
    font-family: inherit;
    resize: vertical;
    background: var(--color-surface);
    color: var(--color-text);
}

.lang-tabs { display: flex; gap: 4px; margin-bottom: var(--space-3); }
.lang-tabs button {
    padding: 4px 12px;
    border: 1px solid var(--color-stroke);
    background: var(--color-surface);
    color: var(--color-text);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    cursor: pointer;
}
.lang-tabs button.active { background: var(--color-accent); border-color: var(--color-accent); color: var(--color-accent-contrast); }

.variables { display: flex; flex-wrap: wrap; gap: var(--space-1); }
.var-chip {
    background: color-mix(in oklch, var(--color-success) 12%, var(--color-surface));
    border: 1px solid color-mix(in oklch, var(--color-success) 35%, transparent);
    color: var(--color-success-text);
    border-radius: var(--radius-pill);
    padding: 4px 10px;
    font-size: var(--font-size-xs);
    cursor: pointer;
}
.var-chip:hover { background: color-mix(in oklch, var(--color-success) 22%, var(--color-surface)); }
.variables-empty { font-size: var(--font-size-sm); color: var(--color-text-muted); font-style: italic; }
</style>
