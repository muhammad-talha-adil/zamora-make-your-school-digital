import { usePage } from '@inertiajs/vue3';
import { computed, isRef, ref, watch, type Ref } from 'vue';
import type { AppPageProps } from '@/types';

export interface AcademicCampus {
    id: number;
    name: string;
    [key: string]: unknown;
}

export interface AcademicClass {
    id: number;
    name: string;
    [key: string]: unknown;
}

export interface AcademicSection {
    id: number;
    name: string;
    class_id: number;
    [key: string]: unknown;
}

export interface AcademicSessionOption {
    id: number;
    name: string;
    [key: string]: unknown;
}

export interface UseCascadingAcademicSelectOptions {
    /** Full campus list, as already passed down by the controller. */
    campuses: Ref<AcademicCampus[]> | AcademicCampus[];
    /** Full class list. */
    classes: Ref<AcademicClass[]> | AcademicClass[];
    /** Full section list (every campus/class) — filtered client-side. */
    sections: Ref<AcademicSection[]> | AcademicSection[];
    /** Full session list. */
    sessions: Ref<AcademicSessionOption[]> | AcademicSessionOption[];
    /** Initial values, e.g. from query params or an edit record. */
    initialCampusId?: number | string | null;
    initialClassId?: number | string | null;
    initialSectionId?: number | string | null;
    initialSessionId?: number | string | null;
    /**
     * A distinct key per page so each page's last-picked session is
     * remembered independently in sessionStorage (browser tab session).
     * Defaults to a shared key so unrelated pages agree on "the session I
     * was just working in" unless a page opts into its own key.
     */
    sessionStorageKey?: string;
}

const SESSION_STORAGE_PREFIX = 'zamora.cascade.session.';

function unwrap<T>(value: Ref<T> | T): T {
    return isRef(value) ? value.value : value;
}

/**
 * Shared Campus → Class → Section → Session cascading select logic
 * (#47/#64/#82/#99/#100), reused across the ~22 pages that hand-roll this
 * flow today. Piloted on 4 pages; see docs/ISSUES-RAW.md for the rest.
 *
 * Does not fetch anything itself — it accepts the lists a page's controller
 * already passes as Inertia props (or already fetches) and layers reactive
 * cascading selection + campus-lock + session-default/persist on top.
 */
export function useCascadingAcademicSelect(options: UseCascadingAcademicSelectOptions) {
    const page = usePage<AppPageProps>();
    const scope = computed(() => page.props.academicScope);

    const campuses = computed(() => unwrap(options.campuses));
    const classes = computed(() => unwrap(options.classes));
    const sections = computed(() => unwrap(options.sections));
    const sessions = computed(() => unwrap(options.sessions));

    const storageKey = SESSION_STORAGE_PREFIX + (options.sessionStorageKey ?? 'default');

    const readStoredSessionId = (): number | null => {
        try {
            const raw = sessionStorage.getItem(storageKey);
            return raw ? Number(raw) || null : null;
        } catch {
            return null;
        }
    };

    const storeSessionId = (id: number | string | ''): void => {
        try {
            if (id === '' || id === null || id === undefined) {
                sessionStorage.removeItem(storageKey);
            } else {
                sessionStorage.setItem(storageKey, String(id));
            }
        } catch {
            // Storage can be unavailable (private browsing, quota) — losing
            // the remembered session is no worse than not remembering it.
        }
    };

    /**
     * A campus-restricted user (anyone but developer/owner/super_admin) is
     * locked to their own campus, per `User::isCampusRestricted()` /
     * `campusId()` — reused here via the shared `academicScope` Inertia
     * prop rather than re-deriving the rule on the frontend.
     */
    const isCampusLocked = computed(() => scope.value.isCampusRestricted);
    const lockedCampusId = computed(() => scope.value.campusId);

    const initialCampus = options.initialCampusId ?? lockedCampusId.value ?? '';
    const selectedCampusId = ref<number | string>(initialCampus === null ? '' : initialCampus);

    if (isCampusLocked.value && lockedCampusId.value) {
        selectedCampusId.value = lockedCampusId.value;
    }

    const selectedClassId = ref<number | string>(options.initialClassId ?? '');
    const selectedSectionId = ref<number | string>(options.initialSectionId ?? '');

    const initialSessionId =
        options.initialSessionId ??
        readStoredSessionId() ??
        scope.value.activeSessionId ??
        '';
    const selectedSessionId = ref<number | string>(initialSessionId === null ? '' : initialSessionId);

    watch(selectedSessionId, (value) => storeSessionId(value));

    const availableClasses = computed(() => classes.value);

    const availableSections = computed(() => {
        if (!selectedClassId.value) {
            return sections.value;
        }
        return sections.value.filter((section) => section.class_id === Number(selectedClassId.value));
    });

    watch(selectedCampusId, () => {
        selectedClassId.value = '';
        selectedSectionId.value = '';
    });

    watch(selectedClassId, () => {
        selectedSectionId.value = '';
    });

    return {
        campuses,
        classes: availableClasses,
        sections,
        sessions,
        selectedCampusId,
        selectedClassId,
        selectedSectionId,
        selectedSessionId,
        availableClasses,
        availableSections,
        isCampusLocked,
        lockedCampusId,
    };
}
