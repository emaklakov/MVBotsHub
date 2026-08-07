<script setup lang="ts">
import { computed } from 'vue'
import type { UiBlock } from '../../composables/useFlowSerializer'
import type { ChannelProfile } from '@/channels'

// `variables`/`channel` объявлены, но не используются этим редактором —
// см. аналогичный комментарий в InputBlockEditor.vue.
const props = defineProps<{ block: UiBlock; variables?: string[]; channel?: ChannelProfile }>()
const emit = defineEmits<{ update: [patch: { content?: any; config?: any }] }>()

type RequestSubtype = 'geolocation' | 'contact'
const subtype = computed<RequestSubtype>(() => (props.block.type as RequestSubtype) || 'geolocation')

const META: Record<RequestSubtype, { placeholder: { ru: string; en: string }; note: string }> = {
    geolocation: {
        placeholder: { ru: 'Поделись своей геолокацией', en: 'Share your location' },
        note: 'В Telegram под сообщением появится нативная кнопка «Отправить геолокацию» — пользователь жмёт её, а не печатает ответ.',
    },
    contact: {
        placeholder: { ru: 'Поделись своим номером телефона', en: 'Share your phone number' },
        note: 'В Telegram под сообщением появится нативная кнопка «Отправить контакт» — пользователь жмёт её, а не печатает ответ.',
    },
}

const meta = computed(() => META[subtype.value])

const translations = computed(() => props.block.content?.translations || { ru: '', en: '' })

const setQuestionText = (lang: 'ru' | 'en', value: string) => {
    emit('update', { content: { ...props.block.content, translations: { ...translations.value, [lang]: value } } })
}

const variable = computed({
    get: () => props.block.config?.variable || '',
    set: (val: string) => emit('update', { config: { ...props.block.config, variable: val } }),
})
</script>

<template>
    <div class="request-editor">
        <div class="field">
            <label>Текст перед запросом (RU)</label>
            <textarea
                :value="translations.ru"
                rows="2"
                :placeholder="meta.placeholder.ru"
                @input="setQuestionText('ru', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Текст перед запросом (EN)</label>
            <textarea
                :value="translations.en"
                rows="2"
                :placeholder="meta.placeholder.en"
                @input="setQuestionText('en', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
        <div class="field">
            <label>Переменная</label>
            <input v-model="variable" :placeholder="subtype === 'geolocation' ? 'user_location' : 'user_phone'" />
            <p class="field-hint">
                {{ subtype === 'geolocation' ? 'Координаты (широта,долгота)' : 'Номер телефона' }}
                сохранятся в эту переменную.
            </p>
        </div>
        <p class="note">ℹ️ {{ meta.note }}</p>
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
.note { font-size: var(--font-size-xs); color: var(--color-text-muted); background: var(--color-surface-50); padding: 8px; border-radius: var(--radius-sm); }
</style>
