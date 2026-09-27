<script setup lang="ts">
interface Testimonial {
    quote: string;
    author: string;
    role: string;
    avatarSeed: string;
}

const testimonials: Testimonial[] = [
    {
        quote: "My daughter has flourished here. The teachers know her by name and always take the time to talk to us about her progress.",
        author: "Amara Yusuf",
        role: "Parent, Grade 4",
        avatarSeed: "parent-1"
    },
    {
        quote: "The teachers push you to do your best and are always there to help when something is confusing. I actually look forward to school.",
        author: "Daniel Okoro",
        role: "Student, Grade 9",
        avatarSeed: "student-1"
    },
    {
        quote: "From the safe campus to the sports program, we always felt this was the right place for our son to grow up.",
        author: "Grace Adewale",
        role: "Parent, Grade 7",
        avatarSeed: "parent-2"
    },
];
</script>

<template>
    <section class="py-16 md:py-24 bg-muted/30" aria-labelledby="testimonials-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="testimonials-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    What our families say
                </h2>
                <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    Hear directly from the parents and students who make up our school community.
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <article v-for="(testimonial, index) in testimonials" :key="testimonial.author"
                         class="bg-card border border-border rounded-[var(--radius-lg)] shadow-sm p-6 md:p-8 transition-shadow hover:shadow-md"
                         style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                         :style="{ animationDelay: `${index * 60}ms` }">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                            <img :src="`https://picsum.photos/seed/${testimonial.avatarSeed}/100/100`" 
                                 :alt="`${testimonial.author} avatar`"
                                 class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <p class="font-medium text-foreground">{{ testimonial.author }}</p>
                            <p class="text-sm text-muted-foreground">{{ testimonial.role }}</p>
                        </div>
                    </div>
                    <blockquote class="text-base text-foreground leading-relaxed mb-6">
                        &ldquo;{{ testimonial.quote }}&rdquo;
                    </blockquote>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>