<template>
  <footer class="bg-card border-t border-border">
    <div class="px-4 py-3 text-center text-sm text-muted-foreground">
      © {{ currentYear }} {{ schoolName }}
    </div>
  </footer>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const currentYear = computed(() => new Date().getFullYear())
const schoolName = computed(() => page.props.name as string)
</script>
