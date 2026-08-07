import type { ChannelId, ChannelProfile } from './types'
import { channelRegistry, defaultChannelId } from './registry'

export type { Capability, ChannelId, ChannelProfile, ChannelLimits, ChannelSystemVariable } from './types'
export { channelHasCapability, channelSupportsAll } from './types'
export { channelRegistry, defaultChannelId } from './registry'

export function getChannelProfile(id: ChannelId): ChannelProfile {
    const profile = channelRegistry[id]
    if (!profile) throw new Error(`Неизвестный канал: "${id}". Проверьте src/channels/registry.ts`)
    return profile
}

export function isKnownChannelId(id: string): id is ChannelId {
    return id in channelRegistry
}

/** Безопасно превращает произвольную строку (например, из data-атрибута
 * на странице, который бэкенд может не прислать или прислать опечаткой)
 * в существующий ChannelId. Неизвестное значение тихо откатывается на
 * канал по умолчанию — редактор не должен падать из-за рассинхрона
 * фронта и бэкенда по названию канала. */
export function resolveChannelId(raw: string | null | undefined): ChannelId {
    if (raw && isKnownChannelId(raw)) return raw
    return defaultChannelId
}
