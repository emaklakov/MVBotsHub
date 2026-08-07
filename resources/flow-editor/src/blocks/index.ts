import type { BlockConfig, BlockContent, FlowBlockType } from '@/types/flow'
import type { ChannelProfile } from '@/channels'
import { channelSupportsAll } from '@/channels'
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

/** true, если у канала есть все возможности, которые требует блок (см.
 * BlockDefinition.requiresCapabilities). Универсальные блоки без
 * requiresCapabilities доступны любому каналу всегда. */
export function isBlockAvailableForChannel(def: BlockDefinition, channel: ChannelProfile): boolean {
    return channelSupportsAll(channel, def.requiresCapabilities)
}

/** Список блоков, доступных данному каналу — то, чем реально
 * пользуется Sidebar при построении библиотеки блоков. */
export function listBlockDefinitionsForChannel(channel: ChannelProfile): BlockDefinition[] {
    return listBlockDefinitions().filter((def) => isBlockAvailableForChannel(def, channel))
}
