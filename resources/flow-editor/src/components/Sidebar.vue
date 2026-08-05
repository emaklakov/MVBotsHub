<script setup lang="ts">
import { ref, computed } from 'vue'
import type { FlowBlockType } from '@/types/flow'

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

// Категории пока отражают ровно те типы блоков, которые реально
// поддержаны схемой/бэкендом/редакторами свойств (text/input/button).
// Расширение набора типов (фото, видео, условия и т.д.) — отдельная
// задача, требующая изменений в схеме, PropertiesPanel и валидации
// на бэкенде, поэтому оставлена за рамками этой фазы.
const categories: Category[] = [
    {
        key: 'bubbles',
        label: 'Bubbles',
        items: [
            { type: 'text', label: 'Текст', hint: 'Сообщение от бота', icon: '💬' },
        ],
    },
    {
        key: 'inputs',
        label: 'Inputs',
        items: [
            { type: 'input', label: 'Вопрос', hint: 'Ждём текстовый ответ пользователя', icon: '✏️' },
            { type: 'button', label: 'Кнопки', hint: 'Выбор одного из вариантов', icon: '🔘' },
        ],
    },
]

const query = ref('')

const openCategories = ref<Record<string, boolean>>(
    Object.fromEntries(categories.map((c) => [c.key, true]))
)

const toggleCategory = (key: string) => {
    openCategories.value[key] = !openCategories.value[key]
}

const normalizedQuery = computed(() => query.value.trim().toLowerCase())

const filteredResults = computed<LibraryItem[]>(() => {
    if (!normalizedQuery.value) return []
    return categories
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
            <input v-model="query" type="text" placeholder="Поиск блока…" />
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
.sidebar { width: 220px; background: #fff; border-right: 1px solid #e2e8f0; overflow-y: auto; display: flex; flex-direction: column; }
.search { padding: 12px; border-bottom: 1px solid #e2e8f0; }
.search input { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
.empty-hint { padding: 16px 12px; color: #94a3b8; font-size: 12px; font-style: italic; text-align: center; }

.category-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: none;
    border: none;
    border-bottom: 1px solid #f1f5f9;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    cursor: pointer;
}
.chevron { transition: transform 0.15s ease; }
.chevron.open { transform: rotate(180deg); }

.category-items, .search-results { padding: 6px; display: flex; flex-direction: column; gap: 4px; }

.library-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 6px;
    cursor: grab;
    border: 1px solid transparent;
}
.library-item:hover { background: #f8fafc; border-color: #e2e8f0; }
.library-item:active { cursor: grabbing; }
.item-icon { font-size: 16px; flex-shrink: 0; }
.item-text { min-width: 0; }
.item-label { font-size: 13px; font-weight: 600; color: #1e293b; }
.item-hint { font-size: 11px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
