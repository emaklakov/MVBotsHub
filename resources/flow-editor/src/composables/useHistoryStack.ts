import { ref, computed } from 'vue'

export interface UseHistoryStackOptions {
    /** Максимум записей в истории (старые вытесняются). */
    maxEntries?: number
    /** Задержка дебаунса для commit(true), мс. */
    debounceMs?: number
}

/**
 * Стек снапшотов состояния для undo/redo. Не привязан к VueFlow/DOM —
 * принимает произвольный getState()/applyState() и работает с любым
 * JSON-сериализуемым состоянием, поэтому легко тестируется без монтирования
 * компонентов.
 *
 * - commit(false) — сразу фиксирует снапшот текущего состояния (для
 *   дискретных действий: добавили блок, удалили группу, провели связь).
 * - commit(true) — фиксирует с дебаунсом (для непрерывных изменений,
 *   например ввода текста в textarea) — иначе каждая нажатая клавиша
 *   становится отдельным шагом отмены.
 * - undo()/redo() применяют состояние через applyState() и не пишут
 *   новую запись в историю сами (иначе стек redo был бы неверным).
 */
export function useHistoryStack<T>(
    getState: () => T,
    applyState: (state: T) => void,
    options: UseHistoryStackOptions = {}
) {
    const maxEntries = options.maxEntries ?? 50
    const debounceMs = options.debounceMs ?? 500

    const clone = (state: T): T => JSON.parse(JSON.stringify(state))

    const history = ref<T[]>([clone(getState())]) as { value: T[] }
    const pointer = ref(0)
    let suspended = false
    let debounceTimer: ReturnType<typeof setTimeout> | null = null

    const commitNow = () => {
        if (suspended) return
        const snapshot = clone(getState())
        history.value = history.value.slice(0, pointer.value + 1)
        history.value.push(snapshot)
        pointer.value = history.value.length - 1

        const excess = history.value.length - maxEntries
        if (excess > 0) {
            history.value.splice(0, excess)
            pointer.value -= excess
        }
    }

    const flushPending = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer)
            debounceTimer = null
            commitNow()
        }
    }

    const commit = (debounce = false) => {
        if (suspended) return
        if (!debounce) {
            if (debounceTimer) {
                clearTimeout(debounceTimer)
                debounceTimer = null
            }
            commitNow()
            return
        }
        if (debounceTimer) clearTimeout(debounceTimer)
        debounceTimer = setTimeout(() => {
            debounceTimer = null
            commitNow()
        }, debounceMs)
    }

    const undo = () => {
        // Отложенное (дебаунс) изменение — например, недописанный текст —
        // сначала фиксируем как отдельный шаг, иначе оно потеряется молча.
        flushPending()
        if (pointer.value <= 0) return
        pointer.value--
        suspended = true
        applyState(clone(history.value[pointer.value]))
        suspended = false
    }

    const redo = () => {
        if (pointer.value >= history.value.length - 1) return
        pointer.value++
        suspended = true
        applyState(clone(history.value[pointer.value]))
        suspended = false
    }

    /** Сбросить историю к текущему состоянию — использовать после
     * асинхронной загрузки данных, чтобы загрузка не попала в undo-стек. */
    const reset = () => {
        flushPending()
        history.value = [clone(getState())]
        pointer.value = 0
    }

    const canUndo = computed(() => pointer.value > 0)
    const canRedo = computed(() => pointer.value < history.value.length - 1)

    return { commit, undo, redo, reset, canUndo, canRedo }
}
