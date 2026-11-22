<template>
  <AppShell>
    <slot />
  </AppShell>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppShell from '@/components/layout/AppShell.vue';
import { alert } from '@/utils';

const page = usePage();

watch(
  () => page.props.flash as { warning?: string | null; error?: string | null } | undefined,
  (flash) => {
    if (flash?.warning) {
      alert.warning(flash.warning);
    } else if (flash?.error) {
      alert.error(flash.error);
    }
  },
  { immediate: true },
);
</script>
