<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import PaletteCard from '@/components/PaletteCard.vue';
import ColorGroup from '@/components/ColorGroup.vue';

interface Props {
    palettes: Record<string, any[]>;
    settings: Record<string, any>;
}

const props = defineProps<Props>();

const activeTab = ref('light');

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
];

const previewMode = ref('light');

const lightForm = useForm({
    selected_palette_id: props.settings.light?.selected_palette_id || null,
    colors: props.settings.light?.colors_json || {},
});

const darkForm = useForm({
    selected_palette_id: props.settings.dark?.selected_palette_id || null,
    colors: props.settings.dark?.colors_json || {},
});

const slots = [
    'sidebar_bg', 'sidebar_text', 'sidebar_active_bg', 'sidebar_active_text',
    'header_bg', 'header_text', 'content_bg', 'content_text',
    'card_bg', 'card_text'
];

const textSlots = ['sidebar_text', 'sidebar_active_text', 'header_text', 'content_text', 'card_text'];

const safeTextColors = [
    '#000000', '#FFFFFF', '#374151', '#F3F4F6', '#1F2937', '#E5E7EB'
];

function getSafeTextColors(bgHex: string) {
    return safeTextColors.filter(textHex => calculateContrast(bgHex, textHex) >= 4.5);
}

function calculateContrast(bgHex: string, textHex: string): number {
    const bgL = hexToLuminance(bgHex);
    const textL = hexToLuminance(textHex);
    const ratio = (Math.max(bgL, textL) + 0.05) / (Math.min(bgL, textL) + 0.05);
    return ratio;
}

