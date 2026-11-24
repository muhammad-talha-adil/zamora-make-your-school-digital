/**
 * Formats a phone number as the user types: Pakistani mobiles become
 * 0333-1112233 (pasted +92 / 0092 / spaces are normalised); anything else is
 * left as digits and common separators.
 */
export const PHONE_PLACEHOLDER = '0333-1112233';

export function formatPhone(raw: string | number | null | undefined): string {
    const value = String(raw ?? '');
    let digits = value.replace(/\D+/g, '');

    if (digits.startsWith('0092')) {
        digits = '0' + digits.slice(4);
    } else if (digits.startsWith('92') && digits.length >= 12) {
        digits = '0' + digits.slice(2);
    } else if (value.trim().startsWith('+92')) {
        digits = '0' + digits.slice(2);
    }

    if (digits.startsWith('03')) {
        digits = digits.slice(0, 11);

        return digits.length > 4 ? `${digits.slice(0, 4)}-${digits.slice(4)}` : digits;
    }

    return value.replace(/[^0-9+\-\s()]/g, '');
}
