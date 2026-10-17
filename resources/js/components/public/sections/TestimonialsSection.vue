<script setup lang="ts">
import Icon from '@/components/Icon.vue';
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
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                Hear From Our Community
            </h2>

            <div class="mt-12 grid gap-6 md:grid-cols-3" :class="revealed ? 'reveal-visible' : 'reveal'">
                <figure
                    v-for="testimonial in testimonials"
                    :key="testimonial.name"
                    class="flex flex-col rounded-2xl border border-border bg-card p-6 text-left shadow-sm"
                >
                    <div class="flex gap-0.5 text-warning">
                        <Icon v-for="n in 5" :key="n" icon="star" class="h-4 w-4 fill-current" />
                    </div>
                    <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-foreground">
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

            <button type="button" class="mt-10 inline-flex h-10 items-center justify-center rounded-full bg-primary px-6 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90">
                Read More Stories
            </button>
        </div>
    </section>
</template>
