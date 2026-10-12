<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';

const roleOptions = [
    { value: 'parent', label: 'Parent' },
    { value: 'student', label: 'Prospective student' },
    { value: 'staff', label: 'Staff applicant' },
    { value: 'other', label: 'Other' },
];

const form = useForm({
    name: '',
    email: '',
    role: '',
    message: '',
    honeypot: '',
});

const submit = () => {
    form.post(route('contact.submit'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'role', 'message'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <input v-model="form.honeypot" type="text" class="hidden" tabindex="-1" autocomplete="off" />

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="contact_name" class="mb-1.5 block text-sm font-medium text-foreground">Name *</label>
                <input
                    id="contact_name"
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="Your name"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
            </div>
            <div>
                <label for="contact_email" class="mb-1.5 block text-sm font-medium text-foreground">Email *</label>
                <input
                    id="contact_email"
                    v-model="form.email"
                    type="email"
                    class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
        </div>

        <div>
            <label for="contact_role" class="mb-1.5 block text-sm font-medium text-foreground">I am a</label>
            <SearchableSelect id="contact_role" v-model="form.role" :options="roleOptions" placeholder="Select one" clearable />
        </div>

        <div>
            <label for="contact_message" class="mb-1.5 block text-sm font-medium text-foreground">Message *</label>
            <textarea
                id="contact_message"
                v-model="form.message"
                rows="4"
                class="w-full rounded-md border border-border bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="How can we help?"
            />
            <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
        </div>

        <button type="submit" class="btn-primary h-11 w-full" :disabled="form.processing">
            {{ form.processing ? 'Sending...' : 'Send Message' }}
        </button>

        <p v-if="form.recentlySuccessful" class="text-center text-sm font-medium text-primary">
            Message sent. We will get back to you within 24 hours.
        </p>
    </form>
</template>
