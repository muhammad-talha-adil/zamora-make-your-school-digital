<script setup lang="ts">
/**
 * One action in a table row: an icon button whose name appears on hover.
 *
 * Every table shares this component so the same action looks and reads the
 * same everywhere -- view is always the same icon in the same colour, whether
 * the row is a student, a voucher or a purchase.
 *
 * Colours come from the theme tokens, so they follow the school's palette.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

type ActionKind =
    | 'view'
    | 'edit'
    | 'delete'
    | 'print'
    | 'restore'
    | 'download'
    | 'approve'
    | 'custom';

interface Props {
    /** Which action this is; decides the icon, label and colour. */
    kind?: ActionKind;
    /** Overrides the label shown on hover. */
    label?: string;
    /** Overrides the icon for `kind: 'custom'`, or any other kind. */
    icon?: string;
    /** Renders an Inertia link instead of a button. */
    href?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    kind: 'custom',
    disabled: false,
});

defineEmits<{ click: [MouseEvent] }>();

/**
 * The icon, wording and colour each action carries.
 *
 * Destructive actions are the only ones tinted; the rest stay in the muted
 * foreground until hovered, so a row of five icons does not read as five
 * competing signals.
 */
const PRESETS: Record<Exclude<ActionKind, 'custom'>, { icon: string; label: string; tone: string }> = {
    view: { icon: 'eye', label: 'View', tone: 'text-muted-foreground hover:text-primary hover:bg-primary/10' },
    edit: { icon: 'pencil', label: 'Edit', tone: 'text-muted-foreground hover:text-primary hover:bg-primary/10' },
    print: { icon: 'printer', label: 'Print', tone: 'text-muted-foreground hover:text-foreground hover:bg-accent' },
    download: { icon: 'download', label: 'Download', tone: 'text-muted-foreground hover:text-foreground hover:bg-accent' },
    approve: { icon: 'check', label: 'Approve', tone: 'text-muted-foreground hover:text-success hover:bg-success/10' },
    restore: { icon: 'rotate-ccw', label: 'Restore', tone: 'text-muted-foreground hover:text-success hover:bg-success/10' },
    delete: { icon: 'trash-2', label: 'Delete', tone: 'text-muted-foreground hover:text-destructive hover:bg-destructive/10' },
};

const preset = computed(() =>
    props.kind === 'custom'
        ? { icon: props.icon ?? 'more-horizontal', label: props.label ?? 'Action', tone: 'text-muted-foreground hover:text-foreground hover:bg-accent' }
        : PRESETS[props.kind],
);

const label = computed(() => props.label ?? preset.value.label);
const icon = computed(() => props.icon ?? preset.value.icon);

const classes = computed(() => [
    'inline-flex h-9 w-9 items-center justify-center rounded-md transition-colors',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    props.disabled ? 'pointer-events-none opacity-40' : preset.value.tone,
]);
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Link v-if="href && !disabled" :href="href" :class="classes" :aria-label="label">
                <Icon :icon="icon" class="h-4 w-4" />
            </Link>
            <button
                v-else
                type="button"
                :class="classes"
                :disabled="disabled"
                :aria-label="label"
                @click="$emit('click', $event)"
            >
                <Icon :icon="icon" class="h-4 w-4" />
            </button>
        </TooltipTrigger>
        <TooltipContent>{{ label }}</TooltipContent>
    </Tooltip>
</template>
