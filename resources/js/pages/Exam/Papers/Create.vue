<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Exam Paper" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Create Exam Paper
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Add exam papers to the date sheet - Select class, section, and enter paper details
                </p>
            </div>

            <!-- Error Alert -->
            <Alert v-if="errorMessage" variant="destructive" class="mb-4">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ errorMessage }}</AlertDescription>
            </Alert>

            <!-- Success Alert -->
            <Alert v-if="successMessage" variant="default" class="mb-4 bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">
                <AlertCircle class="h-4 w-4 text-green-600" />
                <AlertTitle class="text-green-800 dark:text-green-400">Success</AlertTitle>
                <AlertDescription class ="text-green-700 dark:text-green-300">{{ successMessage }}</AlertDescription>
            </Alert>

            <!-- Step 1: Selection Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-md font-semibold text-gray-900 dark:text-white mb-4">
                    Step 1: Select Exam Details
                </h2>

                <!-- Pre-selected exam notice -->
                <div v-if="isExamPreSelected" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <Icon icon="info" class="inline h-4 w-4 mr-1" />
                        You are adding papers for a specific exam. The exam selection is locked.
                    </p>
                </div>
                
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Exam Selection -->
                    <div class="space-y-2">
                        <Label for="exam_id">Exam *</Label>
                        <select
                            id="exam_id"
                            v-model="filters.exam_id"
                            @change="onSelectionChange"
                            :disabled="isExamPreSelected"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            :class="{ 'border-red-500': validationErrors.exam_id, 'bg-gray-100 dark:bg-gray-700': isExamPreSelected }"
                        >
                            <option value="">Select Exam</option>
                            <option v-for="exam in props.exams" :key="exam.id" :value="exam.id">
                                {{ exam.name }}
                            </option>
                        </select>
                        <p v-if="validationErrors.exam_id" class="text-xs text-red-500">{{ validationErrors.exam_id }}</p>
                    </div>

                    <!-- Campus Selection -->
                    <div class="space-y-2">
                        <Label for="campus_id">Campus *</Label>
                        <select
                            id="campus_id"
                            v-model="filters.campus_id"
                            @change="onSelectionChange"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            :class="{ 'border-red-500': validationErrors.campus_id }"
                        >
                            <option value="">Select Campus</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                        <p v-if="validationErrors.campus_id" class="text-xs text-red-500">{{ validationErrors.campus_id }}</p>
                    </div>

                    <!-- Class Selection -->
                    <div class="space-y-2">
                        <Label for="class_id">Class *</Label>
                        <select
                            id="class_id"
                            v-model="filters.class_id"
                            @change="onClassChange"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            :class="{ 'border-red-500': validationErrors.class_id }"
                        >
                            <option value="">Select Class</option>
                            <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                        <p v-if="validationErrors.class_id" class="text-xs text-red-500">{{ validationErrors.class_id }}</p>
                    </div>

                    <!-- Section Selection -->
                    <div class="space-y-2">
                        <Label for="section_id">Section</Label>
                        <select
                            id="section_id"
                            v-model="filters.section_id"
                            :disabled="!filters.class_id"
                            @change="onSectionChange"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            :class="{ 'border-red-500': validationErrors.section_id }"
                        >
                            <option value="">Select Section</option>
                            <option value="all">All Sections</option>
                            <option v-for="section in classSections" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                        <p v-if="filters.section_id === 'all'" class="text-xs text-blue-600 dark:text-blue-400">
                            Paper will apply to all sections in this class
                        </p>
                    </div>
                </div>

                <!-- Scope is automatically derived from class/section selection -->

                <!-- Load Subjects Button -->
                <div class="mt-4">
                    <Button 
                        @click="loadPapersOrSubjects" 
                        :disabled="!filters.exam_id || !filters.class_id || loadingSubjects"
                        variant="secondary"
                        class="bg-gray-100 text-gray-800 border-gray-300 hover:bg-gray-200 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        <Icon icon="search" class="mr-1" />
                        {{ loadingSubjects ? 'Loading...' : 'Load Subjects' }}
                    </Button>
                </div>
            </div>

            <!-- Step 2: Subject Papers Table -->
            <div v-if="subjectPapers.length > 0 || existingPapers.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <!-- Existing Papers Section -->
                <div v-if="existingPapers.length > 0" class="mb-6">
                    <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <Icon icon="file-text" class="h-4 w-4" />
                        Existing Papers ({{ existingPapers.length }})
                    </h3>
                    
                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-12">Sr#</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Subject</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Time</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Total</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Pass</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Scope</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="(paper, index) in existingPapers" :key="paper.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ paper.subject?.name }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <template v-if="editingPaperId === paper.id">
                                            <Input 
                                                v-model="editingPaper.paper_date" 
                                                type="date" 
                                                class="w-32 h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            />
                                        </template>
                                        <template v-else>
                                            {{ formatDate(paper.paper_date) }}
                                        </template>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <template v-if="editingPaperId === paper.id">
                                            <div class="flex gap-1">
                                                <Input 
                                                    v-model="editingPaper.start_time" 
                                                    type="time" 
                                                    class="w-20 h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                                />
                                                <Input 
                                                    v-model="editingPaper.end_time" 
                                                    type="time" 
                                                    class="w-20 h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                                />
                                            </div>
                                        </template>
                                        <template v-else>
                                            {{ paper.start_time }} - {{ paper.end_time }}
                                        </template>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <template v-if="editingPaperId === paper.id">
                                            <Input 
                                                v-model="editingPaper.total_marks" 
                                                type="number" 
                                                class="w-20 h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            />
                                        </template>
                                        <template v-else>
                                            {{ paper.total_marks }}
                                        </template>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <template v-if="editingPaperId === paper.id">
                                            <Input 
                                                v-model="editingPaper.passing_marks" 
                                                type="number" 
                                                class="w-20 h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            />
                                        </template>
                                        <template v-else>
                                            {{ paper.passing_marks }}
                                        </template>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span :class="getStatusClass(paper.status)">
                                            {{ paper.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                            {{ paper.scope_type || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <template v-if="editingPaperId === paper.id">
                                            <div class="flex gap-1">
                                                <Button 
                                                    size="sm" 
                                                    variant="outline"
                                                    @click="saveEditedPaper(paper.id)"
                                                    :disabled="savingEdited"
                                                    class="h-6 px-2"
                                                >
                                                    <Icon icon="save" class="h-3 w-3" />
                                                </Button>
                                                <Button 
                                                    size="sm" 
                                                    variant="outline"
                                                    @click="cancelEdit"
                                                    class="h-6 px-2"
                                                >
                                                    <Icon icon="x" class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="flex gap-1">
                                                <Button 
                                                    size="sm" 
                                                    variant="outline"
                                                    @click="startEdit(paper)"
                                                    class="h-6 px-2"
                                                >
                                                    <Icon icon="edit" class="h-3 w-3" />
                                                </Button>
                                                <Button 
                                                    size="sm" 
                                                    variant="outline"
                                                    @click="deletePaper(paper.id)"
                                                    :disabled="deletingPaperId === paper.id"
                                                    class="h-6 px-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                >
                                                    <Icon icon="trash" class="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-2">
                        <div 
                            v-for="(paper, index) in existingPapers" 
                            :key="paper.id"
                            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ index + 1 }}. {{ paper.subject?.name }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="getStatusClass(paper.status)">
                                        {{ paper.status }}
                                    </span>
                                    <span class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                        {{ paper.scope_type || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <div>
                                    <span class="font-medium">Date:</span> {{ formatDate(paper.paper_date) }}
                                </div>
                                <div>
                                    <span class="font-medium">Time:</span> {{ paper.start_time }} - {{ paper.end_time }}
                                </div>
                                <div>
                                    <span class="font-medium">Marks:</span> {{ paper.total_marks }}/{{ paper.passing_marks }}
                                </div>
                            </div>
                            <div class="flex justify-end gap-1 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                <template v-if="editingPaperId === paper.id">
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="saveEditedPaper(paper.id)"
                                        :disabled="savingEdited"
                                        class="h-6 w-6 p-0"
                                    >
                                        <Icon icon="save" class="h-3 w-3" />
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="cancelEdit"
                                        class="h-6 w-6 p-0"
                                    >
                                        <Icon icon="x" class="h-3 w-3" />
                                    </Button>
                                </template>
                                <template v-else>
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="startEdit(paper)"
                                        class="h-6 w-6 p-0"
                                    >
                                        <Icon icon="edit" class="h-3 w-3" />
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="deletePaper(paper.id)"
                                        :disabled="deletingPaperId === paper.id"
                                        class="h-6 w-6 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    >
                                        <Icon icon="trash-2" class="h-3 w-3" />
                                    </Button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Papers Section -->
                <div v-if="subjectPapers.length > 0">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                        <h2 class="text-md font-semibold text-gray-900 dark:text-white">
                            {{ existingPapers.length > 0 ? 'Add More Papers' : 'Step 2: Enter Paper Details' }}
                        </h2>
                        <div class="flex gap-2">
                            <Button 
                                variant="outline" 
                                @click="saveAllPapers" 
                                :disabled="savingAll || !hasAnyValidPaper"
                            >
                                <Icon icon="save" class="mr-1" />
                                {{ savingAll ? 'Saving...' : 'Save All Papers' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Global Settings for Time and Marks -->
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <!-- Exam Date Range Notice -->
                        <div v-if="examDateRange" class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                <Icon icon="calendar" class="inline h-3 w-3 mr-1" />
                                Valid dates for this exam: <strong>{{ examDateRange.start_date }}</strong> to <strong>{{ examDateRange.end_date }}</strong>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex-1 min-w-[150px]">
                                <Label for="global_start_time" class="mb-1 block text-xs">Global Start Time</Label>
                                <Input 
                                    id="global_start_time"
                                    v-model="globalSettings.start_time" 
                                    type="time" 
                                    class="w-full h-9 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <Label for="global_end_time" class="mb-1 block text-xs">Global End Time</Label>
                                <Input 
                                    id="global_end_time"
                                    v-model="globalSettings.end_time" 
                                    type="time" 
                                    class="w-full h-9 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <Label for="global_paper_date" class="mb-1 block text-xs">Global Date</Label>
                                <Input 
                                    id="global_paper_date"
                                    v-model="globalSettings.paper_date" 
                                    type="date" 
                                    class="w-full h-9 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                    :min="examDateRange?.start_date"
                                    :max="examDateRange?.end_date"
                                />
                            </div>
                            
                            <div class="flex-1 min-w-[150px]">
                                <Label for="global_total_marks" class="mb-1 block text-xs">Global Total Marks</Label>
                                <Input 
                                    id="global_total_marks"
                                    v-model="globalSettings.total_marks" 
                                    type="number" 
                                    min="0"
                                    step="0.01"
                                    placeholder="100"
                                    class="w-full h-9 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <Label for="global_passing_marks" class="mb-1 block text-xs">Global Passing Marks</Label>
                                <Input 
                                    id="global_passing_marks"
                                    v-model="globalSettings.passing_marks" 
                                    type="number" 
                                    min="0"
                                    step="0.01"
                                    placeholder="33"
                                    class="w-full h-9 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                />
                            </div>
                            <Button 
                                variant="secondary" 
                                @click="applyGlobalSettings"
                                :disabled="!globalSettings.start_time || !globalSettings.end_time"
                                class="h-9"
                            >
                                <Icon icon="clock" class="mr-1" />
                                Apply to Selected
                            </Button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Set the same time and marks for selected papers. This will apply to all checked papers.
                        </p>
                    </div>

                    <!-- Papers Table - Desktop -->
                    <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-10">
                                        <input
                                            type="checkbox"
                                            :checked="allSelected"
                                            :indeterminate="someSelected"
                                            @change="toggleSelectAll"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600"
                                        />
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-10">
                                        Sr#
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 min-w-32">
                                        Subject
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-32">
                                        Date
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-24">
                                        Start
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-24">
                                        End
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-20">
                                        Total
                                    </th>
                                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-20">
                                        Pass
                                    </th>
                                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-16">
                                        Status
                                    </th>
                                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-16">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr 
                                    v-for="(paper, index) in subjectPapers" 
                                    :key="paper.subject_id"
                                    class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                    :class="{ 'bg-red-50 dark:bg-red-900/10': paper.exists || paper.error }"
                                >
                                    <td class="px-2 py-2 whitespace-nowrap text-center">
                                        <input
                                            type="checkbox"
                                            v-model="paper.selected"
                                            :disabled="paper.saved || paper.exists"
                                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900 dark:text-white">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <div class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-32">
                                            {{ paper.subject_name }}
                                        </div>
                                        <div v-if="paper.exists" class="text-xs text-red-600 dark:text-red-400">
                                            Already added
                                        </div>
                                        <div v-if="paper.error" class="text-xs text-red-600 dark:text-red-400">
                                            {{ paper.error }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <Input 
                                            v-model="paper.paper_date" 
                                            type="date" 
                                            :disabled="!paper.selected"
                                            class="w-full h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            :class="{ 'border-red-500': !paper.paper_date && paper.touched, 'opacity-50': !paper.selected }"
                                            :min="examDateRange?.start_date"
                                            :max="examDateRange?.end_date"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <Input 
                                            v-model="paper.start_time" 
                                            type="time" 
                                            :disabled="!paper.selected"
                                            class="w-full h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            :class="{ 'border-red-500': !paper.start_time && paper.touched, 'opacity-50': !paper.selected }"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <Input 
                                            v-model="paper.end_time" 
                                            type="time" 
                                            :disabled="!paper.selected"
                                            class="w-full h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            :class="{ 'border-red-500': !paper.end_time && paper.touched, 'opacity-50': !paper.selected }"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <Input 
                                            v-model="paper.total_marks" 
                                            type="number" 
                                            min="0"
                                            step="0.01"
                                            placeholder="100"
                                            :disabled="!paper.selected"
                                            class="w-full h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            :class="{ 'border-red-500': !paper.total_marks && paper.touched, 'opacity-50': !paper.selected }"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <Input 
                                            v-model="paper.passing_marks" 
                                            type="number" 
                                            min="0"
                                            step="0.01"
                                            placeholder="33"
                                            :disabled="!paper.selected"
                                            class="w-full h-7 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                            :class="{ 'border-red-500': !paper.passing_marks && paper.touched, 'opacity-50': !paper.selected }"
                                        />
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-center">
                                        <span 
                                            v-if="paper.saved"
                                            class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                        >
                                            Saved
                                        </span>
                                        <span 
                                            v-else-if="paper.exists"
                                            class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
                                        >
                                            Exists
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            Pending
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-center">
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            @click="saveSinglePaper(index)"
                                            :disabled="savingIndex === index || !isPaperValid(paper) || paper.saved"
                                            class="h-6 w-6 p-0"
                                        >
                                            <Icon icon="save" class="h-3 w-3" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Papers Cards - Mobile -->
                    <div class="md:hidden space-y-3">
                        <div 
                            v-for="(paper, index) in subjectPapers" 
                            :key="paper.subject_id"
                            class="bg-white dark:bg-gray-800 rounded-lg border p-3 space-y-2"
                            :class="paper.exists || paper.error ? 'border-red-300 dark:border-red-700' : 'border-gray-200 dark:border-gray-700'"
                        >
                            <div class="flex justify-between items-start">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ index + 1 }}. {{ paper.subject_name }}
                                </div>
                                <span 
                                    v-if="paper.saved"
                                    class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                >
                                    Saved
                                </span>
                                <span 
                                    v-else-if="paper.exists"
                                    class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
                                >
                                    Exists
                                </span>
                                <span 
                                    v-else
                                    class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    Pending
                                </span>
                            </div>
                            
                            <div v-if="paper.error" class="text-xs text-red-600 dark:text-red-400">
                                {{ paper.error }}
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label class="text-xs">Date</Label>
                                    <Input 
                                        v-model="paper.paper_date" 
                                        type="date" 
                                        class="w-full h-8 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        :class="{ 'border-red-500': !paper.paper_date && paper.touched }"
                                        :min="examDateRange?.start_date"
                                        :max="examDateRange?.end_date"
                                    />
                                </div>
                                <div>
                                    <Label class="text-xs">Start Time</Label>
                                    <Input 
                                        v-model="paper.start_time" 
                                        type="time" 
                                        class="w-full h-8 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        :class="{ 'border-red-500': !paper.start_time && paper.touched }"
                                    />
                                </div>
                                <div>
                                    <Label class="text-xs">End Time</Label>
                                    <Input 
                                        v-model="paper.end_time" 
                                        type="time" 
                                        class="w-full h-8 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        :class="{ 'border-red-500': !paper.end_time && paper.touched }"
                                    />
                                </div>
                                <div>
                                    <Label class="text-xs">Total Marks</Label>
                                    <Input 
                                        v-model="paper.total_marks" 
                                        type="number" 
                                        min="0"
                                        step="0.01"
                                        placeholder="100"
                                        class="w-full h-8 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        :class="{ 'border-red-500': !paper.total_marks && paper.touched }"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <Label class="text-xs">Passing Marks</Label>
                                    <Input 
                                        v-model="paper.passing_marks" 
                                        type="number" 
                                        min="0"
                                        step="0.01"
                                        placeholder="33"
                                        class="w-full h-8 text-xs dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                                        :class="{ 'border-red-500': !paper.passing_marks && paper.touched }"
                                    />
                                </div>
                            </div>
                            
                            <div class="flex justify-end pt-1">
                                <Button 
                                    size="sm" 
                                    variant="outline"
                                    @click="saveSinglePaper(index)"
                                    :disabled="savingIndex === index || !isPaperValid(paper) || paper.saved"
                                >
                                    <Icon icon="save" class="h-3 w-3 mr-1" />
                                    {{ paper.saved ? 'Saved' : 'Save' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <span>Saved: {{ savedCount }}</span>
                        <span>Pending: {{ pendingCount }}</span>
                        <span>Selected: {{ selectedCount }}</span>
                        <span>Existing: {{ existingCount }}</span>
                    </div>
                </div>
            </div>

            <!-- No subjects loaded message -->
            <div v-else-if="subjectsLoaded && !loadingSubjects" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    No subjects found for the selected class and section.
                </p>
            </div>

            <!-- Loading State -->
            <div v-if="loadingSubjects" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                <div class="flex justify-center items-center gap-2">
                    <Icon icon="loader" class="h-5 w-5 animate-spin dark:text-gray-400" />
                    <p class="text-gray-500 dark:text-gray-400">Loading...</p>
                </div>
            </div>

            <!-- Back Button -->
            <div class="flex justify-start">
                <Button variant="outline" @click="router.visit(route('exam.index-page'))" class="text-gray-700 bg-white border-gray-300 hover:bg-gray-50 dark:text-white dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600">
                    Back
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import { AlertCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import Icon from '@/components/Icon.vue';

interface Exam {
    id: number;
    name: string;
}

interface Campus {
    id: number;
    name: string;
}

interface SchoolClass {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    class_id: number;
}

interface Subject {
    id: number;
    name: string;
    short_name?: string;
}

interface Props {
    exams: Exam[];
    campuses: Campus[];
    classes: SchoolClass[];
    sections: Section[];
    subjects: Subject[];
    preSelectedExamId?: number | null;
}

const props = defineProps<Props>();

// Exam date range for validation
const examDateRange = ref<{ start_date: string; end_date: string; exam_name: string } | null>(null);

// Check if exam is pre-selected from URL (read-only mode)
const isExamPreSelected = computed(() => !!props.preSelectedExamId);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Papers', href: '/exams/papers' },
    { title: 'Create Paper', href: '/exams/papers/create' },
];

// Filters - scope_type will be derived from class/section selection
const filters = reactive({
    exam_id: '',
    campus_id: '',
    class_id: '',
    section_id: '',
});

// UI State
const classSections = ref<Section[]>([]);
const loadingSubjects = ref(false);
const savingAll = ref(false);
const savingIndex = ref<number | null>(null);
const savingEdited = ref(false);
const deletingPaperId = ref<number | null>(null);
const subjectsLoaded = ref(false);
const isEditing = ref(false);
const editingPaperId = ref<number | null>(null);
const editingPaper = reactive<any>({});
const errorMessage = ref('');
const successMessage = ref('');
const validationErrors = reactive<Record<string, string>>({});

// Global settings for bulk time/date/marks assignment
const globalSettings = reactive({
    start_time: '',
    end_time: '',
    paper_date: '',
    total_marks: '',
    passing_marks: '',
});

// Subject papers data
interface SubjectPaper {
    subject_id: number;
    subject_name: string;
    paper_date: string;
    start_time: string;
    end_time: string;
    total_marks: string;
    passing_marks: string;
    saved: boolean;
    exists: boolean;
    touched: boolean;
    error?: string;
    paper_id?: number; // ID for existing papers (for updates)
    selected: boolean; // Checkbox for selecting paper to add
}

const subjectPapers = ref<SubjectPaper[]>([]);
const existingPapers = ref<any[]>([]);

// Computed property to derive scope_type from dropdown selections
const derivedScopeType = computed(() => {
    // If "All Sections" is selected → CLASS scope (applies to all sections in the class)
    if (filters.section_id === 'all') {
        return 'CLASS';
    }
    // If a specific section is selected → SECTION scope
    if (filters.section_id) {
        return 'SECTION';
    }
    // If class is selected but no specific section → CLASS (default)
    if (filters.class_id) {
        return 'CLASS';
    }
    // Default to CLASS if class is selected (most common case)
    return 'CLASS';
});
const hasAnyValidPaper = computed(() => {
    return subjectPapers.value.some(paper => 
        paper.selected &&
        !paper.saved && 
        !paper.exists &&
        paper.paper_date && 
        paper.start_time && 
        paper.end_time && 
        paper.total_marks && 
        paper.passing_marks
    );
});

const savedCount = computed(() => subjectPapers.value.filter(p => p.saved).length);
const pendingCount = computed(() => subjectPapers.value.filter(p => !p.saved && !p.exists).length);
const existingCount = computed(() => subjectPapers.value.filter(p => p.exists).length);
const selectedCount = computed(() => subjectPapers.value.filter(p => p.selected && !p.saved && !p.exists).length);

// Selection computed properties
const allSelected = computed(() => {
    const pendingPapers = subjectPapers.value.filter(p => !p.saved && !p.exists);
    return pendingPapers.length > 0 && pendingPapers.every(p => p.selected);
});

const someSelected = computed(() => {
    const pendingPapers = subjectPapers.value.filter(p => !p.saved && !p.exists);
    return pendingPapers.some(p => p.selected) && !allSelected.value;
});

const toggleSelectAll = () => {
    const pendingPapers = subjectPapers.value.filter(p => !p.saved && !p.exists);
    const newValue = !allSelected.value;
    pendingPapers.forEach(p => p.selected = newValue);
};

// Edit functions
const startEdit = (paper: any) => {
    editingPaperId.value = paper.id;
    editingPaper.paper_date = paper.paper_date;
    editingPaper.start_time = paper.start_time;
    editingPaper.end_time = paper.end_time;
    editingPaper.total_marks = paper.total_marks;
    editingPaper.passing_marks = paper.passing_marks;
    isEditing.value = true;
};

const cancelEdit = () => {
    editingPaperId.value = null;
    isEditing.value = false;
    Object.keys(editingPaper).forEach(key => delete editingPaper[key]);
};

const saveEditedPaper = async (paperId: number) => {
    savingEdited.value = true;
    try {
        await axios.put(route('exam.papers.update', paperId), {
            paper_date: editingPaper.paper_date,
            start_time: editingPaper.start_time,
            end_time: editingPaper.end_time,
            total_marks: editingPaper.total_marks,
            passing_marks: editingPaper.passing_marks,
        });
        
        // Update the existing papers list
        const index = existingPapers.value.findIndex(p => p.id === paperId);
        if (index !== -1) {
            existingPapers.value[index] = {
                ...existingPapers.value[index],
                paper_date: editingPaper.paper_date,
                start_time: editingPaper.start_time,
                end_time: editingPaper.end_time,
                total_marks: editingPaper.total_marks,
                passing_marks: editingPaper.passing_marks,
            };
        }
        
        successMessage.value = 'Paper updated successfully!';
        cancelEdit();
    } catch (error: any) {
        console.error('Error updating paper:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to update paper';
    } finally {
        savingEdited.value = false;
    }
};

/**
 * Delete an existing paper
 */
const deletePaper = async (paperId: number) => {
    if (!confirm('Are you sure you want to delete this paper? This action cannot be undone.')) {
        return;
    }
    
    deletingPaperId.value = paperId;
    try {
        await axios.delete(route('exam.papers.destroy', paperId));
        
        // Remove from existing papers list
        existingPapers.value = existingPapers.value.filter(p => p.id !== paperId);
        
        // Also remove from subjectPapers if exists
        const subjectPaperIndex = subjectPapers.value.findIndex(p => p.paper_id === paperId);
        if (subjectPaperIndex !== -1) {
            const paper = subjectPapers.value[subjectPaperIndex];
            paper.saved = false;
            paper.exists = false;
            paper.paper_id = undefined;
        }
        
        successMessage.value = 'Paper deleted successfully!';
    } catch (error: any) {
        console.error('Error deleting paper:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to delete paper';
    } finally {
        deletingPaperId.value = null;
    }
};

// Methods
const onClassChange = () => {
    loadSections();
    resetPapers();
};

const onSelectionChange = () => {
    resetPapers();
    loadExamDateRange();
};

/**
 * Load exam date range for frontend validation
 */
const loadExamDateRange = async () => {
    if (!filters.exam_id) {
        examDateRange.value = null;
        return;
    }

    try {
        const response = await axios.get(route('exam.papers.exam-date-range', { exam_id: filters.exam_id }));
        examDateRange.value = response.data;
    } catch (error) {
        console.error('Error loading exam date range:', error);
        examDateRange.value = null;
    }
};

const resetPapers = () => {
    subjectPapers.value = [];
    existingPapers.value = [];
    subjectsLoaded.value = false;
    errorMessage.value = '';
    successMessage.value = '';
    clearValidationErrors();
    // Reset global settings
    globalSettings.start_time = '';
    globalSettings.end_time = '';
    globalSettings.paper_date = '';
    globalSettings.total_marks = '';
    globalSettings.passing_marks = '';
};

const clearValidationErrors = () => {
    Object.keys(validationErrors).forEach(key => {
        delete validationErrors[key];
    });
};

const loadSections = () => {
    if (!filters.class_id || filters.class_id === '') {
        classSections.value = [];
        filters.section_id = '';
        return;
    }

    // Filter sections for the selected class
    classSections.value = props.sections.filter(s => s.class_id === Number(filters.class_id));
    
    // Reset section selection
    filters.section_id = '';
};

const onSectionChange = () => {
    // When "All Sections" is selected, clear specific section
    if (filters.section_id === 'all') {
        // CLASS scope - no specific section
    }
    // Reload papers/subjects when section changes
    if (subjectsLoaded.value) {
        loadPapersOrSubjects();
    }
};

/**
 * Load remaining subjects that don't have papers yet
 */
const loadRemainingSubjects = async (examId: string, classId: string, sectionId: string, campusId: string) => {
    try {
        // Get subjects that don't have papers for this combination
        const params = new URLSearchParams({
            exam_id: examId,
            class_id: classId,
            section_id: sectionId || '',
            campus_id: campusId || '',
            exclude_existing: 'true',
        });

        const response = await axios.get(`/exams/papers/papers-or-subjects?${params}`);

        if (response.data.type === 'subjects' && response.data.data) {
            // Add remaining subjects to subjectPapers
            const newSubjects = response.data.data.map((subject: any) => ({
                subject_id: subject.id,
                subject_name: subject.name,
                paper_date: '',
                start_time: '',
                end_time: '',
                total_marks: '',
                passing_marks: '',
                saved: false,
                exists: false,
                touched: false,
                error: undefined,
                selected: false,
            }));
            
            // Add to existing papers
            subjectPapers.value = [...subjectPapers.value, ...newSubjects];
        }
    } catch (error) {
        console.error('Error loading remaining subjects:', error);
    }
};

const formatDate = (date: string) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        'scheduled': 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'completed': 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'cancelled': 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes['scheduled'];
};

/**
 * Load papers or subjects based on the selection.
 * This is the main function that calls the getPapersOrSubjects endpoint.
 */
const loadPapersOrSubjects = async () => {
    if (!filters.exam_id || !filters.class_id) {
        errorMessage.value = 'Please select both Exam and Class.';
        return;
    }

    loadingSubjects.value = true;
    subjectsLoaded.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    clearValidationErrors();

    const params = new URLSearchParams({
        exam_id: filters.exam_id,
        class_id: filters.class_id,
        section_id: filters.section_id || '',
        campus_id: filters.campus_id || '',
    });

    try {
        const response = await axios.get(`/exams/papers/papers-or-subjects?${params}`);
        const data = response.data;

        if (data.type === 'papers') {
            // Existing papers found - populate them for editing
            existingPapers.value = data.data || [];
            
            // Fetch remaining subjects that don't have papers yet for adding new papers
            loadRemainingSubjects(filters.exam_id, filters.class_id, filters.section_id, filters.campus_id);
            
            errorMessage.value = data.message || `${existingPapers.value.length} existing paper(s) found. You can edit them below.`;
        } else if (data.type === 'subjects') {
            // No existing papers, show subjects
            existingPapers.value = [];
            const subjects = data.data || [];
            
            // Initialize subject papers with empty values
            subjectPapers.value = subjects.map((subject: any) => ({
                subject_id: subject.id,
                subject_name: subject.name,
                paper_date: '',
                start_time: '',
                end_time: '',
                total_marks: '',
                passing_marks: '',
                saved: false,
                exists: subject.exists || false,
                touched: false,
                error: undefined,
                selected: false,
            }));
        }
    } catch (error: any) {
        console.error('Error loading subjects:', error);
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Failed to load data. Please check your connection and try again.';
        }
        subjectPapers.value = [];
        existingPapers.value = [];
    } finally {
        loadingSubjects.value = false;
    }
};

const isPaperValid = (paper: SubjectPaper): boolean => {
    return !!(paper.paper_date && 
        paper.start_time && 
        paper.end_time && 
        paper.total_marks && 
        paper.passing_marks);
};

const saveSinglePaper = async (index: number) => {
    const paper = subjectPapers.value[index];
    
    if (!isPaperValid(paper)) {
        paper.touched = true;
        return;
    }

    savingIndex.value = index;
    paper.error = undefined;

    try {
        let response;
        
        // If paper_id exists, it's an update; otherwise create new
        if (paper.paper_id) {
            // Update existing paper
            response = await axios.put(route('exam.papers.update', paper.paper_id), {
                exam_id: filters.exam_id,
                subject_id: paper.subject_id,
                campus_id: filters.campus_id || null,
                class_id: filters.class_id,
                section_id: derivedScopeType.value === 'CLASS' ? null : (filters.section_id || null),
                scope_type: derivedScopeType.value,
                paper_date: paper.paper_date,
                start_time: paper.start_time,
                end_time: paper.end_time,
                total_marks: paper.total_marks,
                passing_marks: paper.passing_marks,
            });
        } else {
            // Create new paper
            response = await axios.post(route('exam.papers.store-single'), {
                exam_id: filters.exam_id,
                subject_id: paper.subject_id,
                campus_id: filters.campus_id || null,
                class_id: filters.class_id,
                section_id: derivedScopeType.value === 'CLASS' ? null : (filters.section_id || null),
                scope_type: derivedScopeType.value,
                paper_date: paper.paper_date,
                start_time: paper.start_time,
                end_time: paper.end_time,
                total_marks: paper.total_marks,
                passing_marks: paper.passing_marks,
            });
        }

        const data = response.data;

        if (response.status === 200 || response.status === 201) {
            paper.saved = true;
            paper.exists = false;
            // Store paper_id if this was a create
            if (data.data && data.data.id) {
                paper.paper_id = data.data.id;
            }
            successMessage.value = paper.paper_id ? 'Paper updated successfully!' : 'Paper saved successfully!';
        }
    } catch (error: any) {
        console.error('Error saving paper:', error);
        // Handle different error types
        if (error.response?.data?.error === 'duplicate') {
            paper.exists = true;
            paper.saved = false;
            paper.error = error.response.data.message || 'Paper already exists';
        } else if (error.response?.data?.error === 'hierarchy_conflict') {
            // Handle hierarchical conflicts (CLASS vs SECTION)
            if (error.response.data?.conflict_type === 'class_level_exists') {
                paper.error = 'A CLASS-level paper already exists for this subject. Remove it first or select a different scope.';
            } else if (error.response.data?.conflict_type === 'section_level_exists') {
                paper.error = 'SECTION-level papers already exist for this subject. Remove them first or change scope to CLASS.';
            } else {
                paper.error = error.response.data.message || 'Scope conflict detected';
            }
        } else if (error.response?.data?.error === 'clash') {
            paper.error = error.response.data.message || 'Schedule conflict detected';
        } else if (error.response?.data?.error === 'invalid_date') {
            paper.error = error.response.data.message || 'Paper date is outside the exam date range';
        } else if (error.response?.data?.error === 'date_overlap') {
            paper.error = error.response.data.message || 'A paper already exists on this date for this class/section';
        } else if (error.response?.data?.message) {
            paper.error = error.response.data.message;
        } else {
            paper.error = 'Failed to save paper';
        }
    } finally {
        savingIndex.value = null;
    }
};

const saveAllPapers = async () => {
    const validPapers = subjectPapers.value.filter(p => 
        p.selected &&
        !p.saved && 
        !p.exists &&
        isPaperValid(p)
    );

    if (validPapers.length === 0) return;

    savingAll.value = true;

    try {
        const papers = validPapers.map(p => ({
            subject_id: p.subject_id,
            paper_date: p.paper_date,
            start_time: p.start_time,
            end_time: p.end_time,
            total_marks: p.total_marks,
            passing_marks: p.passing_marks,
        }));

        const response = await axios.post(route('exam.papers.store-bulk'), {
            exam_id: filters.exam_id,
            campus_id: filters.campus_id || null,
            class_id: filters.class_id,
            section_id: derivedScopeType.value === 'CLASS' ? null : (filters.section_id || null),
            scope_type: derivedScopeType.value,
            papers: papers,
        });

        const data = response.data;

        if (response.status === 200 || response.status === 201) {
            // Mark all valid papers as saved
            subjectPapers.value.forEach((paper) => {
                if (!paper.saved && !paper.exists && isPaperValid(paper)) {
                    paper.saved = true;
                }
            });
            
            // Check for skipped papers (duplicates)
            if (data.skipped && data.skipped.length > 0) {
                data.skipped.forEach((skipped: any) => {
                    const paper = subjectPapers.value.find(p => p.subject_id === skipped.subject_id);
                    if (paper) {
                        paper.exists = true;
                        paper.saved = false;
                        paper.error = skipped.reason || 'Paper already exists';
                    }
                });
            }
            
            // Check for clashed papers
            if (data.clashed && data.clashed.length > 0) {
                data.clashed.forEach((clashed: any) => {
                    const paper = subjectPapers.value.find(p => p.subject_id === clashed.subject_id);
                    if (paper) {
                        paper.error = clashed.reason || 'Schedule conflict detected';
                    }
                });
            }

            // Check for invalid date papers
            if (data.invalid_dates && data.invalid_dates.length > 0) {
                data.invalid_dates.forEach((invalid: any) => {
                    const paper = subjectPapers.value.find(p => p.subject_id === invalid.subject_id);
                    if (paper) {
                        paper.error = invalid.reason || 'Paper date is outside the exam date range';
                    }
                });
            }

            // Check for date overlap papers
            if (data.date_overlaps && data.date_overlaps.length > 0) {
                data.date_overlaps.forEach((overlap: any) => {
                    const paper = subjectPapers.value.find(p => p.subject_id === overlap.subject_id);
                    if (paper) {
                        paper.error = overlap.reason || 'A paper already exists on this date for this class/section';
                    }
                });
            }

            // Show summary message
            const messages = [];
            if (data.created_count > 0) messages.push(`${data.created_count} paper(s) created successfully`);
            if (data.skipped_count > 0) messages.push(`${data.skipped_count} skipped (already exists)`);
            if (data.clashed_count > 0) messages.push(`${data.clashed_count} failed (schedule conflict)`);
            if (data.invalid_date_count > 0) messages.push(`${data.invalid_date_count} failed (invalid date)`);
            if (data.date_overlap_count > 0) messages.push(`${data.date_overlap_count} failed (date overlap)`);
            
            successMessage.value = messages.join('. ');
        }
    } catch (error: any) {
        console.error('Error saving all papers:', error);
        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Network error. Please check your connection and try again.';
        }
    } finally {
        savingAll.value = false;
    }
};

/**
 * Apply global time, date and marks settings to all pending papers
 */
const applyGlobalSettings = () => {
    if (!globalSettings.start_time || !globalSettings.end_time) {
        return;
    }

    let count = 0;
    subjectPapers.value.forEach((paper) => {
        // Only apply to selected pending papers (not saved, not existing)
        if (paper.selected && !paper.saved && !paper.exists) {
            paper.start_time = globalSettings.start_time;
            paper.end_time = globalSettings.end_time;
            if (globalSettings.paper_date) {
                paper.paper_date = globalSettings.paper_date;
            }
            if (globalSettings.total_marks) {
                paper.total_marks = globalSettings.total_marks;
            }
            if (globalSettings.passing_marks) {
                paper.passing_marks = globalSettings.passing_marks;
            }
            count++;
        }
    });

    if (count > 0) {
        successMessage.value = `Applied settings to ${count} selected paper(s).`;
    }
};

// Watch for class changes to reset sections
watch(() => filters.class_id, () => {
    loadSections();
    resetPapers();
});

// Also watch for campus changes
watch(() => filters.campus_id, () => {
    resetPapers();
});

// Auto-select exam if pre-selected from URL and load papers
onMounted(() => {
    if (props.preSelectedExamId) {
        filters.exam_id = String(props.preSelectedExamId);
        // Trigger selection change to load papers
        onSelectionChange();
    }
});
</script>
