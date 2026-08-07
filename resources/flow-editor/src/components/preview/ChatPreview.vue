<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { createChatSimulator } from '../../composables/useFlowSimulator'
import type { FlowSchema } from '@/types/flow'

const props = defineProps<{ schema: FlowSchema }>()
const emit = defineEmits<{ close: [] }>()

// Снимок схемы фиксируется в момент открытия панели (см. App.vue —
// пересчитывается только пока панель открыта) и дальше не отслеживает
// live-правки в редакторе: тест должен быть предсказуемым прогоном
// зафиксированного сценария, а не "плыть" при каждом изменении блока.
const sim = createChatSimulator(props.schema)
sim.start()

const inputValue = ref('')
const messagesEl = ref<HTMLElement | null>(null)

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    })
}

watch(() => sim.state.messages.length, scrollToBottom)

const isLastMessage = (id: string) => sim.state.messages[sim.state.messages.length - 1]?.id === id

const handleSendText = () => {
    if (!inputValue.value.trim()) return
    sim.submitText(inputValue.value)
    inputValue.value = ''
}

const handleChoice = (option: string) => {
    sim.submitChoice(option)
}

const handleRestart = () => {
    sim.start()
    inputValue.value = ''
    scrollToBottom()
}

const MEDIA_MISSING_LABELS: Record<'image' | 'video' | 'audio' | 'file', string> = {
    image: 'Изображение без ссылки',
    video: 'Видео без ссылки',
    audio: 'Аудио без ссылки',
    file: 'Файл без ссылки',
}

const mediaMissingLabel = (type?: 'image' | 'video' | 'audio' | 'file') =>
    (type && MEDIA_MISSING_LABELS[type]) || 'Медиа без ссылки'
</script>

<template>
    <div class="preview-backdrop" @click="emit('close')" />
    <div class="chat-preview">
        <div class="preview-header">
            <h3>Тест-режим</h3>
            <div class="header-actions">
                <button class="icon-btn" title="Начать сначала" @click="handleRestart">↻</button>
                <button class="icon-btn" title="Закрыть" @click="emit('close')">✕</button>
            </div>
        </div>

        <div ref="messagesEl" class="messages">
            <template v-for="msg in sim.state.messages" :key="msg.id">
                <div v-if="msg.kind === 'note'" class="note">{{ msg.text }}</div>
                <div v-else-if="msg.kind === 'media'" class="message-row role-bot">
                    <div class="bubble media-bubble">
                        <img v-if="msg.mediaType === 'image' && msg.mediaUrl" :src="msg.mediaUrl" class="media-preview" alt="" />
                        <video v-else-if="msg.mediaType === 'video' && msg.mediaUrl" :src="msg.mediaUrl" class="media-preview" controls />
                        <audio v-else-if="msg.mediaType === 'audio' && msg.mediaUrl" :src="msg.mediaUrl" controls class="media-audio" />
                        <a
                            v-else-if="msg.mediaType === 'file' && msg.mediaUrl"
                            :href="msg.mediaUrl"
                            target="_blank"
                            rel="noopener"
                            class="media-file-link"
                        >
                            📎 {{ msg.mediaFileName || msg.mediaUrl }}
                        </a>
                        <div v-else class="media-missing">{{ mediaMissingLabel(msg.mediaType) }}</div>
                        <div v-if="msg.text" class="media-caption">{{ msg.text }}</div>
                    </div>
                </div>
                <div v-else class="message-row" :class="`role-${msg.role}`">
                    <div class="bubble">{{ msg.text }}</div>
                </div>
                <div
                    v-if="msg.kind === 'buttons' && isLastMessage(msg.id) && sim.state.waiting?.kind === 'buttons'"
                    class="choice-buttons"
                >
                    <button v-for="opt in msg.options" :key="opt" type="button" @click="handleChoice(opt)">{{ opt }}</button>
                </div>
            </template>

            <div v-if="sim.state.finished" class="finished-note">Диалог завершён</div>
        </div>

        <div v-if="sim.state.waiting?.kind === 'input'" class="composer">
            <input
                v-model="inputValue"
                type="text"
                :placeholder="sim.state.waiting.hint || 'Введите ответ…'"
                @keyup.enter="handleSendText"
            />
            <button type="button" class="send-btn" @click="handleSendText">→</button>
        </div>
        <div v-else-if="sim.state.waiting?.kind === 'buttons'" class="composer-hint">Выберите один из вариантов выше ⤴</div>
    </div>
