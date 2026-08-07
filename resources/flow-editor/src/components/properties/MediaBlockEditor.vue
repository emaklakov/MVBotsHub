<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import { getChannelProfile, defaultChannelId, type ChannelProfile } from '@/channels'

const props = defineProps<{ block: UiBlock; variables?: string[]; channel?: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { content?: any }] }>()

// channel не обязателен только чтобы не ломать точечное использование
// компонента вне общего PropertiesPanel — см. аналогичный комментарий в
// TextBlockEditor.vue.
const activeChannel = computed(() => props.channel ?? getChannelProfile(defaultChannelId))
// У Telegram подпись к медиа короче обычного текста; канал без
// отдельного лимита подписи (maxCaptionLength не задан) просто
// использует общий текстовый лимит.
const maxCaptionLength = computed(() => activeChannel.value.limits.maxCaptionLength ?? activeChannel.value.limits.maxTextLength)

type MediaType = 'image' | 'video' | 'audio' | 'file'

/**
 * Единственное, чем реально отличаются image/video/audio/file друг от
 * друга в редакторе — подписи и подсказки по формату. Сама механика
 * (URL + подпись на RU/EN, у file — ещё имя файла) одна и та же, поэтому
 * это один компонент на все 4 типа, а не 4 почти одинаковых файла.
 */
const TYPE_META: Record<MediaType, { label: string; urlPlaceholder: string; urlHint: string }> = {
    image: {
        label: 'изображения',
        urlPlaceholder: 'https://example.com/photo.jpg',
        urlHint: 'Прямая ссылка на JPG/PNG/WEBP. Telegram сам скачает файл по ссылке при отправке.',
    },
    video: {
        label: 'видео',
        urlPlaceholder: 'https://example.com/clip.mp4',
        urlHint: 'Прямая ссылка на MP4.',
    },
    audio: {
        label: 'аудио',
        urlPlaceholder: 'https://example.com/track.mp3',
        urlHint: 'Прямая ссылка на MP3/M4A.',
    },
    file: {
        label: 'файла',
        urlPlaceholder: 'https://example.com/document.pdf',
        urlHint: 'Прямая ссылка на файл. Bot API поддерживает документы размером до 50 МБ.',
    },
}

const mediaType = computed<MediaType>(() => (props.block.type as MediaType) ?? 'file')
const meta = computed(() => TYPE_META[mediaType.value])

const mediaUrl = computed({
    get: () => props.block.content?.mediaUrl || '',
    set: (val: string) => emit('update', { content: { ...props.block.content, mediaUrl: val } }),
})

const mediaFileName = computed({
    get: () => props.block.content?.mediaFileName || '',
    set: (val: string) => emit('update', { content: { ...props.block.content, mediaFileName: val } }),
})

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setCaption = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}
</script>

<template>
    <div class="media-editor">
        <div class="field">
            <label>Ссылка на {{ meta.label }}</label>
            <input v-model="mediaUrl" type="url" :placeholder="meta.urlPlaceholder" />
            <p class="field-hint">{{ meta.urlHint }}</p>
        </div>

        <div v-if="mediaType === 'file'" class="field">
            <label>Имя файла (необязательно)</label>
            <input v-model="mediaFileName" placeholder="document.pdf" />
            <p class="field-hint">Так файл будет подписан у получателя в Telegram. Пусто — Telegram возьмёт имя из ссылки.</p>
        </div>

        <div class="field">
            <label>Подпись RU (необязательно)</label>
            <textarea
                :value="translations.ru"
                rows="2"
                placeholder="Подпись к сообщению…"
                @input="setCaption('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Подпись EN (необязательно)</label>
            <textarea
                :value="translations.en"
                rows="2"
                placeholder="Caption…"
                @input="setCaption('en', ($event.target as HTMLTextAreaElement).value)"
            />
            <div class="char-counter" :class="{ over: Math.max(translations.ru.length, translations.en.length) > maxCaptionLength }">
                {{ Math.max(translations.ru.length, translations.en.length) }} / {{ maxCaptionLength }}
            </div>
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
.char-counter { font-size: var(--font-size-xs); color: var(--color-text-muted); text-align: right; margin-top: 4px; }
.char-counter.over { color: var(--color-error-text); font-weight: 600; }
</style>
