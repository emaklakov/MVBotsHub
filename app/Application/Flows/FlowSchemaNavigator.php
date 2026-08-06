<?php

declare(strict_types=1);

namespace App\Application\Flows;

use App\Domain\Flows\Models\FlowVersion;

/**
 * Адаптер для навигации по схеме flow-editor.
 * Изолирует engine от деталей JSON-структуры.
 */
final class FlowSchemaNavigator
{
    private array $schema;

    public function __construct(FlowVersion $version)
    {
        $this->schema = $version->schema ?? [];
    }

    public function getStartGroupId(): ?string
    {
        return $this->schema['start_group_id'] ?? null;
    }

    public function getGroup(string $groupId): ?array
    {
        return $this->schema['groups'][$groupId] ?? null;
    }

    /**
     * Блоки группы в порядке их следования (сверху вниз).
     * @return list<array>
     */
    public function getBlocksInGroup(string $groupId): array
    {
        $group = $this->getGroup($groupId);
        if (!$group) {
            return [];
        }

        $blocks = [];
        foreach ($group['block_ids'] ?? [] as $blockId) {
            $block = $this->schema['blocks'][$blockId] ?? null;
            if ($block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    public function getBlock(string $blockId): ?array
    {
        return $this->schema['blocks'][$blockId] ?? null;
    }

    /**
     * Найти ID следующей группы.
     * Для condition: передаётся branch ('true'/'false').
     */
    public function getNextGroupId(string $blockId, ?string $branch = null): ?string
    {
        // Для condition и многовыходных блоков: ищем edge по source_block_id + source_handle
        foreach ($this->schema['edges'] ?? [] as $edge) {
            if (($edge['source_block_id'] ?? null) !== $blockId) {
                continue;
            }

            // Если branch не указан — берём первую попавшуюся (обратная совместимость)
            // Если branch указан — строгое совпадение source_handle
            $edgeBranch = $edge['source_handle'] ?? null;

            if ($branch === null) {
                return $edge['target_group_id'] ?? null;
            }

            if ($edgeBranch === $branch) {
                return $edge['target_group_id'] ?? null;
            }
        }

        // Fallback: обычные блоки с outgoing_edge_id
        $block = $this->getBlock($blockId);
        if ($block && !empty($block['outgoing_edge_id'])) {
            $edge = $this->schema['edges'][$block['outgoing_edge_id']] ?? null;
            return $edge['target_group_id'] ?? null;
        }

        return null;
    }

    /**
     * Найти следующий блок внутри группы.
     */
    public function getNextBlockInGroup(string $groupId, string $currentBlockId): ?array
    {
        $group = $this->getGroup($groupId);
        $blockIds = $group['block_ids'] ?? [];
        $index = array_search($currentBlockId, $blockIds, true);

        if ($index === false || !isset($blockIds[$index + 1])) {
            return null;
        }

        return $this->getBlock($blockIds[$index + 1]);
    }

    public function isLastBlockInGroup(string $groupId, string $blockId): bool
    {
        $group = $this->getGroup($groupId);
        $blockIds = $group['block_ids'] ?? [];
        $lastId = $blockIds[count($blockIds) - 1] ?? null;

        return $lastId === $blockId;
    }
}
