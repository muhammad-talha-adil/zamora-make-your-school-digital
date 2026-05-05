<template>
  <div class="h-screen flex overflow-hidden" :style="{ backgroundColor: 'var(--content-bg)', color: 'var(--content-text)' }">
    <!-- Sidebar -->
    <aside
      :class="[
        'w-64 border-r transition-all duration-300 ease-in-out',
        'md:block md:static md:translate-x-0',
        'fixed inset-y-0 left-0 z-50 md:relative',
        isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        (collapsed && !tempExpand) ? 'md:w-16' : 'md:w-64'
      ]"
      :style="{ backgroundColor: 'var(--sidebar-bg)', color: 'var(--sidebar-text)', borderColor: 'var(--sidebar-text)' }"
      @mouseenter="onMouseEnter"
      @mouseleave="onMouseLeave"
    >
      <div class="flex h-full flex-col">
        <!-- Sidebar Header -->
        <div :class="['border-b flex items-center justify-between shrink-0', (collapsed && !tempExpand) ? 'p-2' : 'p-4']" :style="{ borderColor: 'var(--sidebar-text)' }">
          <AppLogo v-if="!collapsed || tempExpand" />
          <div v-else class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
            <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
          </div>
          <!-- Close button for mobile -->
          <button
            @click="closeSidebar"
            class="md:hidden p-2 rounded-md hover:opacity-75"
            :style="{ color: 'var(--sidebar-text)' }"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="sidebar-scroll flex-1 min-h-0 overflow-y-auto overflow-x-hidden">
          <div class="flex min-h-full flex-col">
            <!-- Navigation Links -->
            <nav :class="['flex-1 py-6 space-y-2', (collapsed && !tempExpand) ? 'px-2' : 'px-4']">
              <template v-for="item in mainNavItems" :key="item.title">
                <div v-if="item.children && item.children.length > 0" class="space-y-1">
                  <button
                    @click="toggleMenu(menuKey('main', item.title))"
                    :class="['flex items-center justify-between w-full py-2 text-sm font-medium rounded-md transition-colors', (collapsed && !tempExpand) ? 'px-2' : 'px-4']"
                    :style="{ color: 'var(--sidebar-text)' }"
                  >
                    <div class="flex items-center">
                      <Icon :icon="item.icon" :size="20" :class="(!collapsed || tempExpand) ? 'mr-3' : ''" />
                      <span v-if="!collapsed || tempExpand">{{ item.title }}</span>
                    </div>
                    <ChevronDown
                      v-if="!collapsed || tempExpand"
                      class="w-4 h-4 transition-transform duration-200"
                      :class="{ 'rotate-180': isMenuOpen(menuKey('main', item.title)) }"
                    />
                  </button>
                  <div
                    v-if="!collapsed || tempExpand"
                    v-show="isMenuOpen(menuKey('main', item.title))"
                    class="space-y-1 pt-1"
                  >
                    <Link
                      v-for="child in item.children"
                      :key="child.title"
                      :href="child.href"
                      :class="[
                        'flex items-center gap-2 px-8 py-2 text-sm font-medium rounded-md transition-colors ml-4',
                      ]"
                      :style="{
                        backgroundColor: currentUrl === child.href ? 'var(--sidebar-active-bg)' : 'transparent',
                        color: currentUrl === child.href ? 'var(--sidebar-active-text)' : 'var(--sidebar-text)',
                      }"
                    >
                      <Icon v-if="child.icon" :icon="child.icon" :size="16" />
                      <span v-if="!collapsed || tempExpand">{{ child.title }}</span>
                    </Link>
                  </div>
                </div>
                <Link
                  v-else
                  :href="item.href"
                  :class="[
                    'flex items-center py-2 text-sm font-medium rounded-md transition-colors',
                    (collapsed && !tempExpand) ? 'px-2' : 'px-4'
                  ]"
                  :style="{
                    backgroundColor: currentUrl === item.href ? 'var(--sidebar-active-bg)' : 'transparent',
                    color: currentUrl === item.href ? 'var(--sidebar-active-text)' : 'var(--sidebar-text)',
                  }"
                >
                  <Icon :icon="item.icon" :size="20" :class="(!collapsed || tempExpand) ? 'mr-3' : ''" />
                  <span v-if="!collapsed || tempExpand">{{ item.title }}</span>
                </Link>
              </template>
            </nav>

            <!-- Bottom Menu -->
            <div :class="['mt-auto py-4 border-t space-y-2', (collapsed && !tempExpand) ? 'px-2' : 'px-4']" :style="{ borderColor: 'var(--sidebar-text)' }">
              <template v-for="item in footerNavItems" :key="item.title">
                <div v-if="item.children && item.children.length > 0" class="space-y-1">
                  <button
                    @click="toggleMenu(menuKey('footer', item.title))"
                    :class="['flex items-center justify-between w-full py-2 text-sm font-medium rounded-md transition-colors', (collapsed && !tempExpand) ? 'px-2' : 'px-4']"
                    :style="{ color: 'var(--sidebar-text)' }"
                  >
                    <div class="flex items-center">
                      <Icon :icon="item.icon" :size="20" :class="(!collapsed || tempExpand) ? 'mr-3' : ''" />
                      <span v-if="!collapsed || tempExpand">{{ item.title }}</span>
                    </div>
                    <ChevronDown
                      v-if="!collapsed || tempExpand"
                      class="w-4 h-4 transition-transform duration-200"
                      :class="{ 'rotate-180': isMenuOpen(menuKey('footer', item.title)) }"
                    />
                  </button>
                  <div
                    v-if="!collapsed || tempExpand"
                    v-show="isMenuOpen(menuKey('footer', item.title))"
                    class="space-y-1 pt-1"
                  >
                    <Link
                      v-for="child in item.children"
                      :key="child.title"
                      :href="child.href"
                      :class="[
                        'flex items-center gap-2 px-8 py-2 text-sm font-medium rounded-md transition-colors ml-4',
                      ]"
                      :style="{
                        backgroundColor: currentUrl === child.href ? 'var(--sidebar-active-bg)' : 'transparent',
                        color: currentUrl === child.href ? 'var(--sidebar-active-text)' : 'var(--sidebar-text)',
                      }"
                    >
                      <Icon v-if="child.icon" :icon="child.icon" :size="16" />
                      <span v-if="!collapsed || tempExpand">{{ child.title }}</span>
                    </Link>
                  </div>
                </div>
                <component
                  v-else
                  :is="(item.href as string).startsWith('http') ? 'a' : Link"
                  :href="item.href"
                  :target="(item.href as string).startsWith('http') ? '_blank' : undefined"
                  :method="(item.href as string) === '/logout' ? 'post' : undefined"
                  :as="(item.href as string) === '/logout' ? 'button' : undefined"
                  :class="[
                    'flex items-center py-2 text-sm font-medium rounded-md transition-colors w-full',
                    (collapsed && !tempExpand) ? 'px-2' : 'px-4'
                  ]"
                  :style="{
                    backgroundColor: currentUrl === item.href ? 'var(--sidebar-active-bg)' : 'transparent',
                    color: currentUrl === item.href ? 'var(--sidebar-active-text)' : 'var(--sidebar-text)',
                  }"
                >
                  <Icon :icon="item.icon as string" :size="20" :class="(!collapsed || tempExpand) ? 'mr-3' : ''" />
                  <span v-if="!collapsed || tempExpand">{{ item.title }}</span>
                </component>
              </template>
              <button
                @click="toggleTheme"
                :class="['flex items-center py-2 text-sm font-medium rounded-md transition-colors w-full', (collapsed && !tempExpand) ? 'px-2' : 'px-4']"
                :style="{ color: 'var(--sidebar-text)' }"
              >
                <Sun v-if="resolvedAppearance === 'light'" :class="['w-5 h-5', (!collapsed || tempExpand) ? 'mr-3' : '']" />
                <Moon v-else :class="['w-5 h-5', (!collapsed || tempExpand) ? 'mr-3' : '']" />
                <span v-if="!collapsed || tempExpand">{{ resolvedAppearance === 'light' ? 'Dark Mode' : 'Light Mode' }}</span>
              </button>
              <Link
                href="/logout"
                method="post"
                as="button"
                :class="['flex items-center py-2 text-sm font-medium rounded-md transition-colors w-full', (collapsed && !tempExpand) ? 'px-2' : 'px-4']"
                :style="{ color: 'var(--sidebar-text)' }"
              >
                <LogOut :class="['w-5 h-5', (!collapsed || tempExpand) ? 'mr-3' : '']" />
                <span v-if="!collapsed || tempExpand">Logout</span>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Backdrop for Mobile -->
    <div
      v-if="isOpen"
      @click="closeSidebar"
      class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
    ></div>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0 flex flex-col">
      <!-- Header -->
      <header class="shadow-sm border-b" :style="{ backgroundColor: 'var(--header-bg)', color: 'var(--header-text)', borderColor: 'var(--header-text)' }">
        <div class="flex items-center justify-between px-4 py-3">
          <!-- Hamburger Menu -->
          <button
            @click="toggleSidebar"
            class="p-2 rounded-md hover:opacity-75"
            :style="{ color: 'var(--header-text)' }"
          >
            <Menu class="w-6 h-6" />
          </button>

          <!-- Title -->
          <h1 class="text-xl font-semibold md:ml-0 ml-auto" :style="{ color: 'var(--header-text)' }">{{ schoolName }}</h1>

          <!-- Right Side: User Menu -->
          <div class="flex items-center space-x-4">
            <span class="text-sm" :style="{ color: 'var(--header-text)' }">Welcome, {{ pageProps.auth?.user?.name || 'User' }}</span>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
              :style="{ backgroundColor: 'var(--sidebar-text)', color: 'var(--sidebar-bg)' }"
            >
              Logout
            </Link>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 p-6 overflow-y-auto" :style="{ backgroundColor: 'var(--content-bg)', color: 'var(--content-text)' }">
        <slot />
      </main>

      <!-- Footer -->
      <FooterBar />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Sun, Moon, LogOut, ChevronDown, X, Menu } from 'lucide-vue-next'
