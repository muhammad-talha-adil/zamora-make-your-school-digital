<template>
  <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <div class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
      © {{ currentYear }} {{ schoolName }}
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
const schoolName = import.meta.env.VITE_SCHOOL_NAME || 'School'
</script>
