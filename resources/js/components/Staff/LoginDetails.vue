<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';

defineProps<{
    login: { name: string; username: string; email: string; password: string };
}>();

const copy = async (login: { username: string; email: string; password: string }) => {
    try {
        await navigator.clipboard.writeText(`Username: ${login.username}\nEmail: ${login.email}\nPassword: ${login.password}`);
        alert.success('Copied.');
    } catch {
        alert.error('Could not copy. Please write it down.');
    }
};
</script>

<template>
    <div class="rounded-xl bg-primary p-5 text-white">
        <h3 class="text-base font-semibold text-white">Login for {{ login.name }}</h3>
        <p class="mt-1 text-sm text-white">Write this password down now. It will not be shown again.</p>
        <dl class="mt-3 grid gap-2 text-sm text-white sm:grid-cols-3">
            <div>
                <dt class="opacity-90">Username</dt>
                <dd class="font-mono font-semibold">{{ login.username }}</dd>
            </div>
            <div>
                <dt class="opacity-90">Email</dt>
                <dd class="font-mono font-semibold break-all">{{ login.email }}</dd>
            </div>
            <div>
                <dt class="opacity-90">Password</dt>
                <dd class="font-mono text-base font-bold">{{ login.password }}</dd>
            </div>
        </dl>
        <Button type="button" variant="secondary" size="sm" class="mt-3" @click="copy(login)">
            <Icon icon="copy" class="mr-1 h-4 w-4" />
            Copy
        </Button>
    </div>
</template>
