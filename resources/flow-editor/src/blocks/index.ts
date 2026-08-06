import type { BlockConfig, BlockContent, FlowBlockType } from '@/types/flow'
import { blockRegistry, blockCategories } from './registry'
import { singleOutput, type BlockDefinition, type BlockOutput } from './types'

export type { BlockDefinition, BlockOutput, BlockCategoryMeta } from './types'
export { blockCategories }

/** Бросает понятную ошибку вместо undefined, если кто-то передал
 * несуществующий/устаревший тип блока (например, из повреждённых данных). */
export function getBlockDefinition(type: FlowBlockType): BlockDefinition {
    const def = blockRegistry[type]
    if (!def) throw new Error(`Неизвестный тип блока: "${type}". Проверьте src/blocks/registry.ts`)
    return def
}

export function listBlockDefinitions(): BlockDefinition[] {
    return Object.values(blockRegistry)
}

export function defaultBlockTitle(type: FlowBlockType): string {
    return getBlockDefinition(type).defaultTitle
}

export function defaultBlockContent(type: FlowBlockType): BlockContent {
    return getBlockDefinition(type).defaultContent()
}

export function defaultBlockConfig(type: FlowBlockType): BlockConfig {
    return getBlockDefinition(type).defaultConfig()
}

/**
 * Выходы блока данного типа. Блок без явного `outputs` в реестре
 * (обычный случай) считается блоком с одним обычным выходом.
 *
 * `type` принимается как возможно отсутствующий: вызывающий код обычно
 * берёт последний блок группы (`data.blocks[length - 1]`), а группа может
 * быть пустой — для пустой группы (как и для любого обычного блока)
 * результат — один обычный выход снизу.
 */
export function getBlockOutputs(type: FlowBlockType | undefined, config?: BlockConfig): BlockOutput[] {
    if (!type) return singleOutput
    const def = getBlockDefinition(type)
    return def.outputs ? def.outputs(config) : singleOutput
}

/** true, если блок этого типа умеет сохранять ответ пользователя в
 * переменную (см. BlockDefinition.producesVariable). */
export function blockProducesVariable(type: FlowBlockType): boolean {
    return Boolean(getBlockDefinition(type).producesVariable)
}
