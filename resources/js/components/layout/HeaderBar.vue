<template>
  <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between px-4 py-3">
      <!-- Hamburger Menu for Mobile -->
      <button
        @click="toggleSidebar"
        class="md:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
      >
        <Bars3Icon class="w-6 h-6" />
      </button>

      <!-- Title -->
      <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ schoolName }}</h1>

      <!-- Right Side: User Menu -->
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-700 dark:text-gray-300">Welcome, {{ $page.props.auth.user?.name }}</span>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors"
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
