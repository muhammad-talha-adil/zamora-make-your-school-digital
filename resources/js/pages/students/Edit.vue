<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Student" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Student
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Update student and guardian information
                    </p>
                </div>
                <Button variant="outline" @click="router.visit(route('students.index'))">
                    <Icon icon="arrow-left" class="mr-1" />
                    Back to List
                </Button>
            </div>

            <!-- Sibling Match Alert -->
            <div
                v-if="siblingMatch"
                class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"
            >
                <div class="flex items-center gap-3">
                    <Icon
                        icon="users"
                        class="h-5 w-5 text-blue-600 dark:text-blue-400"
                    />
                    <div class="flex-1">
                        <p class="font-medium text-blue-900 dark:text-blue-100">
                            Guardian Found!
                        </p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            A guardian with this phone number exists ({{ siblingMatch.name }}). Information has been updated.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="clearSiblingMatch"
                    >
                        Clear
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6" enctype="multipart/form-data">
                <!-- Student Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Icon icon="user" class="h-5 w-5 text-primary" />
                        Student Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Student Name -->
                        <div class="space-y-2">
                            <Label for="name">Student Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter student name"
                                :class="{ 'border-red-500': errors.name }"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <!-- Admission No -->
                        <div class="space-y-2">
                            <Label for="admission_no">Admission No <span class="text-red-500">*</span></Label>
                            <Input
                                id="admission_no"
                                v-model="form.admission_no"
                                type="text"
                                placeholder="Enter admission number"
                                :class="{ 'border-red-500': errors.admission_no }"
                                required
                            />
                            <InputError :message="errors.admission_no" />
                        </div>

                        <!-- Registration No (Read-only) -->
                        <div class="space-y-2">
                            <Label for="registration_no">Registration No</Label>
                            <Input
                                id="registration_no"
                                :model-value="studentData?.registration_no || ''"
                                type="text"
                                readonly
                                class="bg-gray-100 dark:bg-gray-700"
                            />
                        </div>

                        <!-- Date of Birth with Age Calculation -->
                        <div class="space-y-2">
                            <Label for="dob">Date of Birth <span class="text-red-500">*</span></Label>
                            <Input
                                id="dob"
                                v-model="form.dob"
                                type="date"
                                :class="{ 'border-red-500': errors.dob }"
                                required
                            />
                            <div v-if="calculatedAge" class="text-xs text-gray-500">
                                Calculated Age: <span class="font-medium text-blue-600">{{ calculatedAge }}</span>
                            </div>
                            <InputError :message="errors.dob" />
                        </div>

                        <!-- Gender -->
                        <div class="space-y-2">
                            <Label for="gender_id">Gender <span class="text-red-500">*</span></Label>
                            <select
                                id="gender_id"
                                v-model="form.gender_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.gender_id }"
                                required
                            >
                                <option value="">Select Gender</option>
                                <option v-for="gender in props.genders" :key="gender.id" :value="gender.id">
                                    {{ gender.name }}
                                </option>
                            </select>
                            <InputError :message="errors.gender_id" />
                        </div>

                        <!-- B-Form/CNIC -->
                        <div class="space-y-2">
                            <Label for="b_form">B-Form Number</Label>
                            <Input
                                id="b_form"
                                v-model="form.b_form"
                                type="text"
                                placeholder="Enter B-Form number"
                                :class="{ 'border-red-500': errors.b_form }"
                            />
                            <InputError :message="errors.b_form" />
                        </div>

                        <!-- Campus -->
                        <div class="space-y-2">
                            <Label for="campus_id">Campus <span class="text-red-500">*</span></Label>
                            <select
                                id="campus_id"
                                v-model="form.campus_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.campus_id }"
                                :required="campuses.length > 1"
                            >
                                <option v-if="campuses.length > 1" value="">Select Campus</option>
                                <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <InputError :message="errors.campus_id" />
                        </div>

                        <!-- Session -->
                        <div class="space-y-2">
                            <Label for="session_id">Academic Session <span class="text-red-500">*</span></Label>
                            <select
                                id="session_id"
                                v-model="form.session_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.session_id }"
                                required
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                            <InputError :message="errors.session_id" />
                        </div>

                        <!-- Class -->
                        <div class="space-y-2">
                            <Label for="class_id">Class <span class="text-red-500">*</span></Label>
                            <select
                                id="class_id"
                                v-model="form.class_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.class_id }"
                                required
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                            <InputError :message="errors.class_id" />
                        </div>

                        <!-- Section -->
                        <div v-if="showSectionField" class="space-y-2">
                            <Label for="section_id">Section <span v-if="filteredSections.length > 1" class="text-red-500">*</span></Label>
                            <select
                                id="section_id"
                                v-model="form.section_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.section_id }"
                                :required="filteredSections.length > 1"
                            >
                                <option v-if="filteredSections.length > 1" value="">Select Section</option>
                                <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                            <InputError :message="errors.section_id" />
                        </div>

                        <!-- Status - EDITABLE IN EDIT FORM -->
                        <div class="space-y-2">
                            <Label for="student_status_id">Status <span class="text-red-500">*</span></Label>
                            <select
                                id="student_status_id"
                                v-model="form.student_status_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.student_status_id }"
                                required
                            >
                                <option value="">Select Status</option>
                                <option v-for="status in props.studentStatuses" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>
                            <InputError :message="errors.student_status_id" />
                        </div>

                        <!-- Admission Date -->
                        <div class="space-y-2">
                            <Label for="admission_date">Admission Date</Label>
                            <Input
                                id="admission_date"
                                v-model="form.admission_date"
                                type="date"
                            />
                        </div>

                        <!-- Description -->
                        <div class="col-span-full space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                                placeholder="Additional notes about the student..."
                            ></textarea>
                        </div>

                        <!-- Monthly Fee -->
                        <div class="space-y-2">
                            <Label for="monthly_fee">Monthly Fee</Label>
                            <Input
                                id="monthly_fee"
                                v-model.number="form.monthly_fee"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>

                        <!-- Annual Fee -->
                        <div class="space-y-2">
                            <Label for="annual_fee">Annual Fee</Label>
                            <Input
                                id="annual_fee"
                                v-model.number="form.annual_fee"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>

                        <!-- Student Image Upload -->
                        <div class="col-span-full space-y-2">
                            <Label for="image">Student Photo</Label>
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <Input
                                        id="image"
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        @change="handleImageChange"
                                        class="h-11"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Upload JPG, PNG, GIF, or WebP (max 2MB)</p>
                                    <InputError :message="errors.image" />
                                </div>
                                <!-- Image Preview or Current Image -->
                                <div v-if="imagePreview" class="shrink-0 relative group">
                                    <img
                                        :src="imagePreview"
                                        alt="Student Preview"
                                        class="h-20 w-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                    />
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon"
                                        class="absolute -top-2 -right-2 h-6 w-6 rounded-full"
                                        @click="removeImage"
                                        aria-label="Remove image"
                                    >
                                        <Icon icon="x" class="h-3 w-3" />
                                    </Button>
                                </div>
                                <div v-else-if="studentData?.image && !removeCurrentImage" class="shrink-0 relative group">
                                    <img
                                        :src="studentData.image"
                                        alt="Current Photo"
                                        class="h-20 w-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                    />
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon"
                                        class="absolute -top-2 -right-2 h-6 w-6 rounded-full"
                                        @click="removeImage"
                                        aria-label="Remove current image"
                                    >
                                        <Icon icon="x" class="h-3 w-3" />
                                    </Button>
                                </div>
                                <div v-else class="h-20 w-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-700">
                                    <Icon icon="user" class="h-10 w-10 text-gray-400" />
                                </div>
                            </div>
                            <input v-if="removeCurrentImage" type="hidden" name="remove_image" value="1" />
                        </div>
                    </div>
                </div>

                <!-- Father Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Icon icon="user-check" class="h-5 w-5 text-primary" />
                        Father Information (Primary Guardian)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Father Name -->
                        <div class="space-y-2">
                            <Label for="father_name">Father's Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="father_name"
                                v-model="form.father_name"
                                type="text"
                                placeholder="Enter father's name"
                                :class="{ 'border-red-500': errors.father_name }"
                                required
                            />
                            <InputError :message="errors.father_name" />
                        </div>

                        <!-- Father Phone -->
                        <div class="space-y-2 relative">
                            <Label for="father_phone">Father's Phone <span class="text-red-500">*</span></Label>
                            <div class="relative">
                                <Input
                                    id="father_phone"
                                    v-model="form.father_phone"
                                    type="text"
                                    placeholder="0321-1234567"
                                    maxlength="12"
                                    @input="onFatherPhoneInput"
                                    :disabled="guardianLookupLoading"
                                    :class="{ 'border-red-500': errors.father_phone }"
                                    required
                                />
                                <!-- Loading spinner -->
                                <Icon
                                    v-if="guardianLookupLoading"
                                    icon="loader"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-gray-400"
                                />
                            </div>
                            <p class="text-xs text-gray-500">Format: 0300-1234567</p>
                            <InputError :message="errors.father_phone" />
                        </div>

                        <!-- Father Email -->
                        <div class="space-y-2">
                            <Label for="father_email">Father's Email</Label>
                            <Input
                                id="father_email"
                                v-model="form.father_email"
                                type="email"
                                placeholder="Enter email address"
                                :class="{ 'border-red-500': errors.father_email }"
                            />
                            <InputError :message="errors.father_email" />
                        </div>

                        <!-- Father CNIC -->
                        <div class="space-y-2">
                            <Label for="father_cnic">Father's CNIC</Label>
                            <Input
                                id="father_cnic"
                                v-model="form.father_cnic"
                                type="text"
                                placeholder="12345-1234567-1"
                                maxlength="15"
                                @input="handleFatherCnicInput"
                                :class="{ 'border-red-500': errors.father_cnic }"
                            />
                            <p class="text-xs text-gray-500"></p>
                            <InputError :message="errors.father_cnic" />
                        </div>

                        <!-- Father Relation -->
                        <div class="space-y-2">
                            <Label for="father_relation_id">Relation <span class="text-red-500">*</span></Label>
                            <select
                                id="father_relation_id"
                                v-model="form.father_relation_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm h-11 cursor-not-allowed"
                                required
                                disabled
                            >
                                <option :value="fatherRelationId">Father</option>
                            </select>
                            <InputError :message="errors.father_relation_id" />
                        </div>
                    </div>
                </div>

                

                <!-- Other Guardian Toggle Checkbox -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="includeOtherGuardian"
                                v-model="includeOtherGuardian"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="includeOtherGuardian" class="text-lg font-semibold text-gray-900 dark:text-white cursor-pointer">
                                Add Another Guardian
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Other Guardian Card -->
                <div v-if="showOtherGuardianSection" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Icon icon="users" class="h-5 w-5 text-primary" />
                        Other Guardian (Optional)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Other Guardian Name -->
                        <div class="space-y-2">
                            <Label for="other_name">Guardian Name</Label>
                            <Input
                                id="other_name"
                                v-model="form.other_name"
                                type="text"
                                placeholder="Enter guardian's name"
                            />
                        </div>

                        <!-- Other Guardian Phone -->
                        <div class="space-y-2">
                            <Label for="other_phone">Guardian Phone</Label>
                            <Input
                                id="other_phone"
                                v-model="form.other_phone"
                                type="text"
                                placeholder="0321-1234567"
                                maxlength="12"
                                @input="handleOtherPhoneInput"
                            />
                            <p class="text-xs text-gray-500">Format: 0300-1234567</p>
                        </div>

                        <!-- Other Guardian Email -->
                        <div class="space-y-2">
                            <Label for="other_email">Guardian Email</Label>
                            <Input
                                id="other_email"
                                v-model="form.other_email"
                                type="email"
                                placeholder="Enter email address"
                                :class="{ 'border-red-500': errors.other_email }"
                            />
                            <InputError :message="errors.other_email" />
                        </div>

                        <!-- Other Guardian CNIC -->
                        <div class="space-y-2">
                            <Label for="other_cnic">Guardian CNIC</Label>
                            <Input
                                id="other_cnic"
                                v-model="form.other_cnic"
                                type="text"
                                placeholder="12345-1234567-1"
                                maxlength="15"
                                @input="handleOtherCnicInput"
                                :class="{ 'border-red-500': errors.other_cnic }"
                            />
                            <p class="text-xs text-gray-500"></p>
                            <InputError :message="errors.other_cnic" />
                        </div>

                        <!-- Other Guardian Relation -->
                        <div class="space-y-2">
                            <Label for="other_relation_id">Relation</Label>
                            <select
                                id="other_relation_id"
                                v-model="form.other_relation_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            >
                                <option value="">Select Relation</option>
                                <option v-for="relation in otherRelations" :key="relation.id" :value="relation.id">
                                    {{ relation.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="router.visit(route('students.index'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        Update Student
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { useStudentForm } from '@/composables/useStudentForm';
import { alert } from '@/utils/alert';

interface Props {
    student: {
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
        user?: { name: string };
        guardians?: Array<{
            id: number;
            name: string;
            phone: string;
            email: string;
            cnic: string;
            pivot?: { relation_id: number };
        }>;
        current_enrollment?: { monthly_fee: number; annual_fee: number };
    };
    campuses: Array<{ id: number; name: string }>;
    sessions: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    sections: Array<{ id: number; name: string; class_id?: number | null }>;
    genders: Array<{ id: number; name: string }>;
    relations: Array<{ id: number; name: string }>;
    studentStatuses: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();
const studentData = computed(() => props.student);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: '/students' },
    { title: 'Edit Student', href: `/students/${props.student?.id}/edit` },
];

// Use the shared student form composable
const {
    form,
    processing,
    imagePreview,
    removeCurrentImage,
    siblingMatch,
    linkedGuardianId,
    fatherRelationId,
    otherRelations,
    campuses,
    showSectionField,
    showOtherGuardianSection,
    includeOtherGuardian,
    filteredSections,
    calculatedAge,
    handleFatherPhoneInput,
    handleOtherPhoneInput,
    handleFatherCnicInput,
    handleOtherCnicInput,
    handleImageChange,
    removeImage,
    clearSiblingMatch,
    setupClassWatcher,
    setupOtherGuardianWatcher,
    initializeForm,
} = useStudentForm(props, {
    isEdit: true,
});

const errors = ref<Record<string, string>>({});

// Setup watchers to clear validation errors when user corrects input
const setupErrorClearWatchers = () => {
    // Student info fields
    watch(() => form.value.name, () => { if (errors.value.name) delete errors.value.name; });
    watch(() => form.value.admission_no, () => { if (errors.value.admission_no) delete errors.value.admission_no; });
    watch(() => form.value.dob, () => { if (errors.value.dob) delete errors.value.dob; });
    watch(() => form.value.gender_id, () => { if (errors.value.gender_id) delete errors.value.gender_id; });
    watch(() => form.value.b_form, () => { if (errors.value.b_form) delete errors.value.b_form; });
    watch(() => form.value.campus_id, () => { if (errors.value.campus_id) delete errors.value.campus_id; });
    watch(() => form.value.session_id, () => { if (errors.value.session_id) delete errors.value.session_id; });
    watch(() => form.value.class_id, () => { if (errors.value.class_id) delete errors.value.class_id; });
    watch(() => form.value.section_id, () => { if (errors.value.section_id) delete errors.value.section_id; });
    watch(() => form.value.student_status_id, () => { if (errors.value.student_status_id) delete errors.value.student_status_id; });
    watch(() => form.value.monthly_fee, () => { if (errors.value.monthly_fee) delete errors.value.monthly_fee; });
    watch(() => form.value.annual_fee, () => { if (errors.value.annual_fee) delete errors.value.annual_fee; });
    
    // Father guardian fields
    watch(() => form.value.father_name, () => { if (errors.value.father_name) delete errors.value.father_name; });
    watch(() => form.value.father_phone, () => { if (errors.value.father_phone) delete errors.value.father_phone; });
    watch(() => form.value.father_email, () => { if (errors.value.father_email) delete errors.value.father_email; });
    watch(() => form.value.father_cnic, () => { if (errors.value.father_cnic) delete errors.value.father_cnic; });
    watch(() => form.value.father_occupation, () => { if (errors.value.father_occupation) delete errors.value.father_occupation; });
    watch(() => form.value.father_address, () => { if (errors.value.father_address) delete errors.value.father_address; });
    
    // Other guardian fields
    watch(() => form.value.other_name, () => { if (errors.value.other_name) delete errors.value.other_name; });
    watch(() => form.value.other_phone, () => { if (errors.value.other_phone) delete errors.value.other_phone; });
    watch(() => form.value.other_email, () => { if (errors.value.other_email) delete errors.value.other_email; });
    watch(() => form.value.other_cnic, () => { if (errors.value.other_cnic) delete errors.value.other_cnic; });
    watch(() => form.value.other_relation_id, () => { if (errors.value.other_relation_id) delete errors.value.other_relation_id; });
    
    // Clear all phone-related errors when includeOtherGuardian changes
    watch(() => includeOtherGuardian.value, () => {
        if (errors.value.other_phone) delete errors.value.other_phone;
    });
};

// Setup watchers
setupClassWatcher(processing);
setupOtherGuardianWatcher();
setupErrorClearWatchers();

// Initialize form with student data on mount
onMounted(() => {
    initializeForm(props.student);
});

// Guardian lookup loading state
const guardianLookupLoading = ref(false);

// Combined handler for father phone input
const onFatherPhoneInput = (event: Event) => {
    handleFatherPhoneInput(event);
    lookupFatherGuardian();
};

// Guardian lookup when phone is entered
const lookupFatherGuardian = async () => {
    const rawPhone = form.value.father_phone.replace(/\D/g, '');
    
    // Only trigger API call when phone is complete (11-12 digits)
    if (rawPhone.length < 11) {
        if (siblingMatch.value) {
            siblingMatch.value = null;
            linkedGuardianId.value = null;
        }
        return;
    }
    
    if (processing.value) return;

    guardianLookupLoading.value = true;

    try {
        const response = await axios.get(route('students.guardian-by-phone'), {
            params: { phone: form.value.father_phone },
        });

        if (response.data.found) {
            const guardian = response.data.guardian;
            siblingMatch.value = {
                id: guardian.id,
                name: guardian.name,
                email: guardian.email,
                cnic: guardian.cnic,
                occupation: guardian.occupation || '',
                address: guardian.address || '',
            };
            linkedGuardianId.value = guardian.id;
            form.value.father_name = guardian.name || '';
            form.value.father_email = guardian.email || '';
            form.value.father_cnic = guardian.cnic || '';
            form.value.father_occupation = guardian.occupation || '';
            form.value.father_address = guardian.address || '';
        } else {
            siblingMatch.value = null;
            linkedGuardianId.value = null;
        }
    } catch (error) {
        console.error('Error looking up guardian:', error);
    } finally {
        guardianLookupLoading.value = false;
    }
};

const submitForm = () => {
    processing.value = true;
    errors.value = {};
    const validationErrors: Record<string, string> = {};

    if (!form.value.name?.trim()) {
        validationErrors.name = 'Student name is required';
    }
    if (!form.value.admission_no?.trim()) {
        validationErrors.admission_no = 'Admission number is required';
    }
    if (!form.value.dob) {
        validationErrors.dob = 'Date of birth is required';
    }
    if (!form.value.gender_id) {
        validationErrors.gender_id = 'Gender is required';
    }
    if (!form.value.campus_id) {
        validationErrors.campus_id = 'Campus is required';
    }
    if (!form.value.session_id) {
        validationErrors.session_id = 'Academic session is required';
    }
    if (!form.value.class_id) {
        validationErrors.class_id = 'Class is required';
    }

    // Section validation - only required if sections exist for the class
    if (showSectionField.value && filteredSections.value.length > 0 && !form.value.section_id) {
        validationErrors.section_id = 'Section is required';
    }
    if (!form.value.student_status_id) {
        validationErrors.student_status_id = 'Status is required';
    }
    if (!form.value.father_name?.trim()) {
        validationErrors.father_name = "Father's name is required";
    }
    if (!form.value.father_phone?.trim()) {
        validationErrors.father_phone = "Father's phone is required";
    } else if (!/^\d{4}-\d{7}$/.test(form.value.father_phone)) {
        validationErrors.father_phone = 'Invalid phone format. Use 0300-1234567';
    }

    // Validate email if provided
    if (form.value.father_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.father_email)) {
        validationErrors.father_email = 'Invalid email format';
    }
    if (form.value.other_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.other_email)) {
        validationErrors.other_email = 'Invalid email format';
    }

    // Validate phone formats
    const phoneFields = [
        { field: 'other_phone', name: 'Guardian phone', required: includeOtherGuardian.value && !!form.value.other_phone }
    ];

    phoneFields.forEach(({ field, name, required }) => {
        const value = form.value[field as keyof typeof form.value] as string;
        if (required && !value) {
            validationErrors[field] = `${name} is required`;
        } else if (value && !/^\d{4}-\d{7}$/.test(value)) {
            validationErrors[field] = `Invalid ${name.toLowerCase()} format. Use 0300-1234567`;
        }
    });

    // If there are validation errors, show them
    if (Object.keys(validationErrors).length > 0) {
        errors.value = validationErrors;
        processing.value = false;
        return;
    }

    const formData = new FormData();

    Object.keys(form.value).forEach((key) => {
        const value = form.value[key as keyof typeof form.value];
        if (value !== null && value !== undefined) {
            if (value instanceof File) {
                formData.append(key, value);
            } else if (typeof value === 'object') {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value as string);
            }
        }
    });

    // Handle image removal flag
    if (removeCurrentImage.value) {
        formData.set('remove_image', '1');
    }

    formData.set('gender_id', String(parseInt(form.value.gender_id.toString()) || 0));
    formData.set('campus_id', String(parseInt(form.value.campus_id.toString()) || 0));
    formData.set('session_id', String(parseInt(form.value.session_id.toString()) || 0));
    formData.set('class_id', String(parseInt(form.value.class_id.toString()) || 0));

    // Only set section_id if sections exist for the class and a section is selected
    if (showSectionField.value && form.value.section_id) {
        formData.set('section_id', String(parseInt(form.value.section_id.toString()) || 0));
    }
    formData.set('student_status_id', String(parseInt(form.value.student_status_id.toString()) || 0));
    formData.set('father_relation_id', String(fatherRelationId.value));

    // Handle other guardian data (only if checkbox is checked and name provided)
    if (includeOtherGuardian.value && form.value.other_name && form.value.other_name.trim()) {
        formData.set('other_name', form.value.other_name);
        if (form.value.other_relation_id) {
            formData.set('other_relation_id', String(parseInt(form.value.other_relation_id.toString())));
        }
        if (form.value.other_phone) {
            formData.set('other_phone', form.value.other_phone);
        }
        if (form.value.other_email) {
            formData.set('other_email', form.value.other_email);
        }
        if (form.value.other_cnic) {
            formData.set('other_cnic', form.value.other_cnic);
        }
    }

    // Send guardian_id if sibling match was found and linked
    if (linkedGuardianId.value) {
        formData.set('guardian_id', String(linkedGuardianId.value));
    }

    router.post(route('students.update', props.student.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onFinish: () => { processing.value = false; },
        onSuccess: () => {
            alert.success('Student updated successfully!');
            router.visit(route('students.index'));
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
            processing.value = false;
            // Show prominent error alert with all error messages
            const errorMessages = Object.values(err as Record<string, string>);
            if (errorMessages.length > 0) {
                const firstError = errorMessages[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to update student. Please check the errors and try again.');
            }
        },
    });
};
</script>
