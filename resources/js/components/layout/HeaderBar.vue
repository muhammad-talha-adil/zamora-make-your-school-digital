<template>
  <header class="bg-card shadow-sm border-b border-border">
    <div class="flex flex-wrap gap-2 items-center justify-between px-4 py-3">
      <!-- Hamburger Menu for Mobile -->
      <button
        @click="toggleSidebar"
        class="md:hidden p-2 rounded-md text-muted-foreground hover:bg-accent"
      >
        <Bars3Icon class="w-6 h-6" />
      </button>

      <!-- Title -->
      <h1 class="text-xl font-semibold text-foreground">{{ schoolName }}</h1>

      <!-- Right Side: User Menu -->
      <div class="flex items-center space-x-4">
        <span class="text-sm text-muted-foreground">Welcome, {{ $page.props.auth.user?.name }}</span>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="px-4 py-2 bg-destructive text-destructive-foreground text-sm font-medium rounded-md hover:bg-destructive/90 transition-colors"
        >
          Logout
        </Link>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Bars3Icon } from '@heroicons/vue/24/outline'
import { useSidebar } from '../../composables/useSidebar'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const { toggle: toggleSidebar } = useSidebar()
const page = usePage()
const schoolName = computed(() => page.props.name)
</script>
