<template>
  <div class="min-h-screen flex flex-col" :style="{ backgroundColor: 'var(--content-bg)', color: 'var(--content-text)' }">
    <!-- Top Nav -->
    <header class="shadow-sm border-b" :style="{ backgroundColor: 'var(--header-bg)', color: 'var(--header-text)', borderColor: 'var(--sidebar-border)' }">
      <div class="mx-auto max-w-5xl flex items-center gap-2 sm:gap-4 px-3 sm:px-4 py-3">
        <Link href="/portal" class="flex items-center gap-2 shrink-0">
          <AppLogo />
        </Link>

        <!-- Desktop links -->
        <nav class="hidden md:flex flex-1 items-center gap-1 ml-4">
          <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors"
            :style="{
              backgroundColor: isActive(item.href) ? 'var(--sidebar-active-bg)' : 'transparent',
              color: isActive(item.href) ? 'var(--sidebar-active-text)' : 'var(--header-text)',
            }"
          >
            <Icon :icon="item.icon" :size="18" />
            {{ item.title }}
          </Link>
        </nav>
        <div v-if="!navItems.length" class="flex-1" />

        <!-- Right side -->
        <div class="flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            @click="toggleTheme"
            aria-label="Toggle theme"
            class="p-2 rounded-md hover:opacity-75"
            :style="{ color: 'var(--header-text)' }"
          >
            <Sun v-if="resolvedAppearance === 'light'" class="w-5 h-5" />
            <Moon v-else class="w-5 h-5" />
          </button>
          <Link
            href="/settings/profile"
            aria-label="Profile"
            class="p-2 rounded-md hover:opacity-75"
            :style="{ color: 'var(--header-text)' }"
          >
            <User class="w-5 h-5" />
          </Link>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="hidden sm:inline-flex px-3 sm:px-4 py-2 text-sm font-medium rounded-md transition-colors"
            :style="{ backgroundColor: 'var(--sidebar-text)', color: 'var(--sidebar-bg)' }"
          >
            Logout
          </Link>
          <button
            @click="mobileNavOpen = !mobileNavOpen"
            class="md:hidden p-2 rounded-md hover:opacity-75"
            aria-label="Toggle navigation"
            :style="{ color: 'var(--header-text)' }"
          >
            <Menu class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Mobile links -->
      <nav v-if="mobileNavOpen" class="md:hidden border-t px-3 py-2 space-y-1" :style="{ borderColor: 'var(--sidebar-border)' }">
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors"
          :style="{
            backgroundColor: isActive(item.href) ? 'var(--sidebar-active-bg)' : 'transparent',
            color: isActive(item.href) ? 'var(--sidebar-active-text)' : 'var(--header-text)',
          }"
          @click="mobileNavOpen = false"
        >
          <Icon :icon="item.icon" :size="18" />
          {{ item.title }}
        </Link>
        <Link href="/logout" method="post" as="button" class="flex w-full items-center gap-2 px-3 py-2 text-sm font-medium rounded-md" :style="{ color: 'var(--header-text)' }">
          <LogOut class="w-5 h-5" />
          Logout
        </Link>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 mx-auto w-full max-w-5xl px-4 py-6 sm:px-6">
      <slot />
    </main>

    <FooterBar />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Sun, Moon, LogOut, Menu, User } from 'lucide-vue-next'
import { useAppearance } from '../composables/useAppearance'
import AppLogo from '../components/AppLogo.vue'
import Icon from '../components/Icon.vue'
import FooterBar from '../components/layout/FooterBar.vue'

/**
 * Portal-facing shell for the student/guardian self-service area:
 * intentionally a slim top-nav rather than the admin `AppShell`'s dense
 * multi-module sidebar, since a family only ever needs a handful of links.
 * Reuses the same design tokens/dark-mode variables as the admin layout so
 * it reads as the same product, not a separate app.
 */
const navItems = [
  { title: 'Dashboard', href: '/portal', icon: 'layout-dashboard' },
  { title: 'Fees', href: '/portal/fees', icon: 'wallet' },
  { title: 'Exam Results', href: '/portal/exams', icon: 'clipboard-list' },
  { title: 'Attendance', href: '/portal/attendance', icon: 'calendar-check' },
]

const page = usePage()
const currentUrl = computed(() => page.url || '')
const { resolvedAppearance, updateAppearance } = useAppearance()
const mobileNavOpen = ref(false)

const isActive = (href: string) => currentUrl.value === href || currentUrl.value.startsWith(`${href}/`)

const toggleTheme = () => {
  updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light')
}
</script>
