<template>
  <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
    <h4 class="text-sm font-semibold text-gray-900 mb-2">{{ title }}</h4>
    <p class="text-xs text-gray-500 mb-6">{{ description }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-3">Background</label>
        <div class="flex items-center space-x-3">
          <button
            @click="$emit('bg-change', bgColor)"
            :title="bgColor"
            class="w-12 h-12 rounded-lg border-2 border-gray-300 hover:border-gray-400 transition-colors"
            :style="{ backgroundColor: bgColor }"
          ></button>
          <input
            :value="bgColor"
            type="color"
            class="w-8 h-8 rounded border border-gray-300 cursor-pointer"
            @input="$emit('bg-change', ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-3">Text</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="color in safeTextColors"
            :key="color"
            @click="$emit('text-change', color)"
            :class="[
              'w-8 h-8 rounded border-2 transition-colors',
              textColor === color ? 'border-indigo-500' : 'border-gray-300 hover:border-gray-400'
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
