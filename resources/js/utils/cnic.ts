/**
 * Formats a CNIC as the user types: 35202-1234567-1. Non-digits are dropped.
 */
export const CNIC_PLACEHOLDER = '35202-1234567-1';

export function formatCnic(raw: string | number | null | undefined): string {
    const digits = String(raw ?? '')
        .replace(/\D+/g, '')
        .slice(0, 13);

    if (digits.length <= 5) {
        return digits;
    }

    if (digits.length <= 12) {
        return `${digits.slice(0, 5)}-${digits.slice(5)}`;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12)}`;
}
