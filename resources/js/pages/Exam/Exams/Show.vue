<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Details" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        {{ exam.name }}
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Exam Details and Configuration
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <!--
                        Publishing, locking and reopening had routes and no
                        button anywhere in the application: an exam could be
                        marked and never given out.
                    -->
                    <Button
                        v-if="state.status !== 'published'"
                        :disabled="busy || state.is_locked"
                        @click="publish(false)"
                    >
                        <Icon icon="check" class="mr-1" />
                        Publish Results
                    </Button>

                    <Button
                        v-else
                        variant="outline"
                        :disabled="busy || state.is_locked"
                        @click="unpublish"
                    >
                        <Icon icon="undo" class="mr-1" />
                        Unpublish
                    </Button>

                    <Button
                        v-if="!state.is_locked"
                        variant="outline"
                        :disabled="busy"
                        @click="lock"
                    >
                        <Icon icon="lock" class="mr-1" />
                        Lock
                    </Button>

                    <Button v-else variant="outline" :disabled="busy" @click="unlock">
                        <Icon icon="unlock" class="mr-1" />
                        Reopen
                    </Button>

                    <Button variant="outline" @click="router.visit(route('exam.edit-page', exam.id))">
                        <Icon icon="edit" class="mr-1" />
                        Edit
                    </Button>
                    <Button variant="outline" @click="router.visit(route('exam.index-page'))">
                        <Icon icon="arrow-left" class="mr-1" />
                        Back
                    </Button>
                </div>
            </div>

            <!--
                What stands between this exam and being published.

                A school told only "not ready" presses the button again, so the
                refusal comes back as the list of what is missing — and a
                deliberate override exists for the school that means it anyway.
            -->
            <div
                v-if="problems.length"
                class="rounded-lg border border-warning/40 bg-warning/10 p-4"
            >
                <p class="text-sm font-semibold text-foreground">
                    This exam is not ready to publish:
                </p>
                <ul class="mt-2 list-disc pl-5 text-sm text-muted-foreground">
                    <li v-for="problem in problems" :key="problem">{{ problem }}</li>
                </ul>
                <Button class="mt-3" size="sm" variant="destructive" :disabled="busy" @click="publish(true)">
                    Publish anyway
                </Button>
            </div>

            <p v-if="message" class="rounded-lg border border-success/40 bg-success/10 p-3 text-sm">
                {{ message }}
            </p>

            <!-- Exam Info -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Exam Type</p>
                        <p class="font-medium text-foreground">{{ exam.exam_type?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Session</p>
                        <p class="font-medium text-foreground">{{ exam.session?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Status</p>
                        <span
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full',
                                exam.status === 'active' ? 'bg-success/10 text-success' :
                                exam.status === 'completed' ? 'bg-primary/10 text-primary' :
                                exam.status === 'cancelled' ? 'bg-destructive/10 text-destructive' :
                                'bg-muted text-foreground'
                            ]"
                        >
                            {{ exam.status }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Start Date</p>
                        <p class="font-medium text-foreground">{{ exam.start_date }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">End Date</p>
                        <p class="font-medium text-foreground">{{ exam.end_date }}</p>
                    </div>
                </div>
            </div>

            <!-- Offerings Summary -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">Offerings</h2>
                <div v-if="exam.exam_offerings && exam.exam_offerings.length > 0" class="space-y-3">
                    <div
                        v-for="offering in exam.exam_offerings"
                        :key="offering.id"
                        class="flex flex-wrap gap-2 items-center justify-between p-3 bg-muted rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <Icon icon="building" class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="font-medium text-foreground">{{ offering.campus?.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ offering.exam_groups?.length || 0 }} Groups</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="offering.is_published" class="px-2 py-1 text-xs bg-success/10 text-success rounded-full">Published</span>
                            <span v-if="offering.is_locked" class="px-2 py-1 text-xs bg-destructive/10 text-destructive rounded-full">Locked</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    No offerings created yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { ExamShowProps } from '@/types/exam';

const props = defineProps<ExamShowProps>();

const exam = props.exam;

/** What the buttons act on, so the page reflects a change without a reload. */
const state = reactive({
    status: exam.status as string,
    is_locked: Boolean((exam as { is_locked?: boolean }).is_locked),
});

const problems = ref<string[]>([]);
const message = ref('');
const busy = ref(false);

const run = async (call: () => Promise<{ data: { data?: Record<string, unknown> } }>) => {
    busy.value = true;
    message.value = '';

    try {
        const { data } = await call();
        const fresh = data.data ?? {};

        state.status = (fresh.status as string) ?? state.status;
        state.is_locked = Boolean(fresh.is_locked);
        problems.value = [];

        return true;
    } catch (e: unknown) {
        if (axios.isAxiosError(e) && e.response?.status === 422) {
            // The refusal carries the list. That is the whole point of it.
            problems.value = e.response.data?.errors?.exam ?? [e.response.data?.message];

            return false;
        }

        problems.value = ['That could not be done.'];

        return false;
    } finally {
        busy.value = false;
    }
};

const publish = async (force: boolean) => {
    const ok = await run(() =>
        axios.patch(route('exam.publish', exam.id), force ? { force: true } : {})
    );

    if (ok) message.value = 'Results published.';
};

const unpublish = async () => {
    if (await run(() => axios.patch(route('exam.unpublish', exam.id)))) {
        message.value = 'Taken back off the board.';
    }
};

const lock = async () => {
    if (await run(() => axios.patch(route('exam.lock', exam.id)))) {
        message.value = 'Exam locked.';
    }
};

const unlock = async () => {
    if (await run(() => axios.patch(route('exam.unlock', exam.id)))) {
        message.value = 'Exam reopened.';
    }
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: exam.name, href: `/exams/${exam.id}` },
];
</script>
