/**
 * Модель каналов доставки бота.
 *
 * Платформа задумана как мульти-канальная (сейчас — только Telegram,
 * в будущем — свой веб-виджет и, возможно, другие), но ядро редактора
 * (реестр блоков, сериализация, канвас) не должно ничего знать о
 * конкретном канале. Вместо списка каналов на каждом блоке
 * (`channels: ['telegram']`) используется набор возможностей
 * (capability) — у Telegram и веб-виджета почти нет пересечения по
 * низкоуровневым ограничениям API, зато многие возможности (геолокация,
 * загрузка файла) технически доступны в обоих, просто через разные
 * механизмы доставки на бэкенде. Capability-модель позволяет одному и
 * тому же блоку быть доступным сразу нескольким каналам, если они оба
 * заявляют нужную возможность.
 */
export type Capability =
    | 'inline_buttons' // кнопки под сообщением
    | 'reply_keyboard' // системная клавиатура вместо чата (есть только у Telegram)
    | 'commands' // /start, /help и т.п.
    | 'deep_link_payload' // параметр, приходящий вместе со входом в диалог
    | 'file_upload'
    | 'voice_input'
    | 'geolocation'
    | 'contact_share'
    | 'web_app_button' // Telegram Mini App
    | 'custom_html' // форматированный текст сверх Markdown-подмножества Bot API
    | 'typing_indicator'

/**
 * Известные на сегодня каналы. Единственное значение — 'telegram': по
 * явной договорённости (см. обсуждение) веб-виджет пока не реализуем,
 * только держим архитектуру готовой под его появление. Когда он
 * появится, добавление 'web_widget' сюда заставит TypeScript потребовать
 * для него запись в channelRegistry (см. registry.ts) — забыть про неё
 * не получится.
 */
export type ChannelId = 'telegram'

export interface ChannelLimits {
    /** Максимальная длина текстового сообщения. */
    maxTextLength: number
    /** Максимальная длина подписи к медиафайлу (image/video/audio/file) —
     * у Telegram она заметно короче обычного текста. Не указана — значит
     * канал использует тот же лимит, что и для обычного текста. */
    maxCaptionLength?: number
    /** Практический максимум количества кнопок в одной группе. */
    maxButtons: number
    maxButtonLabelLength: number
    /** Актуально для Telegram (callback_data ≤ 64 байт); канал без
     * такого технического ограничения может поле не указывать. */
    callbackDataMaxBytes?: number
}

/** Переменная, которую платформа подставляет сама при старте диалога
 * (например, deep-link параметр). Не создаётся пользователем через блок
 * «Вопрос», но должна быть доступна для вставки в текст/условие наравне
 * с обычными переменными. */
export interface ChannelSystemVariable {
    name: string
    label: string
    hint?: string
}

export interface ChannelProfile {
    id: ChannelId
    label: string
    capabilities: Capability[]
    limits: ChannelLimits
    systemVariables: ChannelSystemVariable[]
}

export function channelHasCapability(channel: ChannelProfile, capability: Capability): boolean {
    return channel.capabilities.includes(capability)
}

/** true, если канал поддерживает ВСЕ перечисленные возможности (или
 * список пуст/не задан — блок без требований доступен всем каналам). */
export function channelSupportsAll(channel: ChannelProfile, required: Capability[] | undefined): boolean {
    if (!required || !required.length) return true
    return required.every((capability) => channelHasCapability(channel, capability))
}
