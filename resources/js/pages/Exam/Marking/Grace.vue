<script setup lang="ts">
/**
 * Grace marks.
 *
 * A separate screen rather than a column on the marking grid, because it is a
 * separate act: the grid is a teacher entering what a child scored, and this is
 * somebody deciding to lift a child who missed the pass mark by two. They sit
 * behind different abilities for the same reason.
 *
 * The list is the one a school actually works from — "these six are within
 * three marks" — rather than forty rows to scroll through.
 */
import { Head } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Search, Check } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'

interface Candidate {
    result_line_id: number
    student_id: number
    student_name: string | null
    subject: string | null
    obtained: number
    passing: number
    short_by: number
    headroom: number
}

const props = defineProps<{
    exams: Array<{ id: number; name: string }>
    classes: Array<{ id: number; name: string }>
}>()

const filters = reactive({
    exam_id: '' as string | number,
    class_id: '' as string | number,
    within: 3,
})

const candidates = ref<Candidate[]>([])
const granting = ref<number | null>(null)
const loading = ref(false)
const message = ref('')
const error = ref('')
const searched = ref(false)

/** What the school is about to give each child, keyed by result line. */
const amounts = reactive<Record<number, number>>({})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Exams', href: '/exams' },
    { title: 'Marking', href: '/exams/marking' },
    { title: 'Grace Marks', href: '/exams/marking/grace' },
]

const load = async () => {
    if (!filters.exam_id) return

    loading.value = true
    error.value = ''
    message.value = ''

    try {
        const { data } = await axios.get(route('exam.marking.grace-candidates'), {
            params: {
                exam_id: filters.exam_id,
                within: filters.within,
                ...(filters.class_id ? { class_id: filters.class_id } : {}),
            },
        })

        candidates.value = data.data ?? []

        // Pre-filled with exactly what lifts them to the pass mark, which is
        // what a school gives nine times in ten.
        candidates.value.forEach((c) => {
            amounts[c.result_line_id] = c.short_by
        })

        searched.value = true
    } catch (e: unknown) {
        error.value = e instanceof Error ? e.message : 'Could not load the list.'
    } finally {
        loading.value = false
    }
}

const give = async (candidate: Candidate) => {
    granting.value = candidate.result_line_id
    error.value = ''
    message.value = ''

    try {
        await axios.put(route('exam.marking.grace', candidate.result_line_id), {
            grace_marks: amounts[candidate.result_line_id],
            reason: `Short of the pass mark by ${candidate.short_by}.`,
        })

        message.value = `${candidate.student_name} lifted in ${candidate.subject}.`

        // Gone from the list, because they are no longer short.
        candidates.value = candidates.value.filter(
            (c) => c.result_line_id !== candidate.result_line_id
        )
    } catch (e: unknown) {
        error.value =
            (axios.isAxiosError(e) && e.response?.data?.message) ||
            'That grace could not be given.'
    } finally {
        granting.value = null
    }
}
</script>

<template>
    <Head title="Grace Marks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6 space-y-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">Grace Marks</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Children who missed a pass mark by a little. Grace is recorded
                    <strong>as grace</strong> and never merged into what the child
                    scored, so the school can always answer the parent holding the
                    answer sheet.
                </p>
            </div>

            <div class="rounded-lg border border-border bg-card p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Exam</label>
                        <select v-model="filters.exam_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Choose an exam</option>
                            <option v-for="e in props.exams" :key="e.id" :value="e.id">{{ e.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Class</label>
                        <select v-model="filters.class_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">All classes</option>
                            <option v-for="c in props.classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Within</label>
                        <input
                            v-model.number="filters.within"
                            type="number"
                            min="1"
                            max="20"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="flex items-end">
                        <Button :disabled="!filters.exam_id || loading" @click="load">
                            <Search class="h-4 w-4 mr-2" />
                            {{ loading ? 'Loading…' : 'Find them' }}
                        </Button>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-destructive">{{ error }}</p>
                <p v-if="message" class="mt-4 text-sm text-success">{{ message }}</p>
            </div>

            <div v-if="candidates.length" class="rounded-lg border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Subject</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Scored</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Pass mark</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Short by</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Grace</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-for="c in candidates" :key="c.result_line_id" class="hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-medium">{{ c.student_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ c.subject }}</td>
                                <td class="px-4 py-3 text-sm text-right">{{ c.obtained }}</td>
                                <td class="px-4 py-3 text-sm text-right text-muted-foreground">{{ c.passing }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-destructive">
                                    {{ c.short_by }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input
                                        v-model.number="amounts[c.result_line_id]"
                                        type="number"
                                        min="0"
                                        :max="c.headroom"
                                        step="0.5"
                                        class="w-20 text-center rounded-md border border-input bg-background px-2 py-1 text-sm"
                                    />
                                    <!-- The school's own cap, and the paper's
                                         total, whichever is lower. -->
                                    <span class="block text-[10px] text-muted-foreground">
                                        up to {{ c.headroom }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <Button
                                        size="sm"
                                        :disabled="granting === c.result_line_id || !amounts[c.result_line_id]"
                                        @click="give(c)"
                                    >
                                        <Check class="h-4 w-4 mr-1" />
                                        Give
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-else-if="searched"
                class="rounded-lg border border-dashed border-border p-10 text-center text-sm text-muted-foreground"
            >
                Nobody is within {{ filters.within }} marks of a pass in this exam.
            </div>
        </div>
    </AppLayout>
</template>
