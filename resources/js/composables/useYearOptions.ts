/**
 * Shared list of selectable years, from 2000 to (current year + 1).
 * Mirrors the range used by AcademicSessionForm.vue's endYearOptions.
 */
export function useYearOptions(): number[] {
    const currentYear = new Date().getFullYear();

    return Array.from(
        { length: currentYear + 1 - 2000 + 1 },
        (_, i) => 2000 + i,
    );
}
