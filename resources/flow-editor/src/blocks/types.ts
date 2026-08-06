import type { Component } from 'vue'
import type { BlockConfig, BlockContent, FlowBlockType } from '@/types/flow'

/**
 * Один выход блока на канвасе.
 *
 * Раньше "у condition два выхода, у остальных один" было зашито прямо в
 * GroupNode.vue и useFlowSerializer.ts (`type === 'condition'`). Это не
 * масштабируется — в будущем появятся другие блоки с несколькими выходами
 * (A/B-тест, Picture Choice с раскладкой по вариантам и т.п.), и каждый
 * такой блок пришлось бы точечно дописывать в те же два места.
 *
 * Теперь количество и подписи выходов — часть описания блока в реестре;
 * GroupNode и сериализатор работают с любым числом выходов одинаково.
 */
export interface BlockOutput {
    /**
     * null — единственный "обычный" выход группы: в FlowEdge.source_handle
     * тоже будет null. Строка — конкретный именованный хендл (id Handle на
     * канвасе), совпадает с source_handle соответствующего ребра.
     */
    handle: string | null
    /** Подпись рядом с хендлом (например, "True"/"False"). Для блока с
     * одним выходом не нужна и не отображается. */
    label?: string
    /** Цветовой акцент хендла/подписи. Без указания — нейтральный цвет. */
    tone?: 'success' | 'error' | 'neutral'
}

/** Единственный обычный выход — дефолт для блоков без явного описания outputs. */
export const singleOutput: BlockOutput[] = [{ handle: null }]

export interface BlockDefinition {
    type: FlowBlockType
    /** Ключ категории в библиотеке блоков (Sidebar), см. `blockCategories`. */
    category: string
    label: string
    hint: string
    icon: string
    /** Компактное отображение блока внутри группы на канвасе. */
    renderComponent: Component
    /** Редактор свойств блока в правой панели. Блок без настраиваемых
     * свойств может её не указывать — PropertiesPanel просто ничего не
     * отрисует под общими полями (ID блока и т.п.). */
    editorComponent?: Component
    defaultTitle: string
    defaultContent: () => BlockContent
    defaultConfig: () => BlockConfig
    /**
     * Выходы блока — определяют число Handle снизу группы, если этот блок
     * последний в группе (см. GroupNode.vue). По умолчанию (если не
     * указано) — один обычный выход, как singleOutput.
     * Принимает config блока, чтобы в будущем можно было варьировать
     * число выходов динамически (например, по числу веток блока).
     */
    outputs?: (config?: BlockConfig) => BlockOutput[]
    /**
     * true — блок умеет сохранять ответ пользователя в переменную
     * (через `config.variable`), поэтому должен участвовать в сборе
     * доступных переменных (collectVariables). Сейчас это input и button;
     * при добавлении новых input-подобных типов (number/email/phone/date/
     * rating и т.п. — Фазы 1-2) достаточно проставить этот флаг здесь,
     * без правок логики сбора переменных.
     */
    producesVariable?: boolean
}

export interface BlockCategoryMeta {
    key: string
    label: string
}
