<template>
  <footer class="bg-card border-t border-border">
    <div class="px-4 py-3 text-center text-sm text-muted-foreground">
      © {{ currentYear }} {{ schoolName }}
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
const schoolName = import.meta.env.VITE_SCHOOL_NAME || 'School'
</script>
