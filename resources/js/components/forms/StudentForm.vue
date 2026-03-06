<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ComboboxInput } from '@/components/ui/combobox';
import Icon from '@/components/Icon.vue';

// Props
interface Props {
    student?: {
        id: number;
        user?: {
            name: string;
            email: string;
        };
        admission_no: string;
        status: string;
        admission_date?: string;
        campus_id: number;
        class_id?: number;
        date_of_birth?: string;
        cnic?: string;
        gender_id?: number;
        religion_id?: number;
        session_id?: number;
        section_id?: number;
        primary_guardian?: {
            name: string;
            relation_id?: number;
            email?: string;
            contact?: string;
            address?: string;
            city_id?: number;
        };
    };
    classes: Array<{
        id: number;
        name: string;
    }>;
    sessions: Array<{
        id: number;
        name: string;
    }>;
    sections: Array<{
        id: number;
        name: string;
    }>;
    cities: Array<{
        id: number;
        name: string;
        district: string;
        tahsil: string;
    }>;
    genders: Array<{
        id: number;
        name: string;
    }>;
    religions: Array<{
        id: number;
        name: string;
    }>;
    relations: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Student',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [student?: any];
}>();

// Form data
const form = ref({
    name: props.student?.user?.name || '',
    email: props.student?.user?.email || '',
    admission_no: props.student?.admission_no || '',
    status: props.student?.status || 'active',
    admission_date: props.student?.admission_date || '',
    campus_id: props.student?.campus_id || 1,
    class_id: props.student?.class_id || null,
    date_of_birth: props.student?.date_of_birth || '',
    cnic: props.student?.cnic || '',
    gender_id: props.student?.gender_id || null,
    religion_id: props.student?.religion_id || null,
    session_id: props.student?.session_id || null,
    section_id: props.student?.section_id || null,
    // Guardian fields
    guardian_name: props.student?.primary_guardian?.name || '',
    guardian_relation_id: props.student?.primary_guardian?.relation_id || null,
    guardian_email: props.student?.primary_guardian?.email || '',
    guardian_contact: props.student?.primary_guardian?.contact || '',
    guardian_address: props.student?.primary_guardian?.address || '',
    guardian_city_id: props.student?.primary_guardian?.city_id || null,
});

const errors = ref({});
const processing = ref(false);

// Dialog
const open = ref(false);

