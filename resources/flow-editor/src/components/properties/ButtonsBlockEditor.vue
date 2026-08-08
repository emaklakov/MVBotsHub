<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import type { ButtonItem } from '@/types/flow'
import { channelHasCapability, type ChannelProfile } from '@/channels'

const props = defineProps<{ block: UiBlock; variables?: string[]; channel: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setQuestionText = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const buttons = computed<ButtonItem[]>(() => props.block.content?.buttons || [])

const maxButtons = computed(() => props.channel.limits.maxButtons)
const maxButtonLabelLength = computed(() => props.channel.limits.maxButtonLabelLength)
const canAddButton = computed(() => buttons.value.length < maxButtons.value)

const setButtons = (list: ButtonItem[]) => {
    emit('update', { content: { ...props.block.content, buttons: list } })
}

const updateLabel = (index: number, value: string) => {
    const list = [...buttons.value]
    list[index] = { ...list[index], label: value }
    setButtons(list)
}

const updateCallbackData = (index: number, value: string) => {
    const list = [...buttons.value]
    // Пустая строка — то же самое, что "не задано": симулятор/бэкенд
    // в этом случае используют label как значение ответа (см. комментарий
    // у ButtonItem.callbackData в types/flow.ts).
    list[index] = { ...list[index], callbackData: value || undefined }
    setButtons(list)
}

const addButton = () => {
    if (!canAddButton.value) return
    setButtons([...buttons.value, { label: '' }])
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

// callback_data — исключительно Bot API-концепция inline-клавиатуры:
// нажатие reply-кнопки просто отправляет её текст обычным сообщением, у
// него физически нет отдельного payload. Поэтому поле показываем только
// при keyboardMode: 'inline' — значения, оставшиеся в данных после
// переключения на reply, не удаляем (не наше право терять данные молча),
// но и не показываем/не используем, пока канал в inline-режиме.
const showCallbackData = computed(() => keyboardMode.value === 'inline')

// Bot API считает лимit 64 БАЙТА, а не символа — кириллица/эмодзи в
// callback_data съедают по 2-4 байта на символ, поэтому просто
// maxlength на инпуте здесь недостаточен, считаем реальный размер в UTF-8.
function utf8ByteLength(value: string): number {
    return new TextEncoder().encode(value).length
}

const callbackDataMaxBytes = computed(() => props.channel.limits.callbackDataMaxBytes)

function isCallbackDataOverLimit(value: string | undefined): boolean {
    if (!value || callbackDataMaxBytes.value === undefined) return false
    return utf8ByteLength(value) > callbackDataMaxBytes.value
}

// Reply-клавиатура — это системная клавиатура Telegram, у веб-виджета
// (и вообще у каналов без соответствующей возможности) такого понятия
// нет — опция в селекте появляется только если канал её поддерживает
// (см. src/channels). Сейчас единственный канал — Telegram, у него
// возможность есть, так что видимо ничего не меняется; смысл — в том,
// что при появлении другого канала опция сама перестанет предлагаться.
const supportsReplyKeyboard = computed(() => channelHasCapability(props.channel, 'reply_keyboard'))

// Защита от «застрявшего» невалидного значения: если конфиг блока каким-то
// образом оказался в режиме, которого канал не поддерживает (например,
// после переноса бота на другой канал в будущем), тихо откатываем на inline.
watch(supportsReplyKeyboard, (supported) => {
    if (!supported && keyboardMode.value === 'reply') keyboardMode.value = 'inline'
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
                <option v-if="supportsReplyKeyboard" value="reply">Reply-клавиатура</option>
            </select>
        </div>

        <div class="field">
            <label>Переменная (необязательно)</label>
            <input v-model="variable" placeholder="user_language" />
            <p class="field-hint">
                Выбор пользователя сохранится в эту переменную —
                {{ showCallbackData ? 'значение callback_data кнопки (или её текст, если он не задан)' : 'текст выбранной кнопки' }},
                можно использовать в условии или в тексте других блоков.
            </p>
        </div>

        <div class="field">
            <label>Кнопки</label>
            <div class="button-list">
                <div
                    v-for="(btn, index) in buttons"
                    :key="index"
                    class="button-row"
                    :class="{ 'has-callback': showCallbackData }"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent
                    @drop="onDrop(index)"
                >
                    <span class="drag-handle" title="Перетащить для изменения порядка">⠿</span>
                    <div class="button-row-fields">
                        <input
                            :value="btn.label"
                            :maxlength="maxButtonLabelLength"
                            placeholder="Текст кнопки"
                            @input="updateLabel(index, ($event.target as HTMLInputElement).value)"
                        />
                        <template v-if="showCallbackData">
                            <input
                                :value="btn.callbackData || ''"
                                placeholder="callback_data (необязательно, по умолчанию — текст кнопки)"
                                class="callback-data-input"
                                :class="{ over: isCallbackDataOverLimit(btn.callbackData) }"
                                @input="updateCallbackData(index, ($event.target as HTMLInputElement).value)"
                            />
                            <p v-if="isCallbackDataOverLimit(btn.callbackData)" class="field-hint over">
                                callback_data длиннее {{ callbackDataMaxBytes }} байт (лимит Bot API) —
                                {{ utf8ByteLength(btn.callbackData || '') }} байт.
                            </p>
                        </template>
                    </div>
                    <button type="button" class="remove-btn" title="Удалить" @click="removeButton(index)">✕</button>
                </div>
                <div v-if="!buttons.length" class="empty-hint">Кнопок пока нет</div>
                <button type="button" class="add-btn" :disabled="!canAddButton" @click="addButton">
                    + Добавить кнопку
                </button>
                <p v-if="!canAddButton" class="field-hint">
                    Достигнут лимит канала «{{ channel.label }}» — не больше {{ maxButtons }} кнопок в одной группе.
                </p>
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
.field-hint.over { color: var(--color-error-text); font-weight: 600; }
.button-list { display: flex; flex-direction: column; gap: var(--space-2); }
.button-row { display: flex; align-items: flex-start; gap: var(--space-1); cursor: grab; }
.button-row:active { cursor: grabbing; }
.button-row-fields { flex: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.drag-handle { color: var(--color-text-muted); font-size: var(--font-size-md); flex-shrink: 0; margin-top: 6px; }
.callback-data-input { font-family: var(--font-mono, monospace); font-size: var(--font-size-sm); }
.callback-data-input.over { border-color: var(--color-error); }
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
    margin-top: 2px;
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
.add-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.add-btn:disabled:hover { background: none; }
.empty-hint { font-size: var(--font-size-sm); color: var(--color-text-muted); font-style: italic; }
</style>