import { useAppearance } from '../../composables/useAppearance'
import FooterBar from './FooterBar.vue'
import AppLogo from '../AppLogo.vue'
import AppLogoIcon from '../AppLogoIcon.vue'
import Icon from '../Icon.vue'
import type { MenuItem } from '../../types'

const isOpen = ref(false)
const collapsed = ref(false)
const tempExpand = ref(false)
const activeMenuKey = ref<string | null>(null)

const toggleMenu = (key: string) => {
  activeMenuKey.value = activeMenuKey.value === key ? null : key
}

const page = usePage()
const pageProps = computed(() => page.props || {})
const currentUrl = computed(() => page.url || '')
const { resolvedAppearance, updateAppearance } = useAppearance()

const mainNavItems = computed<MenuItem[]>(() => {
  const items = pageProps.value.menus?.main || []
  return items
})
const footerNavItems = computed<MenuItem[]>(() => pageProps.value.menus?.footer || [])

const menuKey = (group: 'main' | 'footer', title: string) => `${group}:${title}`

const isMenuOpen = (key: string) => activeMenuKey.value === key

const syncOpenMenuWithCurrentRoute = () => {
  const groups: Array<{ group: 'main' | 'footer'; items: MenuItem[] }> = [
    { group: 'main', items: mainNavItems.value },
    { group: 'footer', items: footerNavItems.value },
  ]

  for (const { group, items } of groups) {
    const matchedItem = items.find((item) =>
      item.children?.some((child) => child.href === currentUrl.value),
    )

    if (matchedItem) {
      activeMenuKey.value = menuKey(group, matchedItem.title)
      return
    }
  }

  activeMenuKey.value = null
}

