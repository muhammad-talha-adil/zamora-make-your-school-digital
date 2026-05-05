<template>
  <div class="relative" ref="containerRef">
    <input
      v-model="query"
      :placeholder="placeholder"
      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      :class="classMinWidth"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
    />
    <div
      v-if="isOpen && displayItems.length > 0"
      class="absolute top-full left-0 z-[100] mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg max-h-60 overflow-auto dark:border-gray-600 dark:bg-gray-800"
    >
       <div
         v-for="item in displayItems"
         :key="item.id"
         class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-white whitespace-nowrap"
         @mousedown.prevent
         @click="selectItem(item)"
       >
         {{ getItemDisplayText(item) }}
       </div>
    </div>
    <div
      v-if="isOpen && displayItems.length === 0 && query.length > 0"
      class="absolute top-full left-0 z-[100] mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
    >
      <div class="px-3 py-2 text-gray-500 dark:text-gray-400">
        No results found
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface Item {
  id: number | string
  name: string
  display_text?: string
  class_name?: string
  section_name?: string
  [key: string]: any
}

interface Props {
  modelValue?: number | string | null
  placeholder?: string
  searchUrl?: string
  initialItems?: Item[]
  valueType?: 'id' | 'name'
  displayKey?: string
  classMinWidth?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  placeholder: 'Search...',
  searchUrl: '',
  initialItems: () => [] as Item[],
  valueType: 'id',
  displayKey: 'name',
  classMinWidth: 'min-w-[200px]',
})

const emit = defineEmits<{
  'update:modelValue': [value: number | string | null]
}>()

const containerRef = ref<HTMLElement | null>(null)
const query = ref('')
const isOpen = ref(false)
const debounceTimer = ref<number | null>(null)
const allItems = ref<Item[]>([])

watch(() => props.initialItems, (newItems) => {
  allItems.value = [...newItems]
}, { deep: true })

allItems.value = [...props.initialItems]

// Function to get display text for an item
const getItemDisplayText = (item: Item): string => {
  // If display_text is provided, use it
  if (item.display_text) {
    return item.display_text
  }
  // If displayKey is provided, use it
  if (props.displayKey && item[props.displayKey]) {
    return item[props.displayKey]
  }
  // Default to name
  return item.name || String(item.id)
}

const displayItems = computed(() => {
  const items = allItems.value
  if (query.value.length === 0) {
    return items.slice(0, 10)
  }
  const lowerQuery = query.value.toLowerCase()
  return items.filter((item: Item) =>
    getItemDisplayText(item).toLowerCase().includes(lowerQuery)
  ).slice(0, 10)
})

const selectedItem = computed(() => {
  return allItems.value.find((item: Item) => {
    if (props.valueType === 'id') {
      return item.id === props.modelValue
    }
    // For 'name' valueType, match against the display text
    // Use displayKey if specified, otherwise use name
    const displayProperty = props.displayKey || 'name'
    return item[displayProperty] === props.modelValue || getItemDisplayText(item) === props.modelValue
  })
})

watch(selectedItem, (newItem) => {
  if (newItem) {
    query.value = getItemDisplayText(newItem)
  } else {
    query.value = ''
  }
}, { immediate: true })

const searchItems = async (searchQuery: string) => {
  if (!props.searchUrl) {
    return
  }

  try {
    // Use & if searchUrl already has query params, otherwise use ?
    const separator = props.searchUrl.includes('?') ? '&' : '?';
    const response = await fetch(`${props.searchUrl}${separator}q=${encodeURIComponent(searchQuery)}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    })
    const data = await response.json()
    const fetchedItems = Array.isArray(data) ? data as Item[] : []
    
    // Merge server results with initial items
    const itemsMap = new Map<string, Item>()
    allItems.value.forEach(item => {
      itemsMap.set(String(item.id), item)
    })
    fetchedItems.forEach(item => {
      itemsMap.set(String(item.id), item)
    })
    allItems.value = Array.from(itemsMap.values())
  } catch (error) {
    console.error('Search failed:', error)
  }
}

const onInput = () => {
  isOpen.value = true

  if (
    props.valueType === 'id' &&
    selectedItem.value &&
    query.value !== getItemDisplayText(selectedItem.value)
  ) {
    emit('update:modelValue', null)
  }

  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }

  debounceTimer.value = window.setTimeout(() => {
    if (query.value.length > 0) {
      searchItems(query.value)
    } else {
      allItems.value = [...props.initialItems]
    }
  }, 300)
}

const onFocus = () => {
  isOpen.value = true
  if (query.value.length === 0) {
    allItems.value = [...props.initialItems]
  }
}

const onBlur = () => {
  window.setTimeout(() => {
    isOpen.value = false
    if (props.valueType === 'id') {
      if (selectedItem.value) {
        query.value = getItemDisplayText(selectedItem.value)
      } else if (!query.value.trim()) {
        emit('update:modelValue', null)
      }
      return
    }

    if (query.value.trim() && !selectedItem.value) {
      emit('update:modelValue', query.value)
    }
  }, 150)
}

const selectItem = (item: Item) => {
  const value = props.valueType === 'id' ? item.id : item.name
  emit('update:modelValue', value)
  query.value = getItemDisplayText(item)
  isOpen.value = false
}
</script>
