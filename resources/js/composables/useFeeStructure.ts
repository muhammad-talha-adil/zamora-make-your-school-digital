import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/vue3';

export interface FeeStructureItem {
    id: number;
    fee_head_id: number;
    fee_head: string;
    amount: number;
    frequency: string;
    is_optional: boolean;
}

export interface FeeStructure {
    id: number;
    title: string;
    monthly_fee: number;
    annual_fee: number;
    items: FeeStructureItem[];
}

export interface DiscountType {
    id: number;
    name: string;
    default_value: number;
    value_type: string;
}

export interface AppliedDiscount {
    fee_head_id: number;
    fee_head: string;
    discount_type_id: number;
    value: number;
    value_type: string;
}

export interface ManualFeeEntry {
    fee_head_id: number;
    fee_head: string;
    amount: number;
    original_amount: number;
    discount_percentage: number;
}

export interface FeeStructureEmits {
    (e: 'update:feeStructureId', value: number | null): void;
    (e: 'update:feeMode', value: 'structure' | 'discount' | 'manual'): void;
    (e: 'update:discounts', value: AppliedDiscount[]): void;
    (e: 'update:customFeeEntries', value: ManualFeeEntry[]): void;
    (e: 'update:manualDiscountPercentage', value: number): void;
    (e: 'update:manualDiscountReason', value: string): void;
}

/**
 * Composable for fee structure selection in student forms
 * Used by both Create and Edit student pages
 */