// Options
const statusOptions = [
    { id: 'active', name: 'Active' },
    { id: 'inactive', name: 'Inactive' },
];

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};

    if (props.student) {
        // Update
        router.put(`/students/${props.student.id}`, form.value, {
            preserveScroll: true,
            onSuccess: (response) => {
                alert.success('Student updated successfully!');
                open.value = false;
                resetForm();
                emit('saved');
            },
            onError: (err) => {
                errors.value = err;
                alert.error('Failed to update student. Please check the errors.');
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } else {
        // Create
        router.post('/students', form.value, {
            preserveScroll: true,
            onSuccess: (response) => {
                alert.success('Student created successfully!');
                open.value = false;
                resetForm();
                // For create, we need to fetch to get the new student with id
                emit('saved');
            },
            onError: (err) => {
                errors.value = err;
                alert.error('Failed to create student. Please check the errors.');
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    }
};

const resetForm = () => {
    form.value = {
        name: '',
        email: '',
        admission_no: '',
        status: 'active',
        admission_date: '',
        campus_id: 1,
        class_id: null,
        date_of_birth: '',
        cnic: '',
        gender_id: null,
        religion_id: null,
        session_id: null,
        section_id: null,
        guardian_name: '',
        guardian_relation_id: null,
        guardian_email: '',
        guardian_contact: '',
        guardian_address: '',
        guardian_city_id: null,
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.student" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[900px] max-h-[90vh] overflow-y-auto">
            <form
                @submit.prevent="submit"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>{{ student ? 'Edit Student' : 'Add Student' }}</DialogTitle>
                    <DialogDescription>
                        {{ student ? 'Update the student details.' : 'Create a new student record.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-6 py-4">
                    <!-- Student Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Student Information</h4>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="grid gap-2">
                                <Label for="admission_no">Admission No *</Label>
                                <Input
                                    id="admission_no"
                                    v-model="form.admission_no"
                                    placeholder="ADM-001"
                                />
                                <InputError :message="(errors as any).admission_no" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="name">Full Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="John Doe"
                                />
                                <InputError :message="(errors as any).name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="date_of_birth">Date of Birth</Label>
                                <Input
                                    id="date_of_birth"
                                    v-model="form.date_of_birth"
                                    type="date"
                                />
                                <InputError :message="(errors as any).date_of_birth" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="cnic">CNIC</Label>
                                <Input
                                    id="cnic"
                                    v-model="form.cnic"
                                    placeholder="12345-1234567-1"
                                />
                                <InputError :message="(errors as any).cnic" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="grid gap-2">
                                <Label for="gender_id">Gender</Label>
                                <ComboboxInput
                                    id="gender_id"
                                    v-model="form.gender_id"
                                    placeholder="Search genders..."
                                    :search-url="'/genders/search'"
                                    :initial-items="genders"
                                />
                                <InputError :message="(errors as any).gender_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="religion_id">Religion</Label>
                                <ComboboxInput
                                    id="religion_id"
                                    v-model="form.religion_id"
                                    placeholder="Search religions..."
                                    :search-url="'/religions/search'"
                                    :initial-items="religions"
                                />
                                <InputError :message="(errors as any).religion_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="student@email.com"
                                />
                                <InputError :message="(errors as any).email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <ComboboxInput
                                    id="status"
                                    v-model="form.status"
                                    placeholder="Select status..."
                                    :initial-items="statusOptions"
                                    value-type="id"
                                />
                                <InputError :message="(errors as any).status" />
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Academic Information</h4>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="grid gap-2">
                                <Label for="class_id">Class</Label>
                                <ComboboxInput
                                    id="class_id"
                                    v-model="form.class_id"
                                    placeholder="Search classes..."
                                    :search-url="'/classes/search'"
                                    :initial-items="classes"
                                />
                                <InputError :message="(errors as any).class_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="section_id">Section</Label>
                                <ComboboxInput
                                    id="section_id"
                                    v-model="form.section_id"
                                    placeholder="Search sections..."
                                    :search-url="'/sections/search'"
                                    :initial-items="sections"
                                />
                                <InputError :message="(errors as any).section_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="session_id">Session</Label>
                                <ComboboxInput
                                    id="session_id"
                                    v-model="form.session_id"
                                    placeholder="Search sessions..."
                                    :search-url="'/sessions/search'"
                                    :initial-items="sessions"
                                />
                                <InputError :message="(errors as any).session_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="admission_date">Admission Date</Label>
                                <Input
                                    id="admission_date"
                                    v-model="form.admission_date"
                                    type="date"
                                />
                                <InputError :message="(errors as any).admission_date" />
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Information -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Guardian Information</h4>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="grid gap-2">
                                <Label for="guardian_name">Guardian Name</Label>
                                <Input
                                    id="guardian_name"
                                    v-model="form.guardian_name"
                                    placeholder="Enter guardian's name"
                                />
                                <InputError :message="(errors as any).guardian_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="guardian_relation_id">Relation</Label>
                                <ComboboxInput
                                    id="guardian_relation_id"
                                    v-model="form.guardian_relation_id"
                                    placeholder="Search relations..."
                                    :search-url="'/relations/search'"
                                    :initial-items="relations"
                                />
                                <InputError :message="(errors as any).guardian_relation_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="guardian_email">Email</Label>
                                <Input
                                    id="guardian_email"
                                    v-model="form.guardian_email"
                                    type="email"
                                    placeholder="guardian@email.com"
                                />
                                <InputError :message="(errors as any).guardian_email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="guardian_contact">Contact</Label>
                                <Input
                                    id="guardian_contact"
                                    v-model="form.guardian_contact"
                                    placeholder="+92-300-1234567"
                                />
                                <InputError :message="(errors as any).guardian_contact" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="guardian_address">Address</Label>
                                <textarea
                                    id="guardian_address"
                                    v-model="form.guardian_address"
                                    class="flex min-h-[40px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Complete address"
                                    rows="2"
                                ></textarea>
                                <InputError :message="(errors as any).guardian_address" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="guardian_city_id">City</Label>
                                <ComboboxInput
                                    id="guardian_city_id"
                                    v-model="form.guardian_city_id"
                                    placeholder="Search cities..."
                                    :search-url="'/cities/search'"
                                    :initial-items="cities"
                                />
                                <InputError :message="(errors as any).guardian_city_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label>District</Label>
                                <Input
                                    :value="cities.find(c => c.id === form.guardian_city_id)?.district || ''"
                                    readonly
                                    class="bg-gray-50 dark:bg-gray-800"
                                    placeholder="Auto-filled"
                                />
                                <Label class="text-xs text-gray-500">Tahsil</Label>
                                <Input
                                    :value="cities.find(c => c.id === form.guardian_city_id)?.tahsil || ''"
                                    readonly
                                    class="bg-gray-50 dark:bg-gray-800"
                                    placeholder="Auto-filled"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="resetForm"
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ student ? 'Update' : 'Create' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
