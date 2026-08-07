import { describe, it, expect } from 'vitest'
import {
    getChannelProfile,
    resolveChannelId,
    isKnownChannelId,
    channelHasCapability,
    channelSupportsAll,
    defaultChannelId,
} from '../index'

describe('channelRegistry / getChannelProfile', () => {
    it('telegram зарегистрирован и имеет ожидаемые лимиты', () => {
        const telegram = getChannelProfile('telegram')
        expect(telegram.id).toBe('telegram')
        expect(telegram.limits.maxTextLength).toBe(4096)
        expect(telegram.limits.maxCaptionLength).toBe(1024)
        expect(telegram.limits.callbackDataMaxBytes).toBe(64)
        expect(telegram.limits.maxPollQuestionLength).toBe(300)
        expect(telegram.limits.maxPollOptions).toBe(10)
    })

    it('telegram умеет poll (Фаза 2)', () => {
        expect(getChannelProfile('telegram').capabilities).toContain('poll')
    })

    it('бросает понятную ошибку для незарегистрированного канала', () => {
        // @ts-expect-error — намеренно передаём заведомо неизвестный канал
        expect(() => getChannelProfile('web_widget')).toThrow(/Неизвестный канал/)
    })
})

describe('resolveChannelId', () => {
    it('возвращает известный канал как есть', () => {
        expect(resolveChannelId('telegram')).toBe('telegram')
    })

    it('откатывается на канал по умолчанию для пустого/неизвестного значения', () => {
        expect(resolveChannelId(undefined)).toBe(defaultChannelId)
        expect(resolveChannelId(null)).toBe(defaultChannelId)
        expect(resolveChannelId('')).toBe(defaultChannelId)
        expect(resolveChannelId('web_widget')).toBe(defaultChannelId)
        expect(resolveChannelId('whatsapp')).toBe(defaultChannelId)
    })
})

describe('isKnownChannelId', () => {
    it('true только для telegram на сегодня', () => {
        expect(isKnownChannelId('telegram')).toBe(true)
        expect(isKnownChannelId('web_widget')).toBe(false)
    })
})

describe('channelHasCapability / channelSupportsAll', () => {
    const telegram = getChannelProfile('telegram')

    it('находит реально заявленную возможность', () => {
        expect(channelHasCapability(telegram, 'reply_keyboard')).toBe(true)
        expect(channelHasCapability(telegram, 'geolocation')).toBe(true)
    })

    it('не находит незаявленную возможность', () => {
        expect(channelHasCapability(telegram, 'custom_html')).toBe(false)
    })

    it('channelSupportsAll: пустой/неуказанный список требований — доступно всегда', () => {
        expect(channelSupportsAll(telegram, undefined)).toBe(true)
        expect(channelSupportsAll(telegram, [])).toBe(true)
    })

    it('channelSupportsAll: все требуемые возможности должны быть у канала', () => {
        expect(channelSupportsAll(telegram, ['inline_buttons', 'geolocation'])).toBe(true)
        expect(channelSupportsAll(telegram, ['inline_buttons', 'custom_html'])).toBe(false)
    })
})
