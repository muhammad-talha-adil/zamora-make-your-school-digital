<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PaginationLink {
    label: string;
    url: string | null;
    active: boolean;
}

interface PaginationMeta {
    data?: any[];
    links?: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number | null;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

interface PerPageOption {
    id: number;
    name: string;
}

interface Props {
    pagination: PaginationMeta;
    perPage?: number;
    perPageOptions?: PerPageOption[];
    showPerPageSelector?: boolean;
    /** When true, page links navigate via Inertia <Link> (full visit) instead of emitting a `page` event. */
    useLinks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    perPage: undefined,
    showPerPageSelector: true,
    useLinks: false,
    perPageOptions: () => [
        { id: 10, name: '10' },
        { id: 25, name: '25' },
        { id: 50, name: '50' },
        { id: 100, name: '100' },
    ],
});

const effectivePerPage = computed(() => props.perPage ?? props.pagination?.per_page ?? props.pagination?.total ?? 0);

const emit = defineEmits<{
    'update:perPage': [value: number];
    page: [page: number, url: string | null];
}>();

const getPageFromUrl = (url: string | null): number | null => {
    if (!url) {
        return null;
    }

    const page = new URL(url, window.location.origin).searchParams.get('page');
    return page ? Number.parseInt(page, 10) : 1;
};

const isVisible = computed(() => {
    const total = props.pagination?.total ?? 0;
    const lastPage = props.pagination?.last_page;

    if (typeof lastPage === 'number') {
        return lastPage > 1;
    }

    if (props.perPage || props.pagination?.per_page) {
        return total > effectivePerPage.value;
    }

    // No last_page/per_page in the paginator meta (some endpoints omit them):
    // Laravel's default link set is exactly 3 entries (prev, page 1, next) when there is only one page.
    return (props.pagination?.links?.length ?? 0) > 3;
});

const handlePerPageChange = (event: Event): void => {
    const value = Number.parseInt((event.target as HTMLSelectElement).value, 10);
    emit('update:perPage', value);
};

const handlePageClick = (link: PaginationLink): void => {
    const page = getPageFromUrl(link.url);

    if (page) {
        emit('page', page, link.url);
    }
};
</script>

<template>
    <div
        v-if="isVisible"
        class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
            </div>
            <select
                v-if="showPerPageSelector"
                :value="effectivePerPage"
                class="rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 w-20"
                @change="handlePerPageChange"
            >
                <option v-for="option in perPageOptions" :key="option.id" :value="option.id">
                    {{ option.name }}
                </option>
            </select>
        </div>
        <div class="flex flex-wrap gap-1">
            <template v-if="useLinks">
                <Link
                    v-for="link in pagination.links"
                    :key="`${link.label}-${link.url || 'disabled'}`"
                    :href="link.url || '#'"
                    preserve-state
                    preserve-scroll
                    :class="[
                        'inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors min-h-10',
                        link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground hover:bg-accent border-border',
                        !link.url && 'pointer-events-none opacity-50',
                    ]"
                >
                    <span v-html="link.label"></span>
                </Link>
            </template>
            <template v-else>
                <Button
                    v-for="link in pagination.links"
                    :key="`${link.label}-${link.url || 'disabled'}`"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="handlePageClick(link)"
                >
                    <span v-html="link.label"></span>
                </Button>
            </template>
        </div>
    </div>
</template>
