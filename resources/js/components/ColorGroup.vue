<template>
  <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h4>
    <p class="mb-6 text-xs text-gray-500 dark:text-gray-400">{{ description }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="mb-3 block text-xs font-medium text-gray-700 dark:text-gray-300">Background</label>
        <div class="flex items-center space-x-3">
          <button
            @click="$emit('bg-change', bgColor)"
            :title="bgColor"
            class="h-12 w-12 rounded-lg border-2 border-gray-300 transition-colors hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500"
            :style="{ backgroundColor: bgColor }"
          ></button>
          <input
            :value="bgColor"
            type="color"
            class="h-8 w-8 cursor-pointer rounded border border-gray-300 bg-transparent dark:border-gray-600"
            @input="$emit('bg-change', ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
      <div>
        <label class="mb-3 block text-xs font-medium text-gray-700 dark:text-gray-300">Text</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="color in safeTextColors"
            :key="color"
            @click="$emit('text-change', color)"
            :class="[
              'h-8 w-8 rounded border-2 transition-colors',
              textColor === color
                ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-500/30'
                : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500'
            ]"
            :style="{ backgroundColor: color }"
            :title="color"
          ></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  description: string;
  bgColor: string;
  textColor: string;
  safeTextColors: string[];
}

defineProps<Props>();

defineEmits<{
  'bg-change': [color: string];
  'text-change': [color: string];
}>();
</script>
