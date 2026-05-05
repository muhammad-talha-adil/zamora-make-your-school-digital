<template>
  <div
    @click="$emit('select', palette.id)"
    class="relative cursor-pointer rounded-xl border-2 bg-white/90 p-4 transition-all hover:shadow-md dark:bg-gray-900/80"
    :class="selected ? 'border-indigo-500 ring-2 ring-indigo-200 shadow-sm dark:ring-indigo-500/30' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-500'"
  >
    <div class="flex space-x-1 mb-3">
      <div
        v-for="color in palette.colors.slice(0, 4)"
        :key="color.slot"
        class="flex-1 h-8 rounded-md"
        :style="{ backgroundColor: color.hex }"
      ></div>
    </div>
    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ palette.name }}</div>
    <div v-if="selected" class="absolute top-2 right-2 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center">
      <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  palette: any;
  selected: boolean;
}

defineProps<Props>();

defineEmits<{
  select: [id: number];
}>();
</script>
