<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Suppliers" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Suppliers
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage your inventory suppliers
                    </p>
                </div>
                <SupplierForm
                    :campuses="props.campuses"
                    @saved="router.reload()"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3">
                <select 
                    v-model="filters.campus_id"
                    @change="router.visit(route('inventory.suppliers.index') + `?campus_id=${filters.campus_id || ''}`)"
                    class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Search suppliers..."
                    @input="debouncedSearch"
                    class="w-full sm:w-64 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                />
                <select 
                    v-model="filters.active_only"
                    @change="router.visit(route('inventory.suppliers.index') + `?campus_id=${filters.campus_id || ''}&active_only=${filters.active_only || ''}`)"
                    class="w-full sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option :value="undefined">All Status</option>
                    <option :value="true">Active Only</option>
                    <option :value="false">Inactive</option>
                </select>
            </div>

            <!-- Mobile Card View (visible only on small screens) -->
            <div class="block lg:hidden space-y-3">
                <div 
                    v-for="supplier in props.suppliers.data" 
                    :key="supplier.id" 
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ supplier.name.charAt(0) }}</span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ supplier.name }}</div>
                                <div class="text-xs text-gray-500">{{ supplier.campus?.name }}</div>
                            </div>
                        </div>
                        <span
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full shrink-0',
                                supplier.is_active
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                            ]"
                        >
                            {{ supplier.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div v-if="supplier.contact_person || supplier.phone || supplier.email" class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div v-if="supplier.contact_person" class="flex items-center gap-2">
                            <Icon icon="user" class="h-4 w-4" />
                            <span>{{ supplier.contact_person }}</span>
                        </div>
                        <div v-if="supplier.phone" class="flex items-center gap-2">
                            <Icon icon="phone" class="h-4 w-4" />
                            <span>{{ supplier.phone }}</span>
                        </div>
                        <div v-if="supplier.email" class="flex items-center gap-2">
                            <Icon icon="mail" class="h-4 w-4" />
                            <span class="truncate">{{ supplier.email }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="router.visit(route('inventory.suppliers.show', supplier.id))" class="flex-1">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" @click="router.visit(route('inventory.suppliers.edit', supplier.id))" class="flex-1">
                            <Icon icon="edit" class="mr-1" />Edit
                        </Button>
                        <Button 
                            v-if="supplier.is_active"
                            variant="outline" 
                            size="sm" 
                            @click="inactivateSupplier(supplier.id)"
                            class="flex-1 text-yellow-600 hover:text-yellow-700"
                        >
                            <Icon icon="eye-off" class="mr-1" />Inactivate
                        </Button>
                        <Button 
                            v-else
                            variant="outline" 
                            size="sm" 
                            @click="activateSupplier(supplier.id)"
                            class="flex-1 text-green-600 hover:text-green-700"
                        >
                            <Icon icon="check-circle" class="mr-1" />Activate
                        </Button>
                    </div>
                </div>
                <div v-if="props.suppliers.data.length === 0" class="text-center py-8 text-gray-500">
                    No suppliers found.
                </div>
            </div>

            <!-- Desktop Table View (visible only on large screens) -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Supplier
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Contact Person
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Phone
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Email
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="supplier in props.suppliers.data" :key="supplier.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ supplier.name.charAt(0) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.name }}</div>
                                            <div class="text-xs text-gray-500">{{ supplier.campus?.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ supplier.contact_person || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ supplier.phone || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300 truncate max-w-[200px]">{{ supplier.email || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            supplier.is_active
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                        ]"
                                    >
                                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <Button variant="outline" size="sm" @click="router.visit(route('inventory.suppliers.show', supplier.id))" class="min-h-8">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" @click="router.visit(route('inventory.suppliers.edit', supplier.id))" class="min-h-8">
                                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                        </Button>
                                        <Button 
                                            v-if="supplier.is_active"
                                            variant="outline" 
                                            size="sm" 
                                            @click="inactivateSupplier(supplier.id)"
                                            class="min-h-8 text-yellow-600 hover:text-yellow-700"
                                        >
                                            <Icon icon="eye-off" class="mr-1 h-3 w-3" />Inactivate
                                        </Button>
                                        <Button 
                                            v-else
                                            variant="outline" 
                                            size="sm" 
                                            @click="activateSupplier(supplier.id)"
                                            class="min-h-8 text-green-600 hover:text-green-700"
                                        >
                                            <Icon icon="check-circle" class="mr-1 h-3 w-3" />Activate
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="props.suppliers.links" class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="text-xs md:text-sm text-gray-600">
                    Showing {{ props.suppliers.from }} to {{ props.suppliers.to }} of {{ props.suppliers.total }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <Link
                        v-for="link in props.suppliers.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 text-sm rounded-md transition-colors min-h-10 flex items-center justify-center',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'
                        ]"
                        preserve-state
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import SupplierForm from '@/components/forms/inventory/SupplierForm.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

interface Props {
    suppliers: {
        data: Array<{
            id: number;
            name: string;
            contact_person?: string;
            phone?: string;
            email?: string;
            is_active: boolean;
            campus?: {
                name: string;
            };
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    campuses: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        campus_id?: string;
        search?: string;
        active_only?: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Inventory',
        href: '/inventory',
    },
    {
        title: 'Suppliers',
        href: '/inventory/suppliers',
    },
];

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    search: props.filters?.search || '',
    active_only: props.filters?.active_only,
});

const debouncedSearch = debounce(() => {
    const params = new URLSearchParams();
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.search) params.append('search', filters.search);
    if (filters.active_only !== undefined) params.append('active_only', String(filters.active_only));

    router.visit(route('inventory.suppliers.index') + `?${params.toString()}`, {
        preserveState: true,
    });
}, 300);

const inactivateSupplier = (id: number) => {
    if (confirm('Are you sure you want to inactivate this supplier?')) {
        router.patch(route('inventory.suppliers.inactivate', id), {}, {
            onSuccess: () => router.reload(),
        });
    }
};

const activateSupplier = (id: number) => {
    if (confirm('Are you sure you want to activate this supplier?')) {
        router.patch(route('inventory.suppliers.activate', id), {}, {
            onSuccess: () => router.reload(),
        });
    }
};
</script>
