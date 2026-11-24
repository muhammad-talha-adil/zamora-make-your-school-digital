import { computed, isRef, watch, type ComputedRef, type Ref } from 'vue';

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

/**
 * Friendly "X is required." messages for every empty required field, so a
 * submit button never has to be disabled to tell the user what is missing.
 */
export function requiredErrors<T extends Record<string, unknown>>(
    form: Ref<T> | T,
    requiredFields: (keyof T)[],
    labels: Partial<Record<keyof T, string>> = {},
): Record<string, string> {
    const data: T = isRef(form) ? form.value : form;
    const errors: Record<string, string> = {};

    requiredFields.forEach((field) => {
        if (!isFieldFilled(data[field])) {
            const key = String(field);
            const label = labels[field] ?? key.replace(/_id$/, '').replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
            errors[key] = `${label} is required.`;
        }
    });

    return errors;
}

/**
 * Drops a field's inline error the moment that field is edited, so a
 * corrected field never keeps showing its old message.
 */
export function useClearErrorsOnEdit<T extends Record<string, unknown>>(form: Ref<T> | T, errors: Ref<Record<string, string>>): void {
    watch(
        () => JSON.stringify(isRef(form) ? form.value : form),
        (now, before) => {
            if (!before) {
                return;
            }
            const next = JSON.parse(now) as Record<string, unknown>;
            const prev = JSON.parse(before) as Record<string, unknown>;
            Object.keys(errors.value ?? {}).forEach((key) => {
                const field = key.split('.')[0];
                if (JSON.stringify(next[field]) !== JSON.stringify(prev[field])) {
                    const { [key]: _removed, ...rest } = errors.value;
                    errors.value = rest;
                }
            });
        },
    );
}
