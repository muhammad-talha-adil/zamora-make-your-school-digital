<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target, revealed } = useScrollReveal();

const testimonials = [
    {
        quote: 'The teachers know my daughter by name and notice when something is off before I do. That kind of attention is rare.',
        name: 'Priya Sharma',
        role: 'Parent, Grade 4',
    },
    {
        quote: 'My son went from dreading maths to asking for extra worksheets. The small class sizes made the difference.',
        name: 'Omar Farooqi',
        role: 'Parent, Grade 7',
    },
    {
        quote: 'Clear communication from the office, fair fee structure, and a campus that actually feels safe. We have no regrets switching here.',
        name: 'Hina Raza',
        role: 'Parent, Grade 2',
    },
];

const initials = (name: string) => name.split(' ').map((part) => part[0]).join('');
</script>

<template>
    <section ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                What parents say
            </h2>

            <div class="mt-12 grid gap-6 md:grid-cols-3" :class="revealed ? 'reveal-visible' : 'reveal'">
                <figure
                    v-for="testimonial in testimonials"
                    :key="testimonial.name"
                    class="flex flex-col rounded-2xl border border-border bg-card p-6"
                >
                    <blockquote class="flex-1 text-sm leading-relaxed text-foreground">
                        "{{ testimonial.quote }}"
                    </blockquote>
                    <figcaption class="mt-5 flex items-center gap-3 border-t border-border pt-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                            {{ initials(testimonial.name) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">{{ testimonial.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ testimonial.role }}</p>
                        </div>
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>
</template>
