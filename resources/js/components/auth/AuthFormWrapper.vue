<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Form } from '@inertiajs/vue3';

interface Props {
    /** The submit action URL */
    action: string;
    /** HTTP method (default: post) */
    method?: 'post' | 'put' | 'patch' | 'delete';
    /** Fields to reset on successful submission */
    resetOnSuccess?: string[];
    /** Fields to reset on error */
    resetOnError?: boolean;
    /** Transform function for form data */
    transform?: (data: Record<string, any>) => Record<string, any>;
    /** Custom error banner message */
    errorMessage?: string;
    /** Show error banner */
    showErrorBanner?: boolean;
    /** Custom success handler */
    onSuccess?: () => void;
    /** Custom error handler */
    onError?: () => void;
    /** Additional form classes */
    formClass?: string;
    /** Submit button label */
    submitLabel: string;
    /** Submit button variant */
    submitVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    /** Submit button size */
    submitSize?: 'default' | 'sm' | 'lg' | 'icon' | 'icon-sm' | 'icon-lg';
    /** Whether to show the submit button */
    showSubmit?: boolean;
    /** Whether the form is in a processing state (controlled externally) */
    processing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    method: 'post',
    resetOnSuccess: () => [] as string[],
    resetOnError: false,
    showErrorBanner: true,
    showSubmit: true,
    submitVariant: 'default',
    submitSize: 'default',
    formClass: 'space-y-6',
    processing: false,
});

const emit = defineEmits<{
    submit: [event: SubmitEvent];
    success: [];
    error: [errors: Record<string, string>];
}>();

const handleSuccess = () => {
    emit('success');
    props.onSuccess?.();
};

const handleError = (errors: Record<string, string>) => {
    emit('error', errors);
    props.onError?.();
};
</script>

<template>
    <Form
        :action="action"
        :method="method"
        :reset-on-success="resetOnSuccess"
        :reset-on-error="resetOnError"
        :transform="transform"
        :on-success="handleSuccess"
        :on-error="handleError"
        v-slot="{ errors, processing: formProcessing, clearErrors }"
        :class="formClass"
    >
        <!-- Error Banner -->
        <Alert
            v-if="showErrorBanner && (errorMessage || Object.keys(errors).length > 0)"
            variant="destructive"
            class="animate-in fade-in slide-in-from-top-2 duration-300"
            role="alert"
        >
            <AlertDescription class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </span>
                <span>
                    {{ errorMessage || 'Please check the form for errors and try again.' }}
                </span>
            </AlertDescription>
        </Alert>

        <!-- Form Fields Slot -->
        <slot :errors="errors" :processing="formProcessing || props.processing" :clearErrors="clearErrors" />

        <!-- Submit Button -->
        <Button
            v-if="showSubmit"
            type="submit"
            class="w-full h-11 text-base font-medium transition-all duration-200 hover:shadow-md active:scale-[0.98]"
            :variant="submitVariant"
            :size="submitSize"
            :disabled="formProcessing || props.processing"
            data-test="auth-submit-button"
        >
            <Spinner v-if="formProcessing || props.processing" class="mr-2" />
            {{ (formProcessing || props.processing) ? (submitLabel + '...') : submitLabel }}
        </Button>
    </Form>
</template>