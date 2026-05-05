<template>
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-0 min-h-screen',
      isOpen ? 'translate-x-0' : '-translate-x-full'
    ]"
  >
    <div class="flex flex-col h-full">
      <!-- Sidebar Header -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Navigation</h2>
        <!-- Close button for mobile -->
        <button
          @click="closeSidebar"
          class="md:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-4 py-6 space-y-2">
        <Link
          v-for="item in mainNavItems"
          :key="item.title"
          :href="item.href"
          :class="[
            'flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors',
            $page.url === item.href
              ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
          ]"
        >
          {{ (console.log('mainNavItems item.icon:', item.icon), '') }}
          <component :is="item.icon || 'div'" class="w-5 h-5 mr-3" />
          {{ item.title }}
        </Link>
        <!-- Settings with submenus -->
        <div class="space-y-1">
          <button
            @click="settingsOpen = !settingsOpen"
            class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors"
          >
            <SettingsIcon class="w-5 h-5 mr-3" />
            Settings
            <ChevronDownIcon :class="['w-4 h-4 ml-auto transition-transform', settingsOpen ? 'rotate-180' : '']" />
          </button>
          <div v-if="settingsOpen" class="ml-8 space-y-1">
            <Link
              v-for="child in settingsItems"
              :key="child.title"
              :href="child.href"
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors',
                $page.url === child.href
                  ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
              ]"
            >
              {{ (console.log('settingsItems child.icon:', child.icon), '') }}
              <component :is="child.icon || 'div'" class="w-4 h-4 mr-2 text-gray-600 dark:text-gray-400" />
              {{ child.title }}
            </Link>
          </div>
        </div>
      </nav>
    </div>
  </aside>

  <!-- Backdrop for Mobile -->
  <div
    v-if="isOpen"
    @click="closeSidebar"
    class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
  ></div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { XMarkIcon, SettingsIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useSidebar } from '../../composables/useSidebar'
import { mainNavItems, footerNavItems } from '../../menu'

const { isOpen, close: closeSidebar } = useSidebar()

const settingsOpen = ref(false)

const settingsItems = footerNavItems.find(item => item.title === 'Settings')?.children || []
</script>
