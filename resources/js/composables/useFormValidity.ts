import { computed, isRef, type ComputedRef, type Ref } from 'vue';

/**
 * Checks whether a single form field value counts as "filled".
 */
function isFieldFilled(value: unknown): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    if (typeof value === 'string') {
        return value.trim().length > 0;
    }

    if (Array.isArray(value)) {
        return value.length > 0;
    }

    return true;
}

/**
 * Reactive check that all required fields of a form are filled.
 *
 * Works with either a `ref({...})` form object (the app's common convention)
 * or an Inertia `useForm({...})` instance, since both expose their fields
 * either via `.value` or directly on the object.
 *
 * @param form Form data source: a `Ref` wrapping the field object, or the reactive object itself.
 * @param requiredFields Names of fields that must be non-empty for the form to be considered valid.
 */
export function useFormValidity<T extends Record<string, unknown>>(
    form: Ref<T> | T,
    requiredFields: (keyof T)[],
): { isValid: ComputedRef<boolean> } {
    const isValid = computed<boolean>(() => {
        const data: T = isRef(form) ? form.value : form;

        return requiredFields.every((field) => isFieldFilled(data[field]));
    });

    return { isValid };
}
