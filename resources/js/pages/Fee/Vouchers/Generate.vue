<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';

interface Session {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
}

interface Month {
    id: number;
    name: string;
    month_number: number;
}

interface Campus {
    id: number;
    name: string;
}

interface SchoolClass {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    class_id: number;
}

interface FeeHead {
    id: number;
    name: string;
    code: string;
    category: string;
}

interface FeeStructureItem {
    id: number;
    fee_head_id: number;
    fee_head: {
        id: number;
        name: string;
        code: string;
        category: string;
    } | null;
    amount: number;
    frequency: string;
}

interface FeeStructure {
    id: number;
    title: string;
    effective_from: string | null;
    effective_to: string | null;
    is_default: boolean;
    items: FeeStructureItem[];
    total_monthly: number;
    total_annual: number;
}

interface Props {
    sessions: Session[];
    months: Month[];
    campuses: Campus[];
    classes: SchoolClass[];
    sections: Section[];
    feeHeads: FeeHead[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Vouchers', href: '/fee/vouchers' },
    { title: 'Generate Vouchers', href: '#' },
];

// Current date info
const currentDate = new Date();
const currentMonthNumber = currentDate.getMonth() + 1; // 1-12
const currentYear = currentDate.getFullYear();

const form = reactive({
    session_id: '',
    campus_id: '',
    class_id: '',
    section_id: '',
    year: currentYear,
    month_ids: [] as number[],
    include_previous_unpaid: false,
    custom_fee_heads: [] as Array<{
        fee_head_id: number;
        amount: number;
    }>,
});

// Auto-select current month on mount
const currentMonth = props.months.find(m => m.month_number === currentMonthNumber);
if (currentMonth) {
    form.month_ids.push(currentMonth.id);
}

const isGenerating = ref(false);
const showCustomFeeModal = ref(false);
const selectedFeeHeadId = ref<number | null>(null);
const customFeeAmount = ref<number>(0);

// Fee structure state
const feeStructureLoading = ref(false);
const applicableFeeStructure = ref<FeeStructure | null>(null);
const feeStructureSource = ref<string | null>(null);
const noFeeStructureMessage = ref<string>('');

// Filter sections based on selected class
const filteredSections = computed(() => {
    if (!form.class_id) return [];
    return props.sections.filter(s => s.class_id === form.class_id);
});

// Fetch applicable fee structure when class/campus/session changes
const fetchFeeStructure = async () => {
    // Reset state
    applicableFeeStructure.value = null;
    feeStructureSource.value = null;
    noFeeStructureMessage.value = '';
    
    // Only fetch if we have all required fields
    if (!form.session_id || !form.campus_id || !form.class_id) {
        return;
    }
    
    feeStructureLoading.value = true;
    
    try {
        // Use the same endpoint as student admission form
        const response = await axios.get(route('fee.structures.by-scope'), {
            params: {
                session_id: form.session_id,
                campus_id: form.campus_id,
                class_id: form.class_id,
                section_id: form.section_id || null,
            }
        });
        
        if (response.data.success) {
            // Transform the response to match our display format
            const data = response.data.data;
            applicableFeeStructure.value = {
                id: data.id,
                title: data.title,
                effective_from: null,
                effective_to: null,
                is_default: false,
                items: data.items.map((item: any) => ({
                    id: item.id,
                    fee_head_id: item.fee_head_id,
                    fee_head: {
                        id: item.fee_head_id,
                        name: item.fee_head,
                        code: '',
                        category: ''
                    },
                    amount: item.amount,
                    frequency: item.frequency,
                })),
                total_monthly: data.monthly_fee || 0,
                total_annual: data.annual_fee || 0,
            };
        } else {
            noFeeStructureMessage.value = response.data.message || 'No fee structure found for this class.';
        }
    } catch (error: any) {
        console.error('Error fetching fee structure:', error);
        noFeeStructureMessage.value = 'Error loading fee structure.';
    } finally {
        feeStructureLoading.value = false;
    }
};

// Watch for changes in session, campus, class, or section
watch(
    () => [form.session_id, form.campus_id, form.class_id, form.section_id],
    () => {
        // Debounce the fetch
        setTimeout(() => {
            fetchFeeStructure();
        }, 300);
    }
);

// Available years (2000 to current year + 1)
const years = Array.from({ length: (currentYear + 1) - 2000 + 1 }, (_, i) => 2000 + i);

// Auto-select current year
form.year = currentYear;

// Toggle month selection
const toggleMonth = (monthId: number) => {
    const index = form.month_ids.indexOf(monthId);
    if (index === -1) {
        form.month_ids.push(monthId);
    } else {
        form.month_ids.splice(index, 1);
    }
};

// Select all months for current year
const selectCurrentYearMonths = () => {
    form.month_ids = [];
    props.months.forEach(month => {
        form.month_ids.push(month.id);
    });
};

// Clear all months
const clearAllMonths = () => {
    form.month_ids = [];
};

// Add custom fee head
const addCustomFeeHead = () => {
    if (selectedFeeHeadId.value && customFeeAmount.value > 0) {
        // Check if already added
        const exists = form.custom_fee_heads.some(f => f.fee_head_id === selectedFeeHeadId.value);
        if (!exists) {
            form.custom_fee_heads.push({
                fee_head_id: selectedFeeHeadId.value,
                amount: customFeeAmount.value,
            });
        }
        showCustomFeeModal.value = false;
        selectedFeeHeadId.value = null;
        customFeeAmount.value = 0;
    }
};

// Remove custom fee head
const removeCustomFeeHead = (index: number) => {
    form.custom_fee_heads.splice(index, 1);
};

// Get source label
const getSourceLabel = (source: string | null): string => {
    switch (source) {
        case 'section':
            return 'Section-specific';
        case 'class':
            return 'Class-specific';
        case 'campus':
            return 'Campus-wide';
        default:
            return '';
    }
};

// Get fee head name by id
const getFeeHeadName = (id: number): string => {
    const feeHead = props.feeHeads.find(f => f.id === id);
    return feeHead ? feeHead.name : 'Unknown';
};

const generateVouchers = () => {
    if (form.month_ids.length === 0) {
        alert('Please select at least one month');
        return;
    }

    if (!form.session_id || !form.campus_id || !form.class_id) {
        alert('Please select Session, Campus, and Class');
        return;
    }

    isGenerating.value = true;

    router.post(
        route('fee.vouchers.generate.post'),
        {
            session_id: form.session_id,
            campus_id: form.campus_id,
            class_id: form.class_id,
            section_id: form.section_id || null,
            year: form.year,
            month_ids: form.month_ids,
            include_previous_unpaid: form.include_previous_unpaid,
            custom_fee_heads: form.custom_fee_heads.length > 0 ? form.custom_fee_heads : null,
        },
        {
            onFinish: () => {
                isGenerating.value = false;
            },
        }
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Generate Fee Vouchers" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Generate Fee Vouchers
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Create monthly fee vouchers for students based on their fee structures
                </p>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <form @submit.prevent="generateVouchers" class="space-y-6">
                    <!-- Session, Campus, Class Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Session -->
                        <div>
                            <Label for="session_id">Academic Session *</Label>
                            <select
                                id="session_id"
                                v-model="form.session_id"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Campus -->
                        <div>
                            <Label for="campus_id">Campus *</Label>
                            <select
                                id="campus_id"
                                v-model="form.campus_id"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option value="">Select Campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Class -->
                        <div>
                            <Label for="class_id">Class *</Label>
                            <select
                                id="class_id"
                                v-model="form.class_id"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Section, Year Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Section -->
                        <div>
                            <Label for="section_id">Section</Label>
                            <select
                                id="section_id"
                                v-model="form.section_id"
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option v-if="filteredSections.length > 0" value="">Select Section</option>
                                <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Year -->
                        <div>
                            <Label for="year">Year *</Label>
                            <select
                                id="year"
                                v-model="form.year"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Months Selection -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <Label>Select Months *</Label>
                            <div class="flex gap-2">
                                <Button type="button" variant="outline" size="sm" @click="selectCurrentYearMonths">
                                    Select All
                                </Button>
                                <Button type="button" variant="outline" size="sm" @click="clearAllMonths">
                                    Clear All
                                </Button>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 md:grid-cols-6 lg:grid-cols-12 gap-3">
                            <label
                                v-for="month in props.months"
                                :key="month.id"
                                class="flex items-center space-x-2 cursor-pointer p-2 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="form.month_ids.includes(month.id) ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-500' : 'bg-white dark:bg-gray-800'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.month_ids.includes(month.id)"
                                    @change="toggleMonth(month.id)"
                                    class="w-4 h-4 text-blue-600 rounded"
                                />
                                <span class="text-sm">{{ month.name }}</span>
                            </label>
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Selected: {{ form.month_ids.length }} month(s)
                        </p>
                    </div>

                    <!-- Include Previous Unpaid Balance -->
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="include_previous_unpaid"
                            v-model="form.include_previous_unpaid"
                            class="w-4 h-4 text-blue-600 rounded"
                        />
                        <Label for="include_previous_unpaid" class="cursor-pointer">
                            Include previous unpaid balance in voucher
                        </Label>
                    </div>

                    <!-- Custom Fee Heads Section -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <Label>Add Custom Fee Heads (Optional)</Label>
                            <Button type="button" variant="outline" size="sm" @click="showCustomFeeModal = true">
                                <Icon icon="plus" class="mr-1 h-4 w-4" />
                                Add Fee Head
                            </Button>
                        </div>

                        <!-- Custom Fee Heads List -->
                        <div v-if="form.custom_fee_heads.length > 0" class="space-y-2">
                            <div
                                v-for="(fee, index) in form.custom_fee_heads"
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div>
                                    <span class="font-medium">{{ getFeeHeadName(fee.fee_head_id) }}</span>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400">- Rs. {{ fee.amount.toLocaleString() }}</span>
                                </div>
                                <Button type="button" variant="ghost" size="sm" @click="removeCustomFeeHead(index)">
                                    <Icon icon="trash-2" class="h-4 w-4 text-red-500" />
                                </Button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                            No custom fee heads added. These will be added to all generated vouchers.
                        </p>
                    </div>

                    <!-- Fee Structure Display -->
                    <div v-if="form.session_id && form.campus_id && form.class_id" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <Label class="mb-3 block">Applicable Fee Structure</Label>
                        
                        <!-- Loading State -->
                        <div v-if="feeStructureLoading" class="flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <Icon icon="loader" class="h-6 w-6 animate-spin text-gray-400" />
                            <span class="ml-2 text-gray-500 dark:text-gray-400">Loading fee structure...</span>
                        </div>
                        
                        <!-- Fee Structure Found -->
                        <div v-else-if="applicableFeeStructure" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <Icon icon="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-green-800 dark:text-green-300">
                                                {{ applicableFeeStructure.title }}
                                            </p>
                                            <p class="text-sm text-green-600 dark:text-green-400">
                                                {{ getSourceLabel(feeStructureSource) }} structure
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-green-700 dark:text-green-300">
                                                Rs. {{ applicableFeeStructure.total_monthly.toLocaleString() }}/month
                                            </p>
                                            <p v-if="applicableFeeStructure.total_annual > 0" class="text-sm text-green-600 dark:text-green-400">
                                                + Rs. {{ applicableFeeStructure.total_annual.toLocaleString() }}/annual
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Fee Items Table -->
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="w-full text-sm text-left">
                                            <thead class="bg-green-100 dark:bg-green-800/50">
                                                <tr>
                                                    <th class="px-3 py-2 text-green-800 dark:text-green-300">Fee Head</th>
                                                    <th class="px-3 py-2 text-green-800 dark:text-green-300">Type</th>
                                                    <th class="px-3 py-2 text-right text-green-800 dark:text-green-300">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-green-100 dark:divide-green-800">
                                                <tr v-for="item in applicableFeeStructure.items" :key="item.id" class="hover:bg-green-50 dark:hover:bg-green-900/10">
                                                    <td class="px-3 py-2 text-green-700 dark:text-green-400">{{ item.fee_head?.name || 'N/A' }}</td>
                                                    <td class="px-3 py-2 text-green-600 dark:text-green-500 capitalize">{{ item.frequency }}</td>
                                                    <td class="px-3 py-2 text-right font-medium text-green-700 dark:text-green-400">Rs. {{ item.amount.toLocaleString() }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- No Fee Structure Found -->
                        <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <Icon icon="alert-triangle" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-medium text-yellow-800 dark:text-yellow-300">No Fee Structure Found</p>
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">
                                        {{ noFeeStructureMessage }}
                                    </p>
                                    <Button type="button" variant="outline" size="sm" class="mt-2" @click="router.visit(route('fee.structures.create'))">
                                        <Icon icon="plus" class="mr-1 h-4 w-4" />
                                        Create Fee Structure
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex gap-3">
                            <Icon icon="info" class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                            <div class="text-sm text-blue-800 dark:text-blue-300">
                                <p class="font-medium">How it works:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>Only students with active enrollments will be included</li>
                                    <li>Students without a fee structure will be skipped</li>
                                    <li>Existing vouchers for selected month(s) will not be duplicated</li>
                                    <li>Vouchers will be generated based on the fee structure assigned to each student</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="router.visit(route('fee.vouchers.index'))">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isGenerating">
                            <Icon v-if="isGenerating" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            <Icon v-else icon="file-plus" class="mr-2 h-4 w-4" />
                            {{ isGenerating ? 'Generating...' : 'Generate ' + form.month_ids.length + ' Voucher' + (form.month_ids.length !== 1 ? 's' : '') }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Custom Fee Head Modal -->
        <div v-if="showCustomFeeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Add Custom Fee Head</h3>
                
                <div class="space-y-4">
                    <div>
                        <Label for="custom_fee_head_id">Fee Head</Label>
                        <select
                            id="custom_fee_head_id"
                            v-model="selectedFeeHeadId"
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        >
                            <option value="">Select Fee Head</option>
                            <option v-for="feeHead in props.feeHeads" :key="feeHead.id" :value="feeHead.id">
                                {{ feeHead.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <Label for="custom_fee_amount">Amount (Rs.)</Label>
                        <input
                            id="custom_fee_amount"
                            v-model.number="customFeeAmount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            placeholder="Enter amount"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <Button type="button" variant="outline" @click="showCustomFeeModal = false">
                        Cancel
                    </Button>
                    <Button type="button" @click="addCustomFeeHead">
                        Add
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
