<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const logoPath = computed(() => page.props.school?.logo_path || '/sample-logo.png');

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
  <img
    :src="logoPath"
    alt="Logo"
    :class="className"
    v-bind="$attrs"
  />
</template>


