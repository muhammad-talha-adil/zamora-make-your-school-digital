<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import SchoolBrandHeader from '@/components/auth/SchoolBrandHeader.vue';
import PortfolioHeader from '@/components/portfolio/PortfolioHeader.vue';

defineProps<{
    title: string;
    description: string;
    school?: {
        name: string;
        logo_path?: string;
        tagline?: string;
    };
}>();
</script>

<template>
    <!-- Portfolio Header -->
    <PortfolioHeader />

    <!-- Layer 1: Full Screen Background -->
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 dark:from-slate-900 dark:via-slate-800/50 dark:to-slate-900 relative pt-16">
        <!-- Radial glow behind container -->
        <div class="absolute inset-0" style="background: radial-gradient(circle at center, rgba(59, 130, 246, 0.1) 0%, transparent 50%)"></div>
        <!-- Subtle noise -->
        <div class="absolute inset-0 opacity-[0.02] dark:opacity-[0.05]" style="background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,.15) 1px, transparent 0); background-size: 20px 20px;"></div>

        <!-- Layer 2: Glass Container -->
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="auth-shell w-full max-w-6xl min-h-[560px] bg-white/50 dark:bg-gray-900/55 backdrop-blur-[24px] border border-black/6 dark:border-white/10 rounded-2xl shadow-2xl overflow-hidden relative" style="backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);">
                <!-- Subtle radial highlight for light mode -->
                <div class="absolute inset-0 bg-gradient-radial from-white/10 via-transparent to-transparent rounded-2xl pointer-events-none"></div>
                <!-- Layer 3: Two Column Content -->
                <div class="grid lg:grid-cols-2 min-h-[560px] relative">
                    <!-- Vertical Divider (Soft Gradient) -->
                    <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-px bg-gradient-to-b from-transparent via-black/2 dark:via-white/3 to-transparent transform -translate-x-1/2"></div>

                    <!-- Left Column: Story/Visual -->
                    <div class="flex flex-col justify-center p-8 lg:p-12 relative">
                        <div class="space-y-6">
                            <div>
                                <h1 class="text-4xl lg:text-5xl font-bold text-foreground mb-3">Empowering Education Digitally</h1>
                                <p class="text-xl text-muted-foreground">Smart School Management System</p>
                            </div>

                            <ul class="space-y-3 text-foreground/80">
                                <li class="flex items-center space-x-3">
                                    <Icon icon="check-circle" class="w-5 h-5 text-primary" />
                                    <span>Attendance, Exams & Results</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <Icon icon="check-circle" class="w-5 h-5 text-primary" />
                                    <span>Fees, Accounts & Reports</span>
                                </li>
                                <li class="flex items-center space-x-3">
                                    <Icon icon="check-circle" class="w-5 h-5 text-primary" />
                                    <span>Secure role-based access</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Temporary Hero Image - TODO: Replace with custom illustration -->
                        <div class="mt-8 relative">
                            <div class="relative overflow-hidden rounded-2xl shadow-xl">
                                <img
                                    src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                    alt="Education and technology"
                                    class="w-full h-64 lg:h-80 object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-primary/15 via-transparent to-accent/10 dark:from-primary/10 dark:to-accent/15"></div>
                            </div>
                            <p class="text-xs text-muted-foreground/60 mt-2 text-center">Modern learning environment</p>
                        </div>
                    </div>

                    <!-- Right Column: Login Form -->
                    <div class="flex flex-col justify-center p-8 lg:p-12 bg-white/35 dark:bg-white/4">
                        <div class="max-w-md mx-auto w-full space-y-6">
                            <!-- Branding Header -->
                            <SchoolBrandHeader :school="school" />

                            <!-- Login Form Card -->
                            <div class="space-y-6">
                                <div class="text-center">
                                    <h2 class="text-2xl font-semibold text-card-foreground">{{ title }}</h2>
                                    <p class="text-muted-foreground mt-2">{{ description }}</p>
                                </div>

                                <slot />
                            </div>

                            <!-- Trust Cues -->
                            <div class="text-center text-sm text-muted-foreground space-y-2">
                                <div class="flex items-center justify-center space-x-2">
                                    <Icon icon="lock" class="w-4 h-4" />
                                    <span>Secure & encrypted connection</span>
                                </div>
                                <p>Powered by MJS School Management System</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
