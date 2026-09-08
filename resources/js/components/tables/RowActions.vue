<script setup lang="ts">
/**
 * The container for a row's action icons.
 *
 * Wraps the group in a single TooltipProvider so each row does not create its
 * own, and keeps the spacing and alignment identical across every table.
 */
import { TooltipProvider } from '@/components/ui/tooltip';
</script>

<template>
    <TooltipProvider :delay-duration="150">
        <div class="flex items-center justify-end gap-1">
            <slot />
        </div>
    </TooltipProvider>
</template>
