<script setup lang="ts">
import { ref, computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'

// `variables` объявлен, но не используется этим редактором — см.
// аналогичный комментарий в InputBlockEditor.vue.
const props = defineProps<{ block: UiBlock; variables?: string[] }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setQuestionText = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const buttons = computed(() => props.block.content?.buttons || [])

const setButtons = (list: string[]) => {
    emit('update', { content: { ...props.block.content, buttons: list } })
}

const updateButton = (index: number, value: string) => {
    const list = [...buttons.value]
    list[index] = value
    setButtons(list)
}

const addButton = () => {
    setButtons([...buttons.value, ''])
}

const removeButton = (index: number) => {
    setButtons(buttons.value.filter((_, i) => i !== index))
}

// Тот же лёгкий нативный DnD, что и для сортировки блоков внутри группы —
// список кнопок обычно короткий, тащить сторонний пакет ради этого лишнее.
const dragIndex = ref<number | null>(null)

const onDragStart = (index: number) => {
    dragIndex.value = index
}

const onDrop = (index: number) => {
    if (dragIndex.value === null || dragIndex.value === index) {
        dragIndex.value = null
        return
    }
    const list = [...buttons.value]
    const [moved] = list.splice(dragIndex.value, 1)
    list.splice(index, 0, moved)
    setButtons(list)
    dragIndex.value = null
}

const keyboardMode = computed({
    get: () => props.block.config?.keyboardMode || 'inline',
    set: (val: 'inline' | 'reply') => emit('update', { config: { ...props.block.config, keyboardMode: val } }),
})

const variable = computed({
    get: () => props.block.config?.variable || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, variable: val } }),
})
</script>

<template>
    <div class="buttons-editor">
        <div class="field">
            <label>Текст вопроса (RU)</label>
            <textarea
                :value="translations.ru"
                rows="2"
                placeholder="Выбери язык интерфейса"
                @input="setQuestionText('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Текст вопроса (EN)</label>
            <textarea
                :value="translations.en"
                rows="2"
                placeholder="Choose interface language"
                @input="setQuestionText('en', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>

        <div class="field">
            <label>Режим клавиатуры</label>
            <select v-model="keyboardMode">
                <option value="inline">Inline-кнопки под сообщением</option>
                <option value="reply">Reply-клавиатура</option>
            </select>
        </div>

        <div class="field">
            <label>Переменная (необязательно)</label>
            <input v-model="variable" placeholder="user_language" />
            <p class="field-hint">Выбор пользователя сохранится в эту переменную — можно использовать в условии или в тексте других блоков.</p>
        </div>

        <div class="field">
            <label>Кнопки</label>
            <div class="button-list">
                <div
                    v-for="(btn, index) in buttons"
                    :key="index"
                    class="button-row"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent
                    @drop="onDrop(index)"
                >
                    <span class="drag-handle" title="Перетащить для изменения порядка">⠿</span>
                    <input
                        :value="btn"
                        placeholder="Текст кнопки"
                        @input="updateButton(index, ($event.target as HTMLInputElement).value)"
                    />
                    <button type="button" class="remove-btn" title="Удалить" @click="removeButton(index)">✕</button>
                </div>
                <div v-if="!buttons.length" class="empty-hint">Кнопок пока нет</div>
                <button type="button" class="add-btn" @click="addButton">+ Добавить кнопку</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.field { margin-bottom: var(--space-3); }
label { display: block; font-size: var(--font-size-sm); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: var(--color-text-muted); }
input, textarea, select {
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
.button-list { display: flex; flex-direction: column; gap: var(--space-1); }
.button-row { display: flex; align-items: center; gap: var(--space-1); cursor: grab; }
.button-row:active { cursor: grabbing; }
.button-row input { flex: 1; }
.drag-handle { color: var(--color-text-muted); font-size: var(--font-size-md); flex-shrink: 0; }
.remove-btn {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    border: 1px solid color-mix(in oklch, var(--color-error) 40%, transparent);
    background: color-mix(in oklch, var(--color-error) 10%, var(--color-surface));
    color: var(--color-error-text);
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: var(--font-size-xs);
}
.remove-btn:hover { background: color-mix(in oklch, var(--color-error) 20%, var(--color-surface)); }
.add-btn {
    margin-top: 4px;
    padding: 6px;
    border: 1px dashed var(--color-stroke);
    background: none;
    border-radius: var(--radius-sm);
    color: var(--color-accent-text);
    font-size: var(--font-size-sm);
    cursor: pointer;
}
.add-btn:hover { background: color-mix(in oklch, var(--color-accent) 10%, transparent); }
.empty-hint { font-size: var(--font-size-sm); color: var(--color-text-muted); font-style: italic; }
</style>
