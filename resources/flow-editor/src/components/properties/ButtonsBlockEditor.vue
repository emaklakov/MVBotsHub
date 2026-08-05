<script setup lang="ts">
import { ref, computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'

const props = defineProps<{ block: UiBlock }>()
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
.field { margin-bottom: 12px; }
label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; color: #64748b; }
input, textarea, select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; box-sizing: border-box; font-family: inherit; }
textarea { resize: vertical; }

.button-list { display: flex; flex-direction: column; gap: 6px; }
.button-row { display: flex; align-items: center; gap: 6px; cursor: grab; }
.button-row:active { cursor: grabbing; }
.button-row input { flex: 1; }
.drag-handle { color: #94a3b8; font-size: 14px; flex-shrink: 0; }
.remove-btn {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
}
.remove-btn:hover { background: #fee2e2; }
.add-btn {
    margin-top: 4px;
    padding: 6px;
    border: 1px dashed #cbd5e1;
    background: none;
    border-radius: 4px;
    color: #3b82f6;
    font-size: 12px;
    cursor: pointer;
}
.add-btn:hover { background: #eff6ff; }
.empty-hint { font-size: 12px; color: #94a3b8; font-style: italic; }
</style>
