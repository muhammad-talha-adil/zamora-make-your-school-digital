<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import FeeHeadsPanel from './FeeHeadsPanel.vue';
import DiscountTypesPanel from './DiscountTypesPanel.vue';
import FineRulesPanel from './FineRulesPanel.vue';

interface Props {
    feeHeadsData: {
        feeHeads: unknown;
        filters?: Record<string, unknown>;
        categories: Array<{ value: string; label: string }>;
    } | null;
    discountTypes: unknown[] | null;
    fineRulesData: {
        fineRules: unknown[];
        campuses: unknown[];
        sessions: unknown[];
        classes: unknown[];
        sections: unknown[];
        feeHeads: unknown[];
        filters?: Record<string, unknown>;
    } | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Settings', href: '/fee/settings' },
];

const tabs = computed(() => [
    { id: 'fee-heads', label: 'Fee Heads', visible: props.feeHeadsData !== null },
    { id: 'discount-types', label: 'Discount Types', visible: props.discountTypes !== null },
    { id: 'fine-rules', label: 'Fine Rules', visible: props.fineRulesData !== null },
].filter((tab) => tab.visible));

const activeTab = ref(tabs.value[0]?.id ?? 'fee-heads');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Settings" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Fee Settings
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Configure fee management settings
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-border overflow-x-auto overflow-hidden">
                <nav class="-mb-px flex space-x-4 md:space-x-8 min-w-0">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tabs are kept mounted (v-show) rather than unmounted (v-if) so
                 switching tabs never discards a tab's filters or in-progress
                 form state, matching the fix applied to School Profile's tabs
                 for #24/#56-58/#60/#62. -->

            <!-- Fee Heads Tab -->
            <div v-if="feeHeadsData" v-show="activeTab === 'fee-heads'">
                <FeeHeadsPanel
                    :fee-heads="feeHeadsData.feeHeads"
                    :filters="feeHeadsData.filters"
                    :categories="feeHeadsData.categories"
                />
            </div>

            <!-- Discount Types Tab -->
            <div v-if="discountTypes" v-show="activeTab === 'discount-types'">
                <DiscountTypesPanel :discount-types="discountTypes" />
            </div>

            <!-- Fine Rules Tab -->
            <div v-if="fineRulesData" v-show="activeTab === 'fine-rules'">
                <FineRulesPanel
                    :fine-rules="fineRulesData.fineRules"
                    :campuses="fineRulesData.campuses"
                    :sessions="fineRulesData.sessions"
                    :classes="fineRulesData.classes"
                    :sections="fineRulesData.sections"
                    :fee-heads="fineRulesData.feeHeads"
                    :filters="fineRulesData.filters"
                />
            </div>
        </div>
    </AppLayout>
</template>
