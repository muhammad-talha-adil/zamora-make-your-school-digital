<script setup lang="ts">
/**
 * The year, assembled from the terms.
 *
 * This is the one exam screen that needed a place of its own rather than a tab
 * on something else: every other screen answers a question about *one exam*,
 * and this one answers a question about the session — "how has this child done
 * all year", which is what a parent asks and what promotion is decided on.
 */
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Search, Printer } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import type { BreadcrumbItem } from '@/types'

interface Term {
    exam_id: number
    exam_name: string
    weight: number
    percentage: number | null
    result_status: string | null
    sat: boolean
}

interface Subject {
    subject_id: number
    subject: string | null
    percentage: number | null
    terms: Record<string, number>
}

interface AnnualResult {
    student_id: number
    terms: Term[]
    subjects: Subject[]
    weight_used: number
    weight_configured: number
    percentage: number | null
    grade: string | null
    result_status: string
    is_complete: boolean
}

const props = defineProps<{
    sessions: Array<{ id: number; name: string }>
    classes: Array<{ id: number; name: string }>
}>()

const filters = reactive({
    session_id: '' as string | number,
    class_id: '' as string | number,
    section_id: '' as string | number,
})

const rows = ref<Array<AnnualResult & { name: string; admission_no: string }>>([])
const loading = ref(false)
const error = ref('')
const searched = ref(false)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Exams', href: '/exams' },
    { title: 'Annual Result', href: '/exams/annual-result' },
]

const load = async () => {
    if (!filters.session_id || !filters.class_id) return

    loading.value = true
    error.value = ''

    try {
        const { data } = await axios.get(route('exam.results.annual-section'), {
            params: {
                session_id: filters.session_id,
                class_id: filters.class_id,
                ...(filters.section_id ? { section_id: filters.section_id } : {}),
            },
        })

        // The service keys by student id; the table wants a list.
        rows.value = Object.values(data.data ?? {})
        searched.value = true
    } catch (e: unknown) {
        error.value = e instanceof Error ? e.message : 'Could not load the annual result.'
    } finally {
        loading.value = false
    }
}

/** The terms that make up the year, in the order they were sat. */
const termNames = computed<string[]>(() =>
    rows.value.length ? rows.value[0].terms.map((t) => t.exam_name) : []
)

/**
 * The cards for this child's class, from the last term of the year.
 *
 * The card is per exam; the year's card is the annual paper's, which is the
 * last weighted term.
 */
const printCardsFor = (row: AnnualResult) => {
    const last = row.terms[row.terms.length - 1]

    if (!last) return

    window.open(
        `${route('exam.results.section-cards', last.exam_id)}?class_id=${filters.class_id}`,
        '_blank'
    )
}

const percentageOf = (row: AnnualResult, examName: string): string => {
    const term = row.terms.find((t) => t.exam_name === examName)

    return term?.percentage === null || term?.percentage === undefined
        ? '—'
        : `${term.percentage}%`
}
</script>

<template>
    <Head title="Annual Result" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6 space-y-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">Annual Result</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    The terms weighted into one figure for the year. Only exams the
                    school has given a weight are counted.
                </p>
            </div>

            <div class="rounded-lg border border-border bg-card p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Session</label>
                        <select v-model="filters.session_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Choose a session</option>
                            <option v-for="s in props.sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Class</label>
                        <select v-model="filters.class_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Choose a class</option>
                            <option v-for="c in props.classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <Button :disabled="!filters.session_id || !filters.class_id || loading" @click="load">
                            <Search class="h-4 w-4 mr-2" />
                            {{ loading ? 'Loading…' : 'Show the year' }}
                        </Button>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-destructive">{{ error }}</p>
            </div>

            <div v-if="searched && rows.length" class="rounded-lg border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Adm No</th>
                                <th
                                    v-for="term in termNames"
                                    :key="term"
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider"
                                >
                                    {{ term }}
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Year</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Grade</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Result</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Card</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            <tr v-for="row in rows" :key="row.student_id" class="hover:bg-muted/50">
                                <td class="px-4 py-3 text-sm font-medium">{{ row.name }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.admission_no }}</td>

                                <td
                                    v-for="term in termNames"
                                    :key="term"
                                    class="px-4 py-3 text-sm text-right"
                                >
                                    {{ percentageOf(row, term) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    {{ row.percentage === null ? '—' : row.percentage + '%' }}
                                    <!--
                                        Divided by the weight actually sat, not
                                        the weight configured. A child whose
                                        mid-term is not in yet is shown against
                                        the terms they have sat, and told so.
                                    -->
                                    <span v-if="!row.is_complete" class="block text-[10px] text-warning">
                                        {{ row.weight_used }}% of the year so far
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center text-sm">{{ row.grade ?? '—' }}</td>

                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-medium"
                                        :class="{
                                            'bg-success/10 text-success': row.result_status === 'pass',
                                            'bg-destructive/10 text-destructive': row.result_status === 'fail',
                                            'bg-muted text-muted-foreground': row.result_status === 'pending',
                                        }"
                                    >
                                        {{ row.result_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="!row.terms.length"
                                        @click="printCardsFor(row)"
                                    >
                                        <Printer class="h-4 w-4" />
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
                No weighted exams in this session yet. Give each term a
                <strong>result weight</strong> on the exam — "first term 25, mid 25,
                annual 50" — and the year can be assembled from them.
            </div>
        </div>
    </AppLayout>
</template>
