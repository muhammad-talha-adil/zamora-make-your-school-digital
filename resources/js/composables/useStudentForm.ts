import { ref, computed, watch } from 'vue';
import { useFormatters } from './useFormatters';
import axios from 'axios';
import { route } from 'ziggy-js';

// Common interface definitions
export interface SectionType {
    id: number;
    name: string;
    class_id?: number | null;
}

export interface CampusType {
    id: number;
    name: string;
}

export interface RelationType {
    id: number;
    name: string;
}

export interface GuardianData {
    id: number;
    name: string;
    phone: string;
    email: string;
    cnic: string;
    pivot?: {
        relation_id: number;
    };
}

export interface StudentData {
    id: number;
    admission_no: string;
    registration_no?: string;
    dob: string;
    gender_id: number;
    b_form: string;
    campus_id: number;
    session_id: number;
    class_id: number;
    section_id: number;
    student_status_id: number;
    admission_date: string;
    description: string;
    image?: string | null;
    user?: {
        name: string;
    };
    guardians?: GuardianData[];
    current_enrollment?: {
        monthly_fee: number;
        annual_fee: number;
    };
}

export interface StudentFormProps {
    campuses: CampusType[];
    sessions: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    sections: SectionType[];
    genders: Array<{ id: number; name: string }>;
    relations: RelationType[];
    studentStatuses: Array<{ id: number; name: string }>;
}

/**
 * Common composable for student form functionality
 * Used by both Create and Edit student forms
 */