function hexToLuminance(hex: string): number {
    const rgb = hexToRgb(hex);
    const [r, g, b] = rgb.map(c => {
        c = c / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function hexToRgb(hex: string): number[] {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? [
        parseInt(result[1], 16),
        parseInt(result[2], 16),
        parseInt(result[3], 16)
    ] : [0, 0, 0];
}

function selectPalette(mode: string, paletteId: number | null) {
    const form = mode === 'light' ? lightForm : darkForm;
    form.selected_palette_id = paletteId;
    if (paletteId) {
        const palette = props.palettes[mode].find(p => p.id === paletteId);
        if (palette) {
            const colors: Record<string, string> = {};
            palette.colors.forEach((color: any) => {
                colors[color.slot] = color.hex;
            });
            form.colors = colors;
        }
    }
}

function submitForm(mode: string) {
    const form = mode === 'light' ? lightForm : darkForm;
    const url = mode === 'light' ? '/settings/appearance/light' : '/settings/appearance/dark';
    form.post(url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance" />

        <SettingsLayout>
            <div class="space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Appearance</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage Light and Dark mode color themes for the dashboard.</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Admin Only
                    </span>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="activeTab = 'light'"
                            :class="[
                                activeTab === 'light'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Light Mode
                        </button>
                        <button
                            @click="activeTab = 'dark'"
                            :class="[
                                activeTab === 'dark'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            Dark Mode
                        </button>
                    </nav>
                </div>

                <!-- Light Mode -->
                <div v-if="activeTab === 'light'">
                    <form @submit.prevent="submitForm('light')" class="space-y-8">
                        <!-- Live Preview -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Live Preview</h3>
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden h-96" :style="{ backgroundColor: lightForm.colors.content_bg || '#f9fafb' }">
                                <div class="flex h-full">
                                    <!-- Sidebar -->
                                    <div class="w-64 p-6 border-r border-gray-200" :style="{ backgroundColor: lightForm.colors.sidebar_bg || '#ffffff', color: lightForm.colors.sidebar_text || '#000000' }">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-medium">Sidebar</div>
                                        <div class="space-y-3">
                                            <div class="h-4 bg-current opacity-20 rounded"></div>
                                            <div class="h-4 bg-current opacity-20 rounded w-3/4"></div>
                                            <div class="h-4 bg-current opacity-20 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <!-- Header -->
                                        <div class="p-6 border-b border-gray-200" :style="{ backgroundColor: lightForm.colors.header_bg || '#ffffff', color: lightForm.colors.header_text || '#000000' }">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-medium">Header</div>
                                            <div class="h-5 bg-current opacity-20 rounded w-1/3"></div>
                                        </div>
                                        <!-- Content -->
                                        <div class="p-8">
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-medium">Content</div>
                                            <div class="rounded-xl p-6 shadow-sm" :style="{ backgroundColor: lightForm.colors.card_bg || '#ffffff', color: lightForm.colors.card_text || '#000000' }">
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-medium">Card</div>
                                                <div class="h-4 bg-current opacity-20 rounded mb-3"></div>
                                                <div class="h-4 bg-current opacity-20 rounded w-3/4"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Palette Selection -->
                        <div>
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Choose a Palette</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                <PaletteCard
                                    v-for="palette in props.palettes.light"
                                    :key="palette.id"
                                    :palette="palette"
                                    :selected="lightForm.selected_palette_id == palette.id"
                                    @select="selectPalette('light', $event)"
                                />
                            </div>
                            <p v-if="lightForm.selected_palette_id" class="mt-4 text-sm text-gray-600 dark:text-gray-400">You can fine-tune colors below</p>
                        </div>

                        <!-- Basic Colors -->
                        <div>
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Basic Colors</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <ColorGroup
                                    title="Sidebar"
                                    description="Navigation and menu colors"
                                    :bg-color="lightForm.colors.sidebar_bg"
                                    :text-color="lightForm.colors.sidebar_text"
                                    :safe-text-colors="getSafeTextColors(lightForm.colors.sidebar_bg || '#FFFFFF')"
                                    @bg-change="lightForm.colors.sidebar_bg = $event"
                                    @text-change="lightForm.colors.sidebar_text = $event"
                                />

                                <ColorGroup
                                    title="Header"
                                    description="Top bar and branding"
                                    :bg-color="lightForm.colors.header_bg"
                                    :text-color="lightForm.colors.header_text"
                                    :safe-text-colors="getSafeTextColors(lightForm.colors.header_bg || '#FFFFFF')"
                                    @bg-change="lightForm.colors.header_bg = $event"
                                    @text-change="lightForm.colors.header_text = $event"
                                />

                                <ColorGroup
                                    title="Content"
                                    description="Main page background"
                                    :bg-color="lightForm.colors.content_bg"
                                    :text-color="lightForm.colors.content_text"
                                    :safe-text-colors="getSafeTextColors(lightForm.colors.content_bg || '#FFFFFF')"
                                    @bg-change="lightForm.colors.content_bg = $event"
                                    @text-change="lightForm.colors.content_text = $event"
                                />

                                <ColorGroup
                                    title="Cards"
                                    description="Data containers and panels"
                                    :bg-color="lightForm.colors.card_bg"
                                    :text-color="lightForm.colors.card_text"
                                    :safe-text-colors="getSafeTextColors(lightForm.colors.card_bg || '#FFFFFF')"
                                    @bg-change="lightForm.colors.card_bg = $event"
                                    @text-change="lightForm.colors.card_text = $event"
                                />
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <details class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                            <summary class="cursor-pointer p-6 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-xl text-gray-900 dark:text-white">Advanced Customization</summary>
                            <div class="p-6 pt-0 space-y-6">
                                <ColorGroup
                                    title="Active States"
                                    description="Colors for active navigation items"
                                    :bg-color="lightForm.colors.sidebar_active_bg"
                                    :text-color="lightForm.colors.sidebar_active_text"
                                    :safe-text-colors="getSafeTextColors(lightForm.colors.sidebar_active_bg || '#FFFFFF')"
                                    @bg-change="lightForm.colors.sidebar_active_bg = $event"
                                    @text-change="lightForm.colors.sidebar_active_text = $event"
                                />
                            </div>
                        </details>
                    </form>
                </div>

                <!-- Dark Mode -->
                <div v-if="activeTab === 'dark'">
                    <form @submit.prevent="submitForm('dark')" class="space-y-8">
                        <!-- Live Preview -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Live Preview</h3>
                            <div class="bg-gray-900 rounded-xl shadow-lg overflow-hidden h-96" :style="{ backgroundColor: darkForm.colors.content_bg || '#1f2937' }">
                                <div class="flex h-full">
                                    <!-- Sidebar -->
                                    <div class="w-64 p-6 border-r border-gray-700" :style="{ backgroundColor: darkForm.colors.sidebar_bg || '#1f2937', color: darkForm.colors.sidebar_text || '#ffffff' }">
                                        <div class="text-xs text-gray-400 mb-4 font-medium">Sidebar</div>
                                        <div class="space-y-3">
                                            <div class="h-4 bg-current opacity-30 rounded"></div>
                                            <div class="h-4 bg-current opacity-30 rounded w-3/4"></div>
                                            <div class="h-4 bg-current opacity-30 rounded w-1/2"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <!-- Header -->
                                        <div class="p-6 border-b border-gray-700" :style="{ backgroundColor: darkForm.colors.header_bg || '#1f2937', color: darkForm.colors.header_text || '#ffffff' }">
                                            <div class="text-xs text-gray-400 mb-4 font-medium">Header</div>
                                            <div class="h-5 bg-current opacity-30 rounded w-1/3"></div>
                                        </div>
                                        <!-- Content -->
                                        <div class="p-8">
                                            <div class="text-xs text-gray-400 mb-4 font-medium">Content</div>
                                            <div class="rounded-xl p-6 shadow-sm" :style="{ backgroundColor: darkForm.colors.card_bg || '#374151', color: darkForm.colors.card_text || '#ffffff' }">
                                                <div class="text-xs text-gray-400 mb-4 font-medium">Card</div>
                                                <div class="h-4 bg-current opacity-30 rounded mb-3"></div>
                                                <div class="h-4 bg-current opacity-30 rounded w-3/4"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Palette Selection -->
                        <div>
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Choose a Palette</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                <PaletteCard
                                    v-for="palette in props.palettes.dark"
                                    :key="palette.id"
                                    :palette="palette"
                                    :selected="darkForm.selected_palette_id == palette.id"
                                    @select="selectPalette('dark', $event)"
                                />
                            </div>
                            <p v-if="darkForm.selected_palette_id" class="mt-4 text-sm text-gray-600 dark:text-gray-400">You can fine-tune colors below</p>
                        </div>

                        <!-- Basic Colors -->
                        <div>
                            <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Basic Colors</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <ColorGroup
                                    title="Sidebar"
                                    description="Navigation and menu colors"
                                    :bg-color="darkForm.colors.sidebar_bg"
                                    :text-color="darkForm.colors.sidebar_text"
                                    :safe-text-colors="getSafeTextColors(darkForm.colors.sidebar_bg || '#1f2937')"
                                    @bg-change="darkForm.colors.sidebar_bg = $event"
                                    @text-change="darkForm.colors.sidebar_text = $event"
                                />

                                <ColorGroup
                                    title="Header"
                                    description="Top bar and branding"
                                    :bg-color="darkForm.colors.header_bg"
                                    :text-color="darkForm.colors.header_text"
                                    :safe-text-colors="getSafeTextColors(darkForm.colors.header_bg || '#1f2937')"
                                    @bg-change="darkForm.colors.header_bg = $event"
                                    @text-change="darkForm.colors.header_text = $event"
                                />

                                <ColorGroup
                                    title="Content"
                                    description="Main page background"
                                    :bg-color="darkForm.colors.content_bg"
                                    :text-color="darkForm.colors.content_text"
                                    :safe-text-colors="getSafeTextColors(darkForm.colors.content_bg || '#1f2937')"
                                    @bg-change="darkForm.colors.content_bg = $event"
                                    @text-change="darkForm.colors.content_text = $event"
                                />

                                <ColorGroup
                                    title="Cards"
                                    description="Data containers and panels"
                                    :bg-color="darkForm.colors.card_bg"
                                    :text-color="darkForm.colors.card_text"
                                    :safe-text-colors="getSafeTextColors(darkForm.colors.card_bg || '#374151')"
                                    @bg-change="darkForm.colors.card_bg = $event"
                                    @text-change="darkForm.colors.card_text = $event"
                                />
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <details class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                            <summary class="cursor-pointer p-6 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-xl text-gray-900 dark:text-white">Advanced Customization</summary>
                            <div class="p-6 pt-0 space-y-6">
                                <ColorGroup
                                    title="Active States"
                                    description="Colors for active navigation items"
                                    :bg-color="darkForm.colors.sidebar_active_bg"
                                    :text-color="darkForm.colors.sidebar_active_text"
                                    :safe-text-colors="getSafeTextColors(darkForm.colors.sidebar_active_bg || '#1f2937')"
                                    @bg-change="darkForm.colors.sidebar_active_bg = $event"
                                    @text-change="darkForm.colors.sidebar_active_text = $event"
                                />
                            </div>
                        </details>
                    </form>
                </div>

                <!-- Bottom Actions -->
                <div class=" bottom-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 -mx-4">
                    <div class="flex justify-between items-center">
                        <button
                            type="button"
                            @click="selectPalette(activeTab, activeTab === 'light' ? lightForm.selected_palette_id : darkForm.selected_palette_id)"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Reset to Palette
                        </button>
                        <button
                            @click="submitForm(activeTab)"
                            :disabled="(activeTab === 'light' ? lightForm.processing : darkForm.processing)"
                            class="inline-flex items-center px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                        >
                            Save {{ activeTab === 'light' ? 'Light' : 'Dark' }} Mode
                        </button>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