const toggleSidebar = () => {
  if (window.innerWidth < 768) {
    isOpen.value = !isOpen.value
  } else {
    collapsed.value = !collapsed.value
  }
}

const closeSidebar = () => {
  isOpen.value = false
}

const onMouseEnter = () => {
  if (collapsed.value) tempExpand.value = true
}

const onMouseLeave = () => {
  if (collapsed.value) tempExpand.value = false
}

const toggleTheme = () => {
  updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light')
}

const schoolName = computed(() => pageProps.value.name || 'School Management System')

const applyTheme = () => {
  const theme = (pageProps.value.themeSettings as any)?.[resolvedAppearance.value] || {}
  const root = document.documentElement.style
  root.setProperty('--sidebar-bg', theme.sidebar_bg || '#ffffff')
  root.setProperty('--sidebar-text', theme.sidebar_text || '#000000')
  root.setProperty('--sidebar-active-bg', theme.sidebar_active_bg || '#f3f4f6')
  root.setProperty('--sidebar-active-text', theme.sidebar_active_text || '#000000')
  root.setProperty('--header-bg', theme.header_bg || '#ffffff')
  root.setProperty('--header-text', theme.header_text || '#000000')
  root.setProperty('--content-bg', theme.content_bg || '#f9fafb')
  root.setProperty('--content-text', theme.content_text || '#000000')
  root.setProperty('--card-bg', theme.card_bg || '#ffffff')
  root.setProperty('--card-text', theme.card_text || '#000000')
}

onMounted(() => {
  applyTheme()
  syncOpenMenuWithCurrentRoute()
})

watch(resolvedAppearance, () => {
  applyTheme()
})

watch([mainNavItems, footerNavItems, currentUrl], () => {
  syncOpenMenuWithCurrentRoute()
})
</script>

<style scoped>
.sidebar-scroll {
  scrollbar-width: thin;
  scrollbar-color: color-mix(in srgb, var(--sidebar-text) 32%, transparent) transparent;
}

.sidebar-scroll::-webkit-scrollbar {
  width: 10px;
}

.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: color-mix(in srgb, var(--sidebar-text) 32%, transparent);
  border-radius: 9999px;
  border: 2px solid color-mix(in srgb, var(--sidebar-bg) 85%, transparent);
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background-color: color-mix(in srgb, var(--sidebar-text) 48%, transparent);
}
</style>
