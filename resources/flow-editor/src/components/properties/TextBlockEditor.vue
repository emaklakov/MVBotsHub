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
.field { margin-bottom: 12px; }
label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: #64748b; }
textarea { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; box-sizing: border-box; font-family: inherit; resize: vertical; }

.lang-tabs { display: flex; gap: 4px; margin-bottom: 12px; }
.lang-tabs button {
    padding: 4px 12px;
    border: 1px solid #cbd5e1;
    background: white;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}
.lang-tabs button.active { background: #3b82f6; border-color: #3b82f6; color: white; }

.variables { display: flex; flex-wrap: wrap; gap: 6px; }
.var-chip {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 11px;
    cursor: pointer;
}
.var-chip:hover { background: #d1fae5; }
.variables-empty { font-size: 12px; color: #94a3b8; font-style: italic; }
</style>
