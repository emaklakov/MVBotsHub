import type { ChannelProfile } from './types'

/**
 * Лимиты и возможности взяты из реального Telegram Bot API, а не
 * придуманы "на глаз" — именно поэтому они здесь, а не magic-числами
 * прямо в редакторах свойств:
 *  - текст сообщения ≤ 4096 символов;
 *  - подпись к медиа ≤ 1024 (пригодится в Фазе 1 для image/video/audio);
 *  - callback_data инлайн-кнопки ≤ 64 байт.
 */
export const telegramChannel: ChannelProfile = {
    id: 'telegram',
    label: 'Telegram',
    capabilities: [
        'inline_buttons',
        'reply_keyboard',
        'commands',
        'deep_link_payload',
        'file_upload',
        'voice_input',
        'geolocation',
        'contact_share',
        'web_app_button',
    ],
    limits: {
        maxTextLength: 4096,
        maxCaptionLength: 1024,
        // Bot API формально не ограничивает число inline-кнопок числом,
        // но UX разваливается на десятках — берём разумный практический потолок.
        maxButtons: 40,
        maxButtonLabelLength: 64,
        callbackDataMaxBytes: 64,
    },
    systemVariables: [
        {
            name: 'start_param',
            label: 'Параметр запуска (deep-link)',
            hint: 'Всё, что стоит после ?start= в ссылке вида t.me/bot?start=... — доступно с самого начала диалога, без отдельного блока «Вопрос».',
        },
    ],
}