</template>

<style scoped>
.preview-backdrop {
    position: fixed;
    inset: 0;
    background: oklch(0 0 0 / 25%);
    z-index: 40;
}
.chat-preview {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 380px;
    background: var(--color-surface);
    box-shadow: var(--shadow-md);
    z-index: 41;
    display: flex;
    flex-direction: column;
    font-family: var(--font-sans);
}
.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px var(--space-4);
    border-bottom: 1px solid var(--color-stroke);
}
.preview-header h3 { margin: 0; font-size: var(--font-size-md); color: var(--color-text); }
.header-actions { display: flex; gap: var(--space-1); }
.icon-btn {
    width: 28px;
    height: 28px;
    border: 1px solid var(--color-stroke);
    background: var(--color-surface);
    color: var(--color-text);
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: var(--font-size-base);
}
.icon-btn:hover { background: var(--color-surface-50); }

.messages { flex: 1; overflow-y: auto; padding: var(--space-4); display: flex; flex-direction: column; gap: var(--space-2); background: var(--color-surface-50); }

.message-row { display: flex; }
.message-row.role-bot { justify-content: flex-start; }
.message-row.role-user { justify-content: flex-end; }
.bubble {
    max-width: 80%;
    padding: 8px 12px;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}
.role-bot .bubble { background: var(--color-surface); border: 1px solid var(--color-stroke); color: var(--color-text); border-bottom-left-radius: 2px; }
.role-user .bubble { background: var(--color-primary); color: var(--color-primary-text); border-bottom-right-radius: 2px; }

.media-bubble { background: var(--color-surface); border: 1px solid var(--color-stroke); color: var(--color-text); border-bottom-left-radius: 2px; padding: 8px; display: flex; flex-direction: column; gap: 6px; }
.media-preview { max-width: 100%; max-height: 220px; border-radius: var(--radius-sm); display: block; }
.media-audio { width: 100%; }
.media-file-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 8px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    color: var(--color-accent-text);
    text-decoration: none;
    font-size: var(--font-size-sm);
    word-break: break-all;
}
.media-file-link:hover { background: var(--color-surface-50); }
.media-missing { font-size: var(--font-size-sm); color: var(--color-text-muted); font-style: italic; padding: 4px 0; }
.media-caption { font-size: var(--font-size-base); line-height: 1.4; white-space: pre-wrap; word-break: break-word; }

.note {
    align-self: center;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    font-style: italic;
    text-align: center;
    padding: 2px 8px;
}
.finished-note {
    align-self: center;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    background: var(--color-surface-200);
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    margin-top: 4px;
}

.choice-buttons { display: flex; flex-wrap: wrap; gap: var(--space-1); padding-left: 2px; }
.choice-buttons button {
    padding: 6px 12px;
    border: 1px solid color-mix(in oklch, var(--color-accent) 45%, transparent);
    background: color-mix(in oklch, var(--color-accent) 10%, var(--color-surface));
    color: var(--color-accent-text);
    border-radius: var(--radius-pill);
    font-size: var(--font-size-sm);
    cursor: pointer;
}
.choice-buttons button:hover { background: color-mix(in oklch, var(--color-accent) 20%, var(--color-surface)); }

.composer { display: flex; gap: var(--space-2); padding: 12px var(--space-4); border-top: 1px solid var(--color-stroke); }
.composer input {
    flex: 1;
    padding: 8px 10px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
    background: var(--color-surface);
    color: var(--color-text);
}
.send-btn { width: 36px; border: none; background: var(--color-primary); color: var(--color-primary-text); border-radius: var(--radius-sm); cursor: pointer; font-size: var(--font-size-lg); }
.composer-hint { padding: 12px var(--space-4); border-top: 1px solid var(--color-stroke); font-size: var(--font-size-sm); color: var(--color-text-muted); text-align: center; }
</style>