export function useFeeStructure() {
    // State
    const feeStructure = ref<FeeStructure | null>(null);
    const feeStructureLoading = ref(false);
    const feeMode = ref<'structure' | 'discount' | 'manual'>('structure');
    const discountTypes = ref<DiscountType[]>([]);
    const selectedDiscountType = ref<number | ''>('');
    const discountValue = ref<number>(0);
    const manualSelectedFeeHeads = ref<number[]>([]);
    const manualFeeAmounts = ref<Record<number, number>>({});
    const manualReason = ref('');

    // Form refs (set from parent)
    const formClassId = ref<number | string>('');
    const formSessionId = ref<number | string>('');
    const formCampusId = ref<number | string>('');
    const formSectionId = ref<number | string>('');

    // Initialize form refs from parent
    const initFormRefs = (refs: {
        classId: typeof formClassId;
        sessionId: typeof formSessionId;
        campusId: typeof formCampusId;
        sectionId: typeof formSectionId;
    }) => {
        formClassId.value = refs.classId.value;
        formSessionId.value = refs.sessionId.value;
        formCampusId.value = refs.campusId.value;
        formSectionId.value = refs.sectionId.value;
    };

    // Watch for form changes
    const setupFormWatchers = () => {
        watch(
            [formClassId, formSessionId, formCampusId, formSectionId],
            () => {
                if (formClassId.value && formSessionId.value && formCampusId.value) {
                    fetchFeeStructure();
                } else {
                    feeStructure.value = null;
                }
            }
        );
    };

    // Computed properties
    const selectedDiscountTypeObj = computed(() => {
        return discountTypes.value.find(d => d.id === selectedDiscountType.value);
    });

    const hasFeeStructure = computed(() => {
        return feeStructure.value && feeStructure.value.items && feeStructure.value.items.length > 0;
    });

    const appliedDiscounts = computed((): AppliedDiscount[] => {
        if (!selectedDiscountType.value || !feeStructure.value || manualSelectedFeeHeads.value.length === 0) {
            return [];
        }
        
        return manualSelectedFeeHeads.value.map(feeHeadId => {
            const item = feeStructure.value!.items.find((i: FeeStructureItem) => i.fee_head_id === feeHeadId);
            return {
                fee_head_id: feeHeadId,
                fee_head: item?.fee_head || '',
                discount_type_id: selectedDiscountType.value as number,
                value: discountValue.value,
                value_type: selectedDiscountTypeObj.value?.value_type || 'percent',
            };
        });
    });

    const manualFeeEntries = computed((): ManualFeeEntry[] => {
        if (!feeStructure.value) return [];
        
        return manualSelectedFeeHeads.value.map(feeHeadId => {
            const item = feeStructure.value!.items.find((i: FeeStructureItem) => i.fee_head_id === feeHeadId);
            const customAmount = manualFeeAmounts.value[feeHeadId] || 0;
            return {
                fee_head_id: feeHeadId,
                fee_head: item?.fee_head || '',
                amount: customAmount,
                original_amount: item?.amount || 0,
                discount_percentage: calculateDiscountPercentage(item?.amount || 0, customAmount),
            };
        });
    });

    // Helper functions
    function calculateDiscountPercentage(original: number, custom: number): number {
        if (original <= 0) return 0;
        return Math.round(((original - custom) / original) * 100);
    }

    function setManualFeeAmount(feeHeadId: number, amount: number) {
        manualFeeAmounts.value[feeHeadId] = amount;
    }

    // API functions
    async function fetchFeeStructure() {
        if (!formClassId.value || !formSessionId.value || !formCampusId.value) {
            feeStructure.value = null;
            return;
        }

        feeStructureLoading.value = true;
        try {
            const response = await axios.get(route('fee.structures.by-scope'), {
                params: {
                    class_id: formClassId.value,
                    session_id: formSessionId.value,
                    campus_id: formCampusId.value,
                    section_id: formSectionId.value || null,
                },
            });

            if (response.data.success) {
                feeStructure.value = response.data.data;
                resetSelections();
            } else {
                feeStructure.value = null;
            }
        } catch (error) {
            console.error('Error fetching fee structure:', error);
            feeStructure.value = null;
        } finally {
            feeStructureLoading.value = false;
        }
    }

    async function fetchDiscountTypes() {
        try {
            const response = await axios.get(route('fee.discount-types.all'));
            if (response.data.success) {
                discountTypes.value = response.data.data;
            }
        } catch (error) {
            console.error('Error fetching discount types:', error);
        }
    }

    function resetSelections() {
        manualSelectedFeeHeads.value = [];
        manualFeeAmounts.value = {};
        discountValue.value = 0;
        selectedDiscountType.value = '';
    }

    // Navigate to create fee structure page
    function createFeeStructure() {
        const params = new URLSearchParams();
        params.set('campus_id', String(formCampusId.value));
        params.set('session_id', String(formSessionId.value));
        params.set('class_id', String(formClassId.value));
        router.visit(route('fee.structures.create') + '?' + params.toString());
    }

    // Load existing enrollment data (for edit mode)
    function loadEnrollmentData(enrollment: {
        fee_structure_id?: number | null;
        fee_mode?: string | null;
        custom_fee_entries?: Array<{ fee_head_id: number; amount: number }> | null;
        manual_discount_percentage?: number | null;
        manual_discount_reason?: string | null;
    }) {
        if (enrollment.fee_structure_id) {
            fetchFeeStructure().then(() => {
                if (enrollment.fee_mode) {
                    feeMode.value = enrollment.fee_mode as 'structure' | 'discount' | 'manual';
                    
                    if (enrollment.fee_mode === 'manual' && enrollment.custom_fee_entries) {
                        manualSelectedFeeHeads.value = enrollment.custom_fee_entries.map((e: any) => e.fee_head_id);
                        enrollment.custom_fee_entries.forEach((entry: any) => {
                            manualFeeAmounts.value[entry.fee_head_id] = entry.amount;
                        });
                    }
                    
                    if (enrollment.fee_mode === 'discount') {
                        discountValue.value = enrollment.manual_discount_percentage || 0;
                    }
                }
            });
        }
    }

    // Get data to send to server
    function getFeeData() {
        if (!feeStructure.value) return null;

        const data = {
            fee_structure_id: feeStructure.value.id,
            fee_mode: feeMode.value,
        };

        if (feeMode.value === 'discount' && appliedDiscounts.value.length > 0) {
            (data as any).discounts = appliedDiscounts.value;
            (data as any).manual_discount_percentage = discountValue.value;
        }

        if (feeMode.value === 'manual' && manualFeeEntries.value.length > 0) {
            (data as any).custom_fee_entries = manualFeeEntries.value;
            (data as any).manual_discount_reason = manualReason.value;
            
            const totalDiscount = manualFeeEntries.value.reduce((sum, entry) => {
                return sum + (entry.discount_percentage || 0);
            }, 0);
            const avgDiscount = manualFeeEntries.value.length > 0 
                ? Math.round(totalDiscount / manualFeeEntries.value.length) 
                : 0;
            (data as any).manual_discount_percentage = avgDiscount;
        }

        return data;
    }

    return {
        // State
        feeStructure,
        feeStructureLoading,
        feeMode,
        discountTypes,
        selectedDiscountType,
        discountValue,
        manualSelectedFeeHeads,
        manualFeeAmounts,
        manualReason,
        // Form refs for external control
        formClassId,
        formSessionId,
        formCampusId,
        formSectionId,
        // Computed
        selectedDiscountTypeObj,
        hasFeeStructure,
        appliedDiscounts,
        manualFeeEntries,
        // Methods
        initFormRefs,
        setupFormWatchers,
        fetchFeeStructure,
        fetchDiscountTypes,
        setManualFeeAmount,
        calculateDiscountPercentage,
        resetSelections,
        createFeeStructure,
        loadEnrollmentData,
        getFeeData,
    };
}
