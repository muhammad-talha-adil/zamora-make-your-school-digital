<template>
    <div class="space-y-6">
        <!-- Father Information Card (Primary Guardian) -->
        <div
            class="rounded-lg border border-border bg-card p-6"
        >
            <h2
                class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground"
            >
                <Icon icon="user-check" class="h-5 w-5 text-primary" />
                Father Information (Primary Guardian)
            </h2>

            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <!-- Father Name -->
                <div class="space-y-2">
                    <Label for="father_name"
                        >Father's Name
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="father_name"
                        v-model="form.father_name"
                        type="text"
                        placeholder="Enter father's name"
                        :class="{
                            'border-destructive': errors.father_name,
                        }"
                        required
                    />
                    <InputError :message="errors.father_name" />
                </div>

                <!-- Father Phone with Auto-formatting -->
                <div class="space-y-2 relative">
                    <Label for="father_phone"
                        >Father's Phone
                        <span class="text-destructive">*</span></Label
                    >
                    <div class="relative">
                        <Input
                            id="father_phone"
                            v-model="form.father_phone"
                            type="text"
                            placeholder="0321-1234567"
                            maxlength="12"
                            @input="onFatherPhoneInput"
                            :disabled="guardianLookupLoading"
                            :class="{
                                'border-destructive': errors.father_phone,
                            }"
                            required
                        />
                        <!-- Loading spinner -->
                        <Icon
                            v-if="guardianLookupLoading"
                            icon="loader"
                            class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground"></p>
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
                        :class="{
                            'border-destructive': errors.father_email,
                        }"
                    />
                    <InputError :message="errors.father_email" />
                </div>

                <!-- Father CNIC with Auto-formatting -->
                <div class="space-y-2">
                    <Label for="father_cnic">Father's CNIC</Label>
                    <Input
                        id="father_cnic"
                        v-model="form.father_cnic"
                        type="text"
                        placeholder="12345-1234567-1"
                        maxlength="15"
                        @input="handleFatherCnicInput"
                        :disabled="!!linkedGuardianId"
                        :class="{
                            'border-destructive': errors.father_cnic,
                            'cursor-not-allowed opacity-50': !!linkedGuardianId,
                        }"
                    />
                    <p class="text-xs text-muted-foreground"></p>
                    <InputError :message="errors.father_cnic" />
                </div>

                <!-- Father Occupation -->
                <div class="space-y-2">
                    <Label for="father_occupation">Father's Occupation</Label>
                    <Input
                        id="father_occupation"
                        v-model="form.father_occupation"
                        type="text"
                        placeholder="Enter occupation"
                        :disabled="!!linkedGuardianId"
                        :class="{
                            'border-destructive': errors.father_occupation,
                            'cursor-not-allowed opacity-50': !!linkedGuardianId,
                        }"
                    />
                    <InputError :message="errors.father_occupation" />
                </div>

                <!-- Father Address -->
                <div class="space-y-2">
                    <Label for="father_address">Father's Address</Label>
                    <Input
                        id="father_address"
                        v-model="form.father_address"
                        type="text"
                        placeholder="Enter address"
                        :disabled="!!linkedGuardianId"
                        :class="{
                            'border-destructive': errors.father_address,
                            'cursor-not-allowed opacity-50': !!linkedGuardianId,
                        }"
                    />
                    <InputError :message="errors.father_address" />
                </div>

                <!-- Father Relation - PRE-SELECTED AND DISABLED -->
                <div class="space-y-2">
                    <Label for="father_relation_id"
                        >Relation
                        <span class="text-destructive">*</span></Label
                    >
                    <select
                        id="father_relation_id"
                        v-model="form.father_relation_id"
                        class="h-11 w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-sm text-foreground"
                        :class="{
                            'border-destructive': errors.father_relation_id,
                        }"
                        required
                        disabled
                    >
                        <option :value="fatherRelationId">
                            Father
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground"></p>
                    <InputError :message="errors.father_relation_id" />
                </div>
            </div>
        </div>

        <!-- Other Guardian Toggle Checkbox -->
        <div class="rounded-lg border border-border bg-card p-6">
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        id="includeOtherGuardian"
                        v-model="includeOtherGuardian"
                        class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                    />
                    <Label for="includeOtherGuardian" class="text-lg font-semibold text-foreground cursor-pointer">
                        Add Another Guardian
                    </Label>
                </div>
            </div>
        </div>

        <!-- Other Guardian Information Card (Optional) -->
        <div
            v-if="showOtherGuardianSection"
            class="rounded-lg border border-border bg-card p-6"
        >
            <h2
                class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground"
            >
                <Icon icon="users" class="h-5 w-5 text-primary" />
                Other Guardian (Optional)
            </h2>

            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
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

                <!-- Other Guardian Phone with Auto-formatting -->
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
                    <p class="text-xs text-muted-foreground"></p>
                </div>

                <!-- Other Guardian Email -->
                <div class="space-y-2">
                    <Label for="other_email">Guardian Email</Label>
                    <Input
                        id="other_email"
                        v-model="form.other_email"
                        type="email"
                        placeholder="Enter email address"
                        :class="{
                            'border-destructive': errors.other_email,
                        }"
                    />
                    <InputError :message="errors.other_email" />
                </div>

                <!-- Other Guardian CNIC with Auto-formatting -->
                <div class="space-y-2">
                    <Label for="other_cnic">Guardian CNIC</Label>
                    <Input
                        id="other_cnic"
                        v-model="form.other_cnic"
                        type="text"
                        placeholder="12345-1234567-1"
                        maxlength="15"
                        @input="handleOtherCnicInput"
                        :class="{ 'border-destructive': errors.other_cnic }"
                    />
                    <p class="text-xs text-muted-foreground"></p>
                    <InputError :message="errors.other_cnic" />
                </div>

                <!-- Other Guardian Relation -->
                <div class="space-y-2">
                    <Label for="other_relation_id">Relation</Label>
                    <select
                        id="other_relation_id"
                        v-model="form.other_relation_id"
                        class="h-11 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    >
                        <option value="">Select Relation</option>
                        <option
                            v-for="relation in otherRelations"
                            :key="relation.id"
                            :value="relation.id"
                        >
                            {{ relation.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    form: {
        father_name: string;
        father_email: string;
        father_phone: string;
        father_cnic: string;
        father_relation_id: string | number;
        father_occupation: string;
        father_address: string;
        other_name: string;
        other_email: string;
        other_phone: string;
        other_cnic: string;
        other_relation_id: string | number;
    };
    errors?: Record<string, string>;
    fatherRelationId: number | string;
    otherRelations: Array<{ id: number; name: string }>;
    siblingMatch?: {
        id: number;
        name: string;
    } | null;
    linkedGuardianId?: number | null;
    includeOtherGuardian: boolean;
    showOtherGuardianSection: boolean;
    guardianLookupLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    siblingMatch: null,
    linkedGuardianId: null,
    guardianLookupLoading: false,
});

const emit = defineEmits<{
    (e: 'update:form', value: Props['form']): void;
    (e: 'update:includeOtherGuardian', value: boolean): void;
    (e: 'fatherPhoneInput', event: Event): void;
    (e: 'fatherCnicInput', event: Event): void;
    (e: 'otherPhoneInput', event: Event): void;
    (e: 'otherCnicInput', event: Event): void;
}>();

// Computed for v-model bindings
const form = computed({
    get: () => props.form,
    set: (value) => emit('update:form', value),
});

const includeOtherGuardian = computed({
    get: () => props.includeOtherGuardian,
    set: (value) => emit('update:includeOtherGuardian', value),
});

// Handlers
const onFatherPhoneInput = (event: Event) => {
    emit('fatherPhoneInput', event);
};

const handleFatherCnicInput = (event: Event) => {
    emit('fatherCnicInput', event);
};

const handleOtherPhoneInput = (event: Event) => {
    emit('otherPhoneInput', event);
};

const handleOtherCnicInput = (event: Event) => {
    emit('otherCnicInput', event);
};
</script>
