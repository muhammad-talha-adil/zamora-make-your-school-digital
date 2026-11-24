import { usePage } from '@inertiajs/vue3';

/**
 * The header campus as a string, or '' when the viewer is looking at all
 * campuses. Call inside `setup` so list filters and forms start on it.
 */
export function headerCampusId(): string {
    const id = (usePage().props as { academicScope?: { campusId: number | null } }).academicScope?.campusId;

    return id ? String(id) : '';
}
