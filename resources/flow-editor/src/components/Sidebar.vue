<script setup lang="ts">
import { ref, computed } from 'vue'
import type { FlowBlockType } from '@/types/flow'
import { blockCategories, listBlockDefinitions } from '@/blocks'

interface LibraryItem {
    type: FlowBlockType
    label: string
    hint: string
    icon: string
}

interface Category {
    key: string
    label: string
    items: LibraryItem[]
}

const emit = defineEmits<{ add: [type: string] }>()

// Библиотека блоков строится из реестра (src/blocks) — категории и
// список типов внутри них здесь больше не дублируются. Новый тип блока
// появится в сайдбаре сам, как только будет добавлен в реестр.
const categories = computed<Category[]>(() =>
    blockCategories.map((category) => ({
        key: category.key,
        label: category.label,
        items: listBlockDefinitions()
            .filter((def) => def.category === category.key)
            .map((def) => ({ type: def.type, label: def.label, hint: def.hint, icon: def.icon })),
    }))
)

const query = ref('')

const openCategories = ref<Record<string, boolean>>(
    Object.fromEntries(blockCategories.map((c) => [c.key, true]))
)

const toggleCategory = (key: string) => {
    openCategories.value[key] = !openCategories.value[key]
}

const normalizedQuery = computed(() => query.value.trim().toLowerCase())

const filteredResults = computed<LibraryItem[]>(() => {
    if (!normalizedQuery.value) return []
    return categories.value
        .flatMap((c) => c.items)
        .filter(
            (item) =>
                item.label.toLowerCase().includes(normalizedQuery.value) ||
                item.hint.toLowerCase().includes(normalizedQuery.value)
        )
})

const onDragStart = (event: DragEvent, type: FlowBlockType) => {
    if (!event.dataTransfer) return
    event.dataTransfer.effectAllowed = 'copy'
    event.dataTransfer.setData('application/x-flow-block-type', type)
}
</script>

<template>
    <div class="sidebar">
        <div class="search">
            <input id="search" v-model="query" type="text" placeholder="Поиск блока…" />
        </div>

        <!-- Поиск активен: показываем плоский список найденных блоков -->
        <div v-if="normalizedQuery" class="search-results">
            <div v-if="!filteredResults.length" class="empty-hint">Ничего не найдено</div>
            <div
                v-for="item in filteredResults"
                :key="item.type"
                class="library-item"
                draggable="true"
                @dragstart="onDragStart($event, item.type)"
                @click="emit('add', item.type)"
            >
                <span class="item-icon">{{ item.icon }}</span>
                <div class="item-text">
                    <div class="item-label">{{ item.label }}</div>
                    <div class="item-hint">{{ item.hint }}</div>
                </div>
            </div>
        </div>

        <!-- Поиск пуст: обычные категории-аккордеоны -->
        <div v-else class="categories">
            <div v-for="category in categories" :key="category.key" class="category">
                <button class="category-header" @click="toggleCategory(category.key)">
                    <span>{{ category.label }}</span>
                    <span class="chevron" :class="{ open: openCategories[category.key] }">▾</span>
                </button>
                <div v-show="openCategories[category.key]" class="category-items">
                    <div
                        v-for="item in category.items"
                        :key="item.type"
                        class="library-item"
                        draggable="true"
                        @dragstart="onDragStart($event, item.type)"
                        @click="emit('add', item.type)"
                    >
                        <span class="item-icon">{{ item.icon }}</span>
                        <div class="item-text">
                            <div class="item-label">{{ item.label }}</div>
                            <div class="item-hint">{{ item.hint }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.sidebar { width: 220px; background: var(--color-surface); border-right: 1px solid var(--color-stroke); overflow-y: auto; display: flex; flex-direction: column; }
.search { padding-top: var(--space-3); padding-right: var(--space-3); padding-bottom: var(--space-3); border-bottom: 1px solid var(--color-stroke); }
.search input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--color-stroke);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
    box-sizing: border-box;
    background: var(--color-surface);
    color: var(--color-text);
}
.empty-hint { padding: 16px 12px; color: var(--color-text-muted); font-size: var(--font-size-sm); font-style: italic; text-align: center; }

.category-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: var(--space-3); padding-right: var(--space-3); padding-bottom: var(--space-3);
    background: none;
    border: none;
    border-bottom: 1px solid var(--color-stroke);
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--color-text-muted);
    cursor: pointer;
}
.chevron { transition: transform 0.15s ease; }
.chevron.open { transform: rotate(180deg); }

.category-items, .search-results { padding-top: var(--space-2); padding-right: var(--space-2); padding-bottom: var(--space-2); display: flex; flex-direction: column; gap: var(--space-1); }

.library-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2);
    border-radius: var(--radius-sm);
    cursor: grab;
    border: 1px solid transparent;
}
.library-item:hover { background: var(--color-surface-50); border-color: var(--color-stroke); }
.library-item:active { cursor: grabbing; }
.item-icon { font-size: var(--font-size-lg); flex-shrink: 0; }
.item-text { min-width: 0; }
.item-label { font-size: var(--font-size-base); font-weight: 600; color: var(--color-text); }
.item-hint { font-size: var(--font-size-xs); color: var(--color-text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
