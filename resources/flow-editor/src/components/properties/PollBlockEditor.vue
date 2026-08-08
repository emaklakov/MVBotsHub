<script setup lang="ts">
import { ref, computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import type { ButtonItem } from '@/types/flow'
import type { ChannelProfile } from '@/channels'

const props = defineProps<{ block: UiBlock; variables?: string[]; channel: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { content?: any }] }>()

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })
const maxQuestionLength = computed(() => props.channel.limits.maxPollQuestionLength ?? props.channel.limits.maxTextLength)

const setQuestionText = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const options = computed<ButtonItem[]>(() => props.block.content?.buttons || [])

// Poll — не то же самое, что кнопки под сообщением: у Telegram
// собственный практический потолок вариантов (см. src/channels/telegram.ts),
// поэтому лимит берётся не из maxButtons, а из maxPollOptions. Если у
// канала он не задан (например, канал вообще не умеет 'poll' — тогда
// этот редактор и не должен был открыться, но на всякий случай) —
// откатываемся на 10, официальный практический потолок Bot API.
const maxOptions = computed(() => props.channel.limits.maxPollOptions ?? 10)
const canAddOption = computed(() => options.value.length < maxOptions.value)

const setOptions = (list: ButtonItem[]) => {
    emit('update', { content: { ...props.block.content, buttons: list } })
}

const updateOption = (index: number, value: string) => {
    const list = [...options.value]
    // У варианта опроса нет callback_data (Bot API его не поддерживает
    // для sendPoll — см. ButtonItem в types/flow.ts), поэтому здесь
    // всегда просто { label }, в отличие от ButtonsBlockEditor.
    list[index] = { label: value }
    setOptions(list)
}

const addOption = () => {
    if (!canAddOption.value) return
    setOptions([...options.value, { label: '' }])
}

const removeOption = (index: number) => {
    setOptions(options.value.filter((_, i) => i !== index))
}

const dragIndex = ref<number | null>(null)
const onDragStart = (index: number) => {
    dragIndex.value = index
}
const onDrop = (index: number) => {
    if (dragIndex.value === null || dragIndex.value === index) {
        dragIndex.value = null
        return
    }
    const list = [...options.value]
    const [moved] = list.splice(dragIndex.value, 1)
    list.splice(index, 0, moved)
    setOptions(list)
    dragIndex.value = null
}
</script>

<template>
    <div class="poll-editor">
        <div class="field">
            <label>Вопрос опроса (RU)</label>
            <textarea
                :value="translations.ru"
                :maxlength="maxQuestionLength"
                rows="2"
                placeholder="Что вам интереснее?"
                @input="setQuestionText('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Вопрос опроса (EN)</label>
            <textarea
                :value="translations.en"
                :maxlength="maxQuestionLength"
                rows="2"
                placeholder="What interests you more?"
                @input="setQuestionText('en', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>

        <div class="field">
            <label>Варианты ответа</label>
            <div class="option-list">
                <div
                    v-for="(opt, index) in options"
                    :key="index"
                    class="option-row"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent
                    @drop="onDrop(index)"
                >
                    <span class="drag-handle" title="Перетащить для изменения порядка">⠿</span>
                    <input
                        :value="opt.label"
                        placeholder="Вариант ответа"
                        @input="updateOption(index, ($event.target as HTMLInputElement).value)"
                    />
                    <button type="button" class="remove-btn" title="Удалить" @click="removeOption(index)">✕</button>
                </div>
                <div v-if="!options.length" class="empty-hint">Вариантов пока нет</div>
                <button type="button" class="add-btn" :disabled="!canAddOption" @click="addOption">
                    + Добавить вариант
                </button>
                <p v-if="!canAddOption" class="field-hint">
                    Достигнут лимит Telegram Bot API — не больше {{ maxOptions }} вариантов в опросе.
                </p>
            </div>
        </div>

        <p class="note">
            ℹ️ Опрос отправляется как отдельное сообщение и не ждёт ответа в рамках диалога — голоса Telegram
            доставляет асинхронно (отдельными апдейтами), поэтому у опроса нет своей переменной, а бот сразу
            переходит к следующему блоку.
        </p>
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
.option-list { display: flex; flex-direction: column; gap: var(--space-1); }
.option-row { display: flex; align-items: center; gap: var(--space-1); cursor: grab; }
.option-row:active { cursor: grabbing; }
.option-row input { flex: 1; }
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
.add-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.add-btn:disabled:hover { background: none; }
.empty-hint { font-size: var(--font-size-sm); color: var(--color-text-muted); font-style: italic; }
.note { font-size: var(--font-size-xs); color: var(--color-text-muted); background: var(--color-surface-50); padding: 8px; border-radius: var(--radius-sm); }
</style>
