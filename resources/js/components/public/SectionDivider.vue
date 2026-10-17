<script setup lang="ts">
/**
 * A soft wave seam between two stacked sections. `backdrop` is the full
 * bg-* class of the section ABOVE the divider, `fill` is the full text-*
 * class matching the color of the section BELOW it, so the curve reads as a
 * smooth transition rather than a hard color cut. Both must be passed as
 * complete class strings (not built from a color name here) so Tailwind's
 * scanner can see the literal class text in the caller's template.
 */
defineProps<{
    backdrop?: string;
    fill: string;
}>();
</script>

<template>
    <div class="relative h-10 overflow-hidden md:h-14" :class="backdrop ?? 'bg-background'" aria-hidden="true">
        <svg class="absolute inset-0 h-full w-full" :class="fill" viewBox="0 0 1440 80" preserveAspectRatio="none">
            <path fill="currentColor" d="M0,24 C280,70 480,0 720,16 C960,32 1160,74 1440,20 L1440,80 L0,80 Z" />
        </svg>
    </div>
</template>
