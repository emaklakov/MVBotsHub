import type { ChannelId, ChannelProfile } from './types'
import { telegramChannel } from './telegram'

/**
 * Record<ChannelId, ChannelProfile> — не Partial. Когда появится второй
 * ChannelId (например 'web_widget' — см. обсуждение архитектуры), это
 * заставит TypeScript потребовать для него запись здесь же: пропустить
 * регистрацию нового канала и получить рантайм-ошибку в проде не выйдет,
 * компиляция просто не пройдёт.
 */
export const channelRegistry: Record<ChannelId, ChannelProfile> = {
    telegram: telegramChannel,
}

/** Канал по умолчанию, пока бэкенд не прислал явный channelId (например,
 * старые боты, заведённые до появления мультиканальности). */
export const defaultChannelId: ChannelId = 'telegram'
