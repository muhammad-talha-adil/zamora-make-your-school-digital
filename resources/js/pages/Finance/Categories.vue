<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    type: string;
    parent_id: number | null;
    is_active: boolean;
}

interface Props {
    categories?: Category[];
    filters?: {
        type?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

// Form state
const showForm = ref(false);
const editingCategory = ref<Category | null>(null);
const form = ref({
    name: '',
    type: 'INCOME',
    parent_id: null as number | null,
    is_active: true,
});

const categories = computed(() => props.categories || []);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Categories' },
];

// Group categories by type
const incomeCategories = computed(() => categories.value.filter(c => c.type === 'INCOME'));
const expenseCategories = computed(() => categories.value.filter(c => c.type === 'EXPENSE'));

// Open form for new category
const openForm = (type: string = 'INCOME') => {
    editingCategory.value = null;
    form.value = { name: '', type: type, parent_id: null, is_active: true };
    showForm.value = true;
};

// Open form for editing
const editCategory = (category: Category) => {
    editingCategory.value = category;
    form.value = { 
        name: category.name, 
        type: category.type, 
        parent_id: category.parent_id, 
        is_active: category.is_active 
    };
    showForm.value = true;
};

// Submit form
const submitForm = async () => {
    if (editingCategory.value) {
        await router.put(`/finance/categories/${editingCategory.value.id}`, form.value);
    } else {
        await router.post('/finance/categories', form.value);
    }
    showForm.value = false;
};

// Delete category
const deleteCategory = async (id: number) => {
    if (confirm('Are you sure you want to delete this category?')) {
        await router.delete(`/finance/categories/${id}`);
    }
};


</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Categories" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Categories
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage income and expense categories
                    </p>
                </div>
                <Button @click="openForm()">Add Category</Button>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Income Categories -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-green-600 dark:text-green-400 mb-4">Income Categories</h2>
                    <div class="space-y-2">
                        <div 
                            v-for="category in incomeCategories" 
                            :key="category.id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div class="flex items-center gap-2">
                                <span :class="{'opacity-50': !category.is_active}">{{ category.name }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click="editCategory(category)" class="text-blue-600 hover:text-blue-800">Edit</button>
                                <button @click="deleteCategory(category.id)" class="text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </div>
                        <p v-if="incomeCategories.length === 0" class="text-gray-500">No income categories</p>
                    </div>
                </div>

                <!-- Expense Categories -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Expense Categories</h2>
                    <div class="space-y-2">
                        <div 
                            v-for="category in expenseCategories" 
                            :key="category.id"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div class="flex items-center gap-2">
                                <span :class="{'opacity-50': !category.is_active}">{{ category.name }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click="editCategory(category)" class="text-blue-600 hover:text-blue-800">Edit</button>
                                <button @click="deleteCategory(category.id)" class="text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </div>
                        <p v-if="expenseCategories.length === 0" class="text-gray-500">No expense categories</p>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Form Modal -->
            <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ editingCategory ? 'Edit Category' : 'Add Category' }}
                    </h3>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <Label>Name</Label>
                            <Input v-model="form.name" required />
                        </div>
                        <div>
                            <Label>Type</Label>
                            <select v-model="form.type" class="w-full mt-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="INCOME">Income</option>
                                <option value="EXPENSE">Expense</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit">Save</Button>
                            <Button type="button" variant="outline" @click="showForm = false">Cancel</Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
