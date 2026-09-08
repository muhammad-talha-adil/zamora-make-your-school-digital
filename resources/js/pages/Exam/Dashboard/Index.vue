<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Dashboard" />

        <div class="p-6 space-y-6">
            <!-- Header with Quick Actions -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Exam Dashboard</h1>
                    <p class="text-sm text-muted-foreground mt-1">Overview of your examination management system</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('exam.create-page')" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Exam
                    </Link>
                    <Link :href="route('exam.papers.create-page')" class="inline-flex items-center px-4 py-2 bg-success text-success-foreground rounded-lg hover:bg-success/90 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Add Paper
                    </Link>
                </div>
            </div>

            <!-- Overview Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Exams -->
                <div class="bg-card rounded-xl shadow-sm border border-border p-5">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Exams</p>
                            <p class="text-3xl font-bold text-primary mt-1">{{ stats?.total_exams || 0 }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs">
                        <span class="text-muted-foreground">{{ stats?.exams_by_status?.scheduled || 0 }} scheduled</span>
                        <span class="text-muted-foreground">|</span>
                        <span class="text-success">{{ stats?.exams_by_status?.active || 0 }} active</span>
                    </div>
                </div>

                <!-- Total Papers -->
                <div class="bg-card rounded-xl shadow-sm border border-border p-5">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Papers</p>
                            <p class="text-3xl font-bold text-success mt-1">{{ stats?.total_papers || 0 }}</p>
                        </div>
                        <div class="p-3 bg-success/10 rounded-lg">
                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs">
                        <span class="text-muted-foreground">{{ stats?.papers_by_status?.scheduled || 0 }} scheduled</span>
                        <span class="text-muted-foreground">|</span>
                        <span class="text-primary">{{ stats?.papers_by_status?.completed || 0 }} completed</span>
                    </div>
                </div>

                <!-- Total Registrations -->
                <div class="bg-card rounded-xl shadow-sm border border-border p-5">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Registrations</p>
                            <p class="text-3xl font-bold text-primary mt-1">{{ stats?.total_registrations || 0 }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-muted-foreground">
                        Student enrollments in exams
                    </div>
                </div>

                <!-- Total Results -->
                <div class="bg-card rounded-xl shadow-sm border border-border p-5">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Results Entered</p>
                            <p class="text-3xl font-bold text-warning mt-1">{{ stats?.total_results || 0 }}</p>
                        </div>
                        <div class="p-3 bg-warning/10 rounded-lg">
                            <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-muted-foreground">
                        Marksheets processed
                    </div>
                </div>
            </div>

            <!-- Today's Papers & Upcoming Papers -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Today's Papers -->
                <div class="bg-card rounded-xl shadow-sm border border-border">
                    <div class="p-5 border-b border-border">
                        <div class="flex flex-wrap gap-2 items-center justify-between">
                            <h2 class="text-lg font-semibold text-foreground">Today's Papers</h2>
                            <span class="px-2 py-1 bg-primary/10 text-primary text-xs font-medium rounded-full">
                                {{ stats?.today_papers?.length || 0 }} papers
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div v-if="stats?.today_papers?.length" class="space-y-3">
                            <div v-for="paper in stats.today_papers" :key="paper.id" class="flex flex-wrap gap-2 items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-foreground">{{ paper.subject_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ paper.class_name }} - {{ paper.section_name || 'All Sections' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-foreground">{{ paper.start_time }} - {{ paper.end_time }}</p>
                                    <p class="text-xs text-muted-foreground">{{ paper.total_marks }} marks</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            <svg class="w-12 h-12 mx-auto mb-2 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm">No papers scheduled for today</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Papers (Next 7 Days) -->
                <div class="bg-card rounded-xl shadow-sm border border-border">
                    <div class="p-5 border-b border-border">
                        <div class="flex flex-wrap gap-2 items-center justify-between">
                            <h2 class="text-lg font-semibold text-foreground">Upcoming Papers</h2>
                            <span class="px-2 py-1 bg-success/10 text-success text-xs font-medium rounded-full">
                                Next 7 days
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div v-if="stats?.upcoming_papers?.length" class="space-y-3">
                            <div v-for="paper in stats.upcoming_papers" :key="paper.id" class="flex flex-wrap gap-2 items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-success/10 rounded-lg">
                                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-foreground">{{ paper.subject_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ paper.exam_name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-foreground">{{ paper.paper_date }}</p>
                                    <p class="text-xs text-muted-foreground">{{ paper.start_time }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            <svg class="w-12 h-12 mx-auto mb-2 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm">No upcoming papers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Exams & Quick Links -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Active Exams -->
                <div class="lg:col-span-2 bg-card rounded-xl shadow-sm border border-border">
                    <div class="p-5 border-b border-border">
                        <div class="flex flex-wrap gap-2 items-center justify-between">
                            <h2 class="text-lg font-semibold text-foreground">Active Exams</h2>
                            <Link :href="route('exam.index-page')" class="text-sm text-primary hover:text-primary">
                                View All →
                            </Link>
                        </div>
                    </div>
                    <div class="p-5">
                        <div v-if="stats?.active_exams?.length" class="space-y-3">
                            <div v-for="exam in stats.active_exams" :key="exam.id" class="flex flex-wrap gap-2 items-center justify-between p-4 bg-gradient-to-r from-muted to-muted rounded-lg border border-border">
                                <div class="flex items-center gap-4">
                                    <div :class="{
                                        'bg-warning/10': exam.status === 'scheduled',
                                        'bg-success/10': exam.status === 'active',
                                        'bg-primary/10': exam.status === 'marking',
                                    }" class="p-3 rounded-lg">
                                        <svg class="w-6 h-6" :class="{
                                            'text-warning': exam.status === 'scheduled',
                                            'text-success': exam.status === 'active',
                                            'text-primary': exam.status === 'marking',
                                        }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-foreground">{{ exam.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ exam.exam_type }} • {{ exam.start_date }} to {{ exam.end_date }}</p>
                                        <div class="flex gap-3 mt-1 text-xs">
                                            <span class="text-muted-foreground">{{ exam.papers_count }} papers</span>
                                            <span class="text-muted-foreground">|</span>
                                            <span class="text-muted-foreground">{{ exam.registrations_count }} registered</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="{
                                        'bg-warning/10 text-warning': exam.status === 'scheduled',
                                        'bg-success/10 text-success': exam.status === 'active',
                                        'bg-primary/10 text-primary': exam.status === 'marking',
                                    }" class="px-3 py-1 text-xs font-medium rounded-full capitalize">
                                        {{ exam.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            <svg class="w-12 h-12 mx-auto mb-2 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm">No active exams</p>
                            <Link :href="route('exam.create-page')" class="text-primary hover:underline text-sm">Create your first exam</Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-card rounded-xl shadow-sm border border-border">
                    <div class="p-5 border-b border-border">
                        <h2 class="text-lg font-semibold text-foreground">Quick Links</h2>
                    </div>
                    <div class="p-5 space-y-2">
                        <Link :href="route('exam.papers.index-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">Date Sheet / Papers</span>
                        </Link>
                        <Link :href="route('exam.registrations.index-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-info/10 rounded-lg">
                                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">Registrations</span>
                        </Link>
                        <Link :href="route('exam.marking.select-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">Marking / Results</span>
                        </Link>
                        <Link :href="route('exam.results.index-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-info/10 rounded-lg">
                                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">View Results</span>
                        </Link>
                        <Link :href="route('exam.revaluations.index-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">Revaluations</span>
                        </Link>
                        <Link :href="route('exam.settings.index-page')" class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent transition-colors">
                            <div class="p-2 bg-muted rounded-lg">
                                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="font-medium text-muted-foreground">Settings</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Exam Details Section (When exam is selected) -->
            <div v-if="selectedExamId" class="bg-card rounded-xl shadow-sm border border-border">
                <div class="p-5 border-b border-border">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-foreground">Exam Details: {{ stats?.selected_exam?.name }}</h2>
                            <p class="text-sm text-muted-foreground">Marking progress and grade distribution</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select 
                                v-model="selectedExamId"
                                class="w-full md:w-64 border-border rounded-lg shadow-sm"
                            >
                                <option :value="null">Select an exam</option>
                                <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                    {{ exam.name }}
                                </option>
                            </select>
                            <Link :href="route('exam.show-page', selectedExamId)" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
                
                <div class="p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Marking Progress -->
                        <div>
                            <h3 class="text-md font-semibold text-foreground mb-4">Marking Progress</h3>
                            <div v-if="stats?.marking_progress" class="space-y-4">
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <span class="text-sm text-muted-foreground">Total Registered</span>
                                    <span class="font-semibold text-foreground">{{ stats.marking_progress.total_registered }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <span class="text-sm text-muted-foreground">Marked</span>
                                    <span class="font-semibold text-success">{{ stats.marking_progress.marked }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <span class="text-sm text-muted-foreground">Pending</span>
                                    <span class="font-semibold text-warning">{{ stats.marking_progress.pending }}</span>
                                </div>
                                <div class="mt-2">
                                    <div class="flex flex-wrap gap-2 justify-between text-sm mb-1">
                                        <span class="text-muted-foreground">Progress</span>
                                        <span class="font-semibold text-foreground">{{ stats.marking_progress.percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-muted rounded-full h-2.5">
                                        <div class="bg-primary h-2.5 rounded-full" :style="{ width: stats.marking_progress.percentage + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <p class="text-sm">No marking data available</p>
                            </div>
                        </div>

                        <!-- Grade Distribution -->
                        <div>
                            <h3 class="text-md font-semibold text-foreground mb-4">Grade Distribution</h3>
                            <div v-if="stats?.grade_distribution?.length" class="flex flex-wrap gap-2">
                                <div v-for="item in stats.grade_distribution" :key="item.grade" class="flex items-center gap-2 px-4 py-2 bg-muted rounded-lg">
                                    <span class="font-semibold text-foreground">{{ item.grade }}</span>
                                    <span class="text-muted-foreground">({{ item.count }})</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <p class="text-sm">No grade data available</p>
                            </div>
                        </div>
                    </div>

                    <!-- Toppers -->
                    <div v-if="stats?.toppers?.length" class="mt-6 pt-6 border-t border-border">
                        <h3 class="text-md font-semibold text-foreground mb-4">Top Performers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div v-for="(topper, index) in stats.toppers" :key="topper.id" class="p-4 bg-gradient-to-br from-warning/10 to-warning/10 rounded-xl border border-warning/40">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="flex items-center justify-center w-6 h-6 bg-warning text-warning text-xs font-bold rounded-full">{{ index + 1 }}</span>
                                </div>
                                <p class="font-semibold text-foreground">{{ topper.student_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ topper.class_name }} - {{ topper.section_name }}</p>
                                <div class="mt-2 pt-2 border-t border-warning/40 flex flex-wrap gap-2 justify-between">
                                    <span class="text-sm font-medium text-foreground">{{ topper.percentage }}%</span>
                                    <span class="text-sm font-medium text-success">{{ topper.grade }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Select Exam Prompt (When no exam is selected) -->
            <div v-else class="bg-card rounded-xl shadow-sm border border-border">
                <div class="p-8 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <h3 class="text-lg font-semibold text-foreground mb-2">View Exam Details</h3>
                    <p class="text-muted-foreground mb-4">Select an exam to view marking progress, grade distribution, and top performers</p>
                    <select 
                        v-model="selectedExamId"
                        class="w-full md:w-64 border-border rounded-lg shadow-sm"
                    >
                        <option :value="null">Select an exam</option>
                        <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                            {{ exam.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { Link, Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

interface Exam {
  id: number
  name: string
  status: string
  start_date: string
  end_date: string
}

interface Stats {
  total_exams: number
  total_papers: number
  total_registrations: number
  total_results: number
  exams_by_status: Record<string, number>
  papers_by_status: Record<string, number>
  recent_exams: Exam[]
  active_exams: any[]
  upcoming_papers: any[]
  today_papers: any[]
  selected_exam: Exam | null
  marking_progress: {
    total_registered: number
    marked: number
    pending: number
    percentage: number
  } | null
  grade_distribution: Array<{
    grade: string
    count: number
  }>
  toppers: any[]
  active_grade_system: any
}

const stats = ref<Stats | null>(null)
const loading = ref(false)
const selectedExamId = ref<number | null>(null)
const exams = ref<Exam[]>([])

const loadStats = () => {
  loading.value = true
  const params = selectedExamId.value ? { exam_id: selectedExamId.value } : {}
  
  axios.get(route('exam.dashboard.stats'), { params })
    .then((response) => {
      stats.value = response.data
      loading.value = false
    })
    .catch(() => {
      loading.value = false
    })
}

const loadExams = () => {
  loading.value = true
  
  axios.get(route('exam.index'), {
    params: {}
  })
  .then((response) => {
    exams.value = response.data.data || []
    loading.value = false
  })
  .catch(() => {
    exams.value = []
    loading.value = false
  })
}

onMounted(() => {
  loadStats()
  loadExams()
})

watch(selectedExamId, () => {
  loadStats()
})

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Dashboard', href: '/exams/dashboard' },
]
</script>
