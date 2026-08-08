import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useHistoryStack } from '../useHistoryStack'

describe('useHistoryStack', () => {
    let state: { value: number }
    const getState = () => ({ value: state.value })
    const applyState = (s: { value: number }) => {
        state.value = s.value
    }

    beforeEach(() => {
        state = { value: 0 }
    })

    it('undo() ничего не делает, если истории для отката ещё нет', () => {
        const history = useHistoryStack(getState, applyState)
        expect(history.canUndo.value).toBe(false)
        history.undo()
        expect(state.value).toBe(0)
    })

    it('commit(false) сразу фиксирует снапшот, undo() возвращает к предыдущему', () => {
        const history = useHistoryStack(getState, applyState)

        state.value = 1
        history.commit(false)
        state.value = 2
        history.commit(false)

        expect(state.value).toBe(2)
        history.undo()
        expect(state.value).toBe(1)
        history.undo()
        expect(state.value).toBe(0)
        expect(history.canUndo.value).toBe(false)
    })

    it('redo() возвращает состояние вперёд после undo()', () => {
        const history = useHistoryStack(getState, applyState)

        state.value = 1
        history.commit(false)
        history.undo()
        expect(state.value).toBe(0)

        expect(history.canRedo.value).toBe(true)
        history.redo()
        expect(state.value).toBe(1)
        expect(history.canRedo.value).toBe(false)
    })

    it('новый commit() после undo() обрезает "будущее" (redo больше недоступен)', () => {
        const history = useHistoryStack(getState, applyState)

        state.value = 1
        history.commit(false)
        state.value = 2
        history.commit(false)

        history.undo() // вернулись к value=1, redo указывает на value=2
        expect(history.canRedo.value).toBe(true)

        state.value = 99
        history.commit(false) // новая ветка — старый "future" (value=2) должен исчезнуть

        expect(history.canRedo.value).toBe(false)
        history.undo()
        expect(state.value).toBe(1)
    })

    it('reset() обнуляет историю к текущему состоянию (undo/redo недоступны)', () => {
        const history = useHistoryStack(getState, applyState)

        state.value = 1
        history.commit(false)
        state.value = 2
        history.reset()

        expect(history.canUndo.value).toBe(false)
        expect(history.canRedo.value).toBe(false)
        history.undo()
        expect(state.value).toBe(2) // не откатилось, т.к. история сброшена именно на это состояние
    })

    it('ограничивает размер истории maxEntries, вытесняя самые старые записи', () => {
        const history = useHistoryStack(getState, applyState, { maxEntries: 3 })

        for (let i = 1; i <= 5; i++) {
            state.value = i
            history.commit(false)
        }
        // История: [0,1,2,3,4,5] -> ограничена 3 записями -> [3,4,5]
        history.undo()
        history.undo()
        expect(state.value).toBe(3)
        expect(history.canUndo.value).toBe(false)
    })

    describe('commit(true) — дебаунс', () => {
        beforeEach(() => {
            vi.useFakeTimers()
        })
        afterEach(() => {
            vi.useRealTimers()
        })

        it('несколько commit(true) подряд схлопываются в одну запись истории', () => {
            const history = useHistoryStack(getState, applyState, { debounceMs: 500 })

            state.value = 1
            history.commit(true)
            state.value = 2
            history.commit(true)
            state.value = 3
            history.commit(true)

            vi.advanceTimersByTime(500)

            // Три быстрых правки — но должен получиться ОДИН шаг истории с
            // финальным значением, а не три отдельных шага по одному на ввод.
            expect(state.value).toBe(3)
            history.undo()
            expect(state.value).toBe(0)
            expect(history.canUndo.value).toBe(false)
        })

        it('undo() во время ожидающего commit(true) сначала фиксирует его, потом откатывает', () => {
            const history = useHistoryStack(getState, applyState, { debounceMs: 500 })

            state.value = 1
            history.commit(true) // ещё не сработал (таймер не истёк)

            history.undo() // должен сначала зафиксировать value=1, затем откатить к value=0
            expect(state.value).toBe(0)

            history.redo()
            expect(state.value).toBe(1)
        })
    })
})
