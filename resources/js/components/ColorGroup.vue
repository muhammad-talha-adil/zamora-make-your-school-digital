<template>
  <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
    <h4 class="mb-2 text-sm font-semibold text-foreground">{{ title }}</h4>
    <p class="mb-6 text-xs text-muted-foreground">{{ description }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="mb-3 block text-xs font-medium text-muted-foreground">Background</label>
        <div class="flex items-center space-x-3">
          <button
            @click="$emit('bg-change', bgColor)"
            :title="bgColor"
            class="h-12 w-12 rounded-lg border-2 border-border transition-colors hover:border-border"
            :style="{ backgroundColor: bgColor }"
          ></button>
          <input
            :value="bgColor"
            type="color"
            class="h-8 w-8 cursor-pointer rounded border border-border bg-transparent"
            @input="$emit('bg-change', ($event.target as HTMLInputElement).value)"
          />
        </div>
      </div>
      <div>
        <label class="mb-3 block text-xs font-medium text-muted-foreground">Text</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="color in safeTextColors"
            :key="color"
            @click="$emit('text-change', color)"
            :class="[
              'h-8 w-8 rounded border-2 transition-colors',
              textColor === color
                ? 'border-primary ring-2 ring-primary/40'
                : 'border-border hover:border-border'
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
