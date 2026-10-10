<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';

interface Props {
    staffProfile: { id: number; user: { name: string } };
    permissionGroups: Record<string, string[]>;
    grantedPermissions: string[];
}

const props = defineProps<Props>();

const search = ref('');
const granted = ref<Set<string>>(new Set(props.grantedPermissions));
const saving = ref(false);

const filteredGroups = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.permissionGroups;
    }

    const result: Record<string, string[]> = {};

    for (const [group, permissions] of Object.entries(props.permissionGroups)) {
        const matches = permissions.filter((p) => p.toLowerCase().includes(term) || group.toLowerCase().includes(term));

        if (matches.length) {
            result[group] = matches;
        }
    }

    return result;
});

const toggle = (permission: string) => {
    if (granted.value.has(permission)) {
        granted.value.delete(permission);
    } else {
        granted.value.add(permission);
    }
    // Re-trigger reactivity — Set mutation alone doesn't notify Vue.
    granted.value = new Set(granted.value);
};

const groupFullyGranted = (permissions: string[]) => permissions.every((p) => granted.value.has(p));

const toggleGroup = (permissions: string[]) => {
    const allGranted = groupFullyGranted(permissions);

    for (const p of permissions) {
        if (allGranted) {
            granted.value.delete(p);
        } else {
            granted.value.add(p);
        }
    }
    granted.value = new Set(granted.value);
};

const save = () => {
    saving.value = true;

    router.put(
        route('staff.people.permissions.update', props.staffProfile.id),
        { permissions: Array.from(granted.value) },
        {
            preserveScroll: true,
            onSuccess: () => alert.success('Permissions updated.'),
            onError: () => alert.error('Failed to update permissions.'),
            onFinish: () => (saving.value = false),
        },
    );
};
</script>

<template>
    <AppLayout>
        <Head :title="`Permissions — ${props.staffProfile.user.name}`" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Permissions — {{ props.staffProfile.user.name }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Grant or remove specific abilities for this staff member, independent of their role. Their sidebar and
                        pages adjust to match.
                    </p>
                </div>
                <Button :disabled="saving" @click="save">
                    <Icon v-if="saving" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                    Save Changes
                </Button>
            </div>

            <div class="relative max-w-sm">
                <Input v-model="search" placeholder="Search permissions..." class="pl-9" />
                <Icon icon="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            </div>

            <div class="space-y-4">
                <div
                    v-for="(permissions, group) in filteredGroups"
                    :key="group"
                    class="rounded-lg border border-border bg-card p-4"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold capitalize text-foreground">{{ group }}</h2>
                        <button
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="toggleGroup(permissions)"
                        >
                            {{ groupFullyGranted(permissions) ? 'Unselect all' : 'Select all' }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="permission in permissions"
                            :key="permission"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-border text-primary"
                                :checked="granted.has(permission)"
                                @change="toggle(permission)"
                            />
                            <span class="truncate text-foreground">{{ permission }}</span>
                        </label>
                    </div>
                </div>

                <div v-if="Object.keys(filteredGroups).length === 0" class="py-12 text-center text-sm text-muted-foreground">
                    No permissions match "{{ search }}".
                </div>
            </div>
        </div>
    </AppLayout>
</template>
