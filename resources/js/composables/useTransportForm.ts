import { nextTick, ref } from 'vue';
import axios from 'axios';
import { requiredErrors } from '@/composables/useFormValidity';
import { alert } from '@/utils';

/**
 * Shared save flow for the transport dialogs: inline "X is required." errors,
 * scroll to the first invalid field, a small toast, and server errors mapped
 * back under their fields. No submit button is ever disabled to hide a mistake.
 */
export function useTransportForm() {
    const fieldErrors = ref<Record<string, string>>({});
    const submitting = ref(false);

    const scrollToFirstError = async () => {
        await nextTick();
        document
            .querySelector<HTMLElement>('[data-transport-error="true"]')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const failValidation = async (errors: Record<string, string>): Promise<boolean> => {
        fieldErrors.value = errors;

        if (Object.keys(errors).length === 0) {
            return false;
        }

        alert.error('Please check the errors in the form.');
        await scrollToFirstError();

        return true;
    };

    const validateRequired = async <T extends Record<string, unknown>>(
        form: T,
        fields: (keyof T)[],
        labels: Partial<Record<keyof T, string>> = {},
    ): Promise<boolean> => failValidation(requiredErrors(form, fields, labels));

    const reportSaveError = async (error: any, fallback: string) => {
        const errors = error?.response?.data?.errors as Record<string, string[]> | undefined;

        if (error?.response?.status === 422 && errors) {
            await failValidation(Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0]])));

            return;
        }

        alert.error(error?.response?.data?.message || fallback);
    };

    const save = async (request: () => Promise<unknown>, fallback: string): Promise<boolean> => {
        if (submitting.value) {
            return false;
        }

        submitting.value = true;

        try {
            await request();

            return true;
        } catch (error) {
            await reportSaveError(error, fallback);

            return false;
        } finally {
            submitting.value = false;
        }
    };

    return { fieldErrors, submitting, validateRequired, reportSaveError, save, axios };
}

export const formatMoney = (amount: number | string | null | undefined): string =>
    `Rs ${Number(amount || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;

interface RouteOptionSource {
    id: number;
    name: string;
    monthly_fee: number | string | null;
    vehicle?: { vehicle_no: string } | null;
}

/**
 * Dropdown options for transport routes: "Name – Vehicle • Rs fee/mo".
 * Two routes that still read the same get their number appended so they
 * can be told apart.
 */
export const buildRouteOptions = (routes: RouteOptionSource[]): { value: string; label: string }[] => {
    const labels = routes.map(
        (row) => `${row.name} – ${row.vehicle?.vehicle_no ?? 'No vehicle'} • ${formatMoney(row.monthly_fee)}/mo`,
    );

    return routes.map((row, index) => ({
        value: String(row.id),
        label: labels.filter((label) => label === labels[index]).length > 1 ? `${labels[index]} (#${row.id})` : labels[index],
    }));
};