export function useStudentForm(
    props: StudentFormProps,
    options: {
        activeStatusId?: number;
        admissionNo?: string;
        isEdit?: boolean;
        todayDate?: string;
    } = {}
) {
    const { formatPhone, formatCnic, calculateAge } = useFormatters();
    const { activeStatusId = 1, admissionNo = '',  todayDate = new Date().toISOString().split('T')[0] } = options;

    // Processing state
    const processing = ref(false);

    // Image handling state
    const imagePreview = ref<string | null>(null);
    const imageError = ref<string | null>(null);
    const removeCurrentImage = ref(false);

    // Phone uniqueness
    const phoneError = ref<string | null>(null);

    // Other Guardian section toggle
    const includeOtherGuardian = ref(false);

    // Sibling match (for Create form)
    const siblingMatch = ref<{
        id: number;
        name: string;
        email: string;
        cnic: string;
        occupation: string;
        address: string;
    } | null>(null);
    const linkedGuardianId = ref<number | null>(null);

    // Fee loading state
    const feeLoading = ref(false);

    // Get Father relation ID
    const fatherRelationId = computed(() => {
        const father = props.relations.find((r) => r.name.toLowerCase() === 'father');
        return father?.id || 1;
    });

    // Filter relations to exclude Father for "Other Guardian"
    const otherRelations = computed(() => {
        return props.relations.filter(
            (r) =>
                r.name.toLowerCase() !== 'father'
        );
    });

    // Auto-select campus when only one campus exists
    const campuses = computed(() => props.campuses);

    const shouldAutoSelectCampus = computed(() => {
        return campuses.value.length === 1;
    });

    // Show section field only when sections exist for the selected class
    const showSectionField = computed(() => {
        if (!form.value.class_id) return false;
        return filteredSections.value.length > 0;
    });

    // Auto-select section when only one section exists for a class
    const shouldAutoSelectSection = computed(() => {
        return filteredSections.value.length === 1;
    });

    // Filter sections based on selected class
    const filteredSections = computed(() => {
        if (!form.value.class_id) return props.sections as SectionType[];
        const classId = parseInt(form.value.class_id.toString());
        if (isNaN(classId)) return props.sections as SectionType[];
        return (props.sections as SectionType[]).filter((s) => s.class_id === classId);
    });

    // Show Other Guardian section when checkbox is checked
    const showOtherGuardianSection = computed(() => {
        return includeOtherGuardian.value;
    });

    // Calculated age
    const calculatedAge = computed(() => {
        return calculateAge(form.value.dob);
    });

    // Form state
    const form = ref({
        name: '',
        admission_no: admissionNo,
        dob: '',
        gender_id: '' as string | number,
        b_form: '',
        campus_id: '' as string | number,
        session_id: '' as string | number,
        class_id: '' as string | number,
        section_id: '' as string | number,
        student_status_id: activeStatusId,
        admission_date: todayDate,
        description: '',
        image: null as File | null,
        monthly_fee: 0,
        annual_fee: 0,
        father_name: '',
        father_email: '',
        father_phone: '',
        father_cnic: '',
        father_relation_id: fatherRelationId.value,
        other_name: '',
        other_email: '',
        other_phone: '',
        other_cnic: '',
        linkedGuardianId: null as number | null,
        father_occupation: '',
        father_address: '',
        other_relation_id: '',
    });

    // Fetch fee structure for selected class
    const fetchFeeStructure = async () => {
        const classId = form.value.class_id;
        const sessionId = form.value.session_id;
        const campusId = form.value.campus_id;
        const sectionId = form.value.section_id;

        // Need at least class, session and campus to fetch fee
        if (!classId || !sessionId || !campusId) {
            return;
        }

        feeLoading.value = true;

        try {
            const response = await axios.get(route('fee.structures.by-scope'), {
                params: {
                    session_id: sessionId,
                    campus_id: campusId,
                    class_id: classId,
                    section_id: sectionId || null
                }
            });

            if (response.data.success) {
                const feeData = response.data.data;
                // Only auto-populate if fees are not already manually set
                if (form.value.monthly_fee === 0 || form.value.annual_fee === 0) {
                    form.value.monthly_fee = feeData.monthly_fee || 0;
                    form.value.annual_fee = feeData.annual_fee || 0;
                }
            }
        } catch (error) {
            console.error('Error fetching fee structure:', error);
            // Silently fail - don't disrupt the form
        } finally {
            feeLoading.value = false;
        }
    };

    // Phone formatting handlers
    const handleFatherPhoneInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.value.father_phone = formatPhone(target.value);
    };

    const handleOtherPhoneInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.value.other_phone = formatPhone(target.value);
    };

    // CNIC formatting handlers
    const handleFatherCnicInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.value.father_cnic = formatCnic(target.value);
    };

    const handleOtherCnicInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.value.other_cnic = formatCnic(target.value);
    };

    // B-Form formatting handler
    const handleBFormInput = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.value.b_form = formatCnic(target.value);
    };

    // Image handling
    const handleImageChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];

        imageError.value = null;
        removeCurrentImage.value = false;

        if (file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                imageError.value = 'Please select a valid image file (JPG, PNG, GIF, or WebP)';
                form.value.image = null;
                imagePreview.value = null;
                target.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                imageError.value = 'Image size must be less than 2MB';
                form.value.image = null;
                imagePreview.value = null;
                target.value = '';
                return;
            }

            form.value.image = file;

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.value = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        } else {
            form.value.image = null;
            imagePreview.value = null;
        }
    };

    const removeImage = () => {
        form.value.image = null;
        imagePreview.value = null;
        imageError.value = null;
        removeCurrentImage.value = true;
        const imageInput = document.getElementById('image') as HTMLInputElement;
        if (imageInput) imageInput.value = '';
    };

    // Phone uniqueness validation
    const validatePhoneUniqueness = (): boolean => {
        const fatherPhone = form.value.father_phone?.replace(/\D/g, '') || '';
        const otherPhone = form.value.other_phone?.replace(/\D/g, '') || '';

        if (!fatherPhone || fatherPhone.length < 10) {
            phoneError.value = null;
            return true;
        }

        const phones = [];
        if (fatherPhone.length >= 10) phones.push({ phone: fatherPhone, field: 'father_phone' });
        if (includeOtherGuardian.value && otherPhone.length >= 10) {
            phones.push({ phone: otherPhone, field: 'other_phone' });
        }

        for (let i = 0; i < phones.length; i++) {
            for (let j = i + 1; j < phones.length; j++) {
                if (phones[i].phone === phones[j].phone) {
                    phoneError.value = `Same phone number cannot be used for multiple guardians`;
                    return false;
                }
            }
        }

        phoneError.value = null;
        return true;
    };

    // Clear sibling match
    const clearSiblingMatch = () => {
        siblingMatch.value = null;
        linkedGuardianId.value = null;
        form.value.father_name = '';
        form.value.father_email = '';
        form.value.father_cnic = '';
        form.value.father_occupation = '';
        form.value.father_address = '';
    };

    // Class watcher setup - with fee auto-population
    const setupClassWatcher = (processingRef: import('vue').Ref<boolean>) => {
        watch(
            [() => form.value.class_id, () => form.value.session_id, () => form.value.campus_id],
            ([newClassId, newSessionId, newCampusId], [oldClassId, oldSessionId, oldCampusId]) => {
                if (processingRef.value) return;

                // Handle section selection based on class
                if (newClassId && newClassId !== oldClassId) {
                    const classId = parseInt(newClassId.toString());
                    const classSections = (props.sections as SectionType[]).filter(
                        (s) => s.class_id === classId
                    );

                    if (classSections.length === 0) {
                        form.value.section_id = '';
                    } else if (classSections.length === 1) {
                        form.value.section_id = String(classSections[0].id);
                    } else {
                        form.value.section_id = '';
                    }

                    // Fetch fee structure when class changes
                    fetchFeeStructure();
                }

                // Also fetch if session or campus changes
                if ((newSessionId && newSessionId !== oldSessionId) || 
                    (newCampusId && newCampusId !== oldCampusId)) {
                    if (newClassId) {
                        fetchFeeStructure();
                    }
                }
            }
        );
    };

    // Other Guardian section watcher - clear fields when unchecked
    const setupOtherGuardianWatcher = () => {
        watch(
            () => includeOtherGuardian.value,
            (include) => {
                if (!include) {
                    form.value.other_name = '';
                    form.value.other_email = '';
                    form.value.other_phone = '';
                    form.value.other_cnic = '';
                    form.value.other_relation_id = '';
                }
            }
        );
    };

    // Phone watchers
    const setupPhoneWatchers = () => {
        watch(
            () => form.value.father_phone,
            () => { validatePhoneUniqueness(); }
        );
        watch(
            () => form.value.other_phone,
            () => { validatePhoneUniqueness(); }
        );
        watch(
            () => includeOtherGuardian.value,
            () => { validatePhoneUniqueness(); }
        );
    };

    // Initialize form with student data
    const initializeForm = (studentData?: StudentData | null) => {
        if (studentData) {
            form.value.name = studentData.user?.name || '';
            form.value.admission_no = studentData.admission_no || '';
            form.value.dob = studentData.dob || '';
            form.value.gender_id = studentData.gender_id || '';
            form.value.b_form = studentData.b_form || '';
            form.value.campus_id = studentData.campus_id || '';
            form.value.session_id = studentData.session_id || '';
            form.value.class_id = studentData.class_id || '';
            form.value.section_id = studentData.section_id || '';
            form.value.student_status_id = studentData.student_status_id || activeStatusId;
            form.value.admission_date = studentData.admission_date || todayDate;
            form.value.description = studentData.description || '';
            form.value.monthly_fee = studentData.current_enrollment?.monthly_fee || 0;
            form.value.annual_fee = studentData.current_enrollment?.annual_fee || 0;

            if (studentData.image) {
                imagePreview.value = studentData.image;
            }

            if (studentData.guardians) {
                studentData.guardians.forEach(guardian => {
                    const relationId = guardian.pivot?.relation_id || 0;
                    const relation = props.relations.find(r => r.id === relationId);
                    const relationName = relation?.name?.toLowerCase() || '';

                    if (relationName === 'father') {
                        form.value.father_name = guardian.name;
                        form.value.father_phone = guardian.phone;
                        form.value.father_email = guardian.email;
                        form.value.father_cnic = guardian.cnic;
                        form.value.father_relation_id = relationId;
                    } else {
                        // For any non-father guardian, treat as other guardian
                        form.value.other_name = guardian.name;
                        form.value.other_phone = guardian.phone;
                        form.value.other_email = guardian.email;
                        form.value.other_cnic = guardian.cnic;
                        form.value.other_relation_id = String(relationId);
                        includeOtherGuardian.value = true;
                    }
                });
            }
        }

        if (shouldAutoSelectCampus.value && campuses.value.length > 0) {
            form.value.campus_id = String(campuses.value[0].id);
        }
    };

    return {
        form,
        processing,
        imagePreview,
        imageError,
        removeCurrentImage,
        phoneError,
        siblingMatch,
        linkedGuardianId,
        feeLoading,
        fatherRelationId,
        otherRelations,
        campuses,
        shouldAutoSelectCampus,
        showSectionField,
        shouldAutoSelectSection,
        filteredSections,
        showOtherGuardianSection,
        includeOtherGuardian,
        calculatedAge,
        handleFatherPhoneInput,
        handleOtherPhoneInput,
        handleFatherCnicInput,
        handleOtherCnicInput,
        handleBFormInput,
        handleImageChange,
        removeImage,
        validatePhoneUniqueness,
        clearSiblingMatch,
        setupClassWatcher,
        setupOtherGuardianWatcher,
        setupPhoneWatchers,
        initializeForm,
    };
}
