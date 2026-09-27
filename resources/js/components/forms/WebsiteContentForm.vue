<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import RepeaterList from '@/components/forms/RepeaterList.vue';

interface SchoolWebsiteContent {
    hero_headline?: string | null;
    hero_subtext?: string | null;
    hero_cta_primary_text?: string | null;
    hero_cta_primary_url?: string | null;
    hero_cta_secondary_text?: string | null;
    hero_cta_secondary_url?: string | null;
    hero_illustration_seed?: string | null;
    mission_statement?: string | null;
    vision_statement?: string | null;
    values?: Array<{ icon: string; title: string; description: string }> | null;
    leadership_team?: Array<{ name: string; role: string; bio: string; photo_url: string }> | null;
    stats?: Array<{ label: string; value: string; suffix: string }> | null;
    history_timeline?: Array<{ year: string; title: string; description: string }> | null;
    contact_address?: string | null;
    contact_phone?: string | null;
    contact_email?: string | null;
    contact_hours?: string | null;
    map_embed_url?: string | null;
    social_links?: Array<{ platform: string; url: string }> | null;
    academics_hero_headline?: string | null;
    academics_hero_subtext?: string | null;
    academics_programs?: Array<{ title: string; description: string }> | null;
    academics_faq?: Array<{ question: string; answer: string }> | null;
    admissions_hero_headline?: string | null;
    admissions_hero_subtext?: string | null;
    admission_steps?: Array<{ title: string; description: string }> | null;
    admissions_faq?: Array<{ question: string; answer: string }> | null;
    home_features?: Array<{ icon: string; title: string; description: string; seed: string }> | null;
}

interface Props {
    school?: SchoolWebsiteContent;
}

const props = withDefaults(defineProps<Props>(), {});

const emit = defineEmits<{ saved: [] }>();

const defaultForm = () => ({
    hero_headline: props.school?.hero_headline ?? '',
    hero_subtext: props.school?.hero_subtext ?? '',
    hero_cta_primary_text: props.school?.hero_cta_primary_text ?? '',
    hero_cta_primary_url: props.school?.hero_cta_primary_url ?? '',
    hero_cta_secondary_text: props.school?.hero_cta_secondary_text ?? '',
    hero_cta_secondary_url: props.school?.hero_cta_secondary_url ?? '',
    hero_illustration_seed: props.school?.hero_illustration_seed ?? '',
    mission_statement: props.school?.mission_statement ?? '',
    vision_statement: props.school?.vision_statement ?? '',
    values: props.school?.values ?? [],
    leadership_team: props.school?.leadership_team ?? [],
    stats: props.school?.stats ?? [],
    history_timeline: props.school?.history_timeline ?? [],
    contact_address: props.school?.contact_address ?? '',
    contact_phone: props.school?.contact_phone ?? '',
    contact_email: props.school?.contact_email ?? '',
    contact_hours: props.school?.contact_hours ?? '',
    map_embed_url: props.school?.map_embed_url ?? '',
    social_links: props.school?.social_links ?? [],
    academics_hero_headline: props.school?.academics_hero_headline ?? '',
    academics_hero_subtext: props.school?.academics_hero_subtext ?? '',
    academics_programs: props.school?.academics_programs ?? [],
    academics_faq: props.school?.academics_faq ?? [],
    admissions_hero_headline: props.school?.admissions_hero_headline ?? '',
    admissions_hero_subtext: props.school?.admissions_hero_subtext ?? '',
    admission_steps: props.school?.admission_steps ?? [],
    admissions_faq: props.school?.admissions_faq ?? [],
    home_features: props.school?.home_features ?? [],
});

const form = ref(defaultForm());
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = () => {
    processing.value = true;
    errors.value = {};

    router.post('/settings/school-profile/website-content', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Website content updated successfully!');
            emit('saved');
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
            alert.error('Failed to update website content. Please check the errors.');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Home Hero -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="layout" class="mr-2 h-5 w-5" />
                    Home Page Hero
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="hero_headline">Headline</Label>
                        <Input id="hero_headline" v-model="form.hero_headline" />
                        <InputError :message="errors.hero_headline" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="hero_subtext">Subtext</Label>
                        <textarea id="hero_subtext" v-model="form.hero_subtext" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                        <InputError :message="errors.hero_subtext" />
                    </div>
                    <div class="space-y-2">
                        <Label for="hero_cta_primary_text">Primary button text</Label>
                        <Input id="hero_cta_primary_text" v-model="form.hero_cta_primary_text" />
                    </div>
                    <div class="space-y-2">
                        <Label for="hero_cta_primary_url">Primary button URL</Label>
                        <Input id="hero_cta_primary_url" v-model="form.hero_cta_primary_url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="hero_cta_secondary_text">Secondary button text</Label>
                        <Input id="hero_cta_secondary_text" v-model="form.hero_cta_secondary_text" />
                    </div>
                    <div class="space-y-2">
                        <Label for="hero_cta_secondary_url">Secondary button URL</Label>
                        <Input id="hero_cta_secondary_url" v-model="form.hero_cta_secondary_url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="hero_illustration_seed">Hero illustration seed</Label>
                        <Input id="hero_illustration_seed" v-model="form.hero_illustration_seed" />
                        <p class="text-xs text-muted-foreground">Any short text — used to generate a consistent placeholder illustration.</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Home Features -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="grid" class="mr-2 h-5 w-5" />
                    Home Page Features
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="mb-3 text-xs text-muted-foreground">
                    Valid icon values: UserGroupIcon, AcademicCapIcon, BeakerIcon, TrophyIcon, ShieldCheckIcon, HeartIcon.
                </p>
                <RepeaterList
                    v-model="form.home_features"
                    add-label="Add feature"
                    empty-text="No features yet."
                    :make-row="() => ({ icon: '', title: '', description: '', seed: '' })"
                >
                    <template #default="{ row }">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Title *</Label>
                                <Input v-model="row.title" />
                            </div>
                            <div class="space-y-1">
                                <Label>Icon</Label>
                                <Input v-model="row.icon" placeholder="UserGroupIcon" />
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <Label>Description</Label>
                                <textarea v-model="row.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                            </div>
                            <div class="space-y-1">
                                <Label>Image seed</Label>
                                <Input v-model="row.seed" placeholder="e.g. small-classes" />
                            </div>
                        </div>
                    </template>
                </RepeaterList>
            </CardContent>
        </Card>

        <!-- About: Mission/Vision/Values -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="flag" class="mr-2 h-5 w-5" />
                    About: Mission, Vision &amp; Values
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="mission_statement">Mission statement</Label>
                    <textarea id="mission_statement" v-model="form.mission_statement" rows="3" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                </div>
                <div class="space-y-2">
                    <Label for="vision_statement">Vision statement</Label>
                    <textarea id="vision_statement" v-model="form.vision_statement" rows="3" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                </div>
                <div>
                    <Label class="mb-2 block">Values</Label>
                    <p class="mb-3 text-xs text-muted-foreground">Icon is stored but not yet rendered visually on the public page — free text is fine.</p>
                    <RepeaterList
                        v-model="form.values"
                        add-label="Add value"
                        empty-text="No values yet."
                        :make-row="() => ({ icon: '', title: '', description: '' })"
                    >
                        <template #default="{ row }">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label>Title *</Label>
                                    <Input v-model="row.title" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Icon</Label>
                                    <Input v-model="row.icon" />
                                </div>
                                <div class="space-y-1 md:col-span-2">
                                    <Label>Description</Label>
                                    <textarea v-model="row.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
            </CardContent>
        </Card>

        <!-- About: Leadership, Stats, History -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="users" class="mr-2 h-5 w-5" />
                    About: Leadership Team
                </CardTitle>
            </CardHeader>
            <CardContent>
                <RepeaterList
                    v-model="form.leadership_team"
                    add-label="Add leader"
                    empty-text="No leadership entries yet."
                    :make-row="() => ({ name: '', role: '', bio: '', photo_url: '' })"
                >
                    <template #default="{ row }">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Name *</Label>
                                <Input v-model="row.name" />
                            </div>
                            <div class="space-y-1">
                                <Label>Role</Label>
                                <Input v-model="row.role" />
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <Label>Bio</Label>
                                <textarea v-model="row.bio" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <Label>Photo URL</Label>
                                <Input v-model="row.photo_url" />
                            </div>
                        </div>
                    </template>
                </RepeaterList>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="bar-chart" class="mr-2 h-5 w-5" />
                    About: Stats
                </CardTitle>
            </CardHeader>
            <CardContent>
                <RepeaterList
                    v-model="form.stats"
                    add-label="Add stat"
                    empty-text="No stats yet."
                    :make-row="() => ({ label: '', value: '', suffix: '' })"
                >
                    <template #default="{ row }">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1">
                                <Label>Label *</Label>
                                <Input v-model="row.label" />
                            </div>
                            <div class="space-y-1">
                                <Label>Value</Label>
                                <Input v-model="row.value" placeholder="e.g. 1200" />
                            </div>
                            <div class="space-y-1">
                                <Label>Suffix</Label>
                                <Input v-model="row.suffix" placeholder="e.g. +" />
                            </div>
                        </div>
                    </template>
                </RepeaterList>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="history" class="mr-2 h-5 w-5" />
                    About: History Timeline
                </CardTitle>
            </CardHeader>
            <CardContent>
                <RepeaterList
                    v-model="form.history_timeline"
                    add-label="Add milestone"
                    empty-text="No timeline entries yet."
                    :make-row="() => ({ year: '', title: '', description: '' })"
                >
                    <template #default="{ row }">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="space-y-1">
                                <Label>Year *</Label>
                                <Input v-model="row.year" />
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <Label>Title</Label>
                                <Input v-model="row.title" />
                            </div>
                            <div class="space-y-1 md:col-span-3">
                                <Label>Description</Label>
                                <textarea v-model="row.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                            </div>
                        </div>
                    </template>
                </RepeaterList>
            </CardContent>
        </Card>

        <!-- Contact -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="mail" class="mr-2 h-5 w-5" />
                    Contact Page
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label for="contact_address">Address</Label>
                        <textarea id="contact_address" v-model="form.contact_address" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_phone">Phone</Label>
                        <Input id="contact_phone" v-model="form.contact_phone" />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_email">Email</Label>
                        <Input id="contact_email" type="email" v-model="form.contact_email" :class="{ 'border-destructive': errors.contact_email }" />
                        <InputError :message="errors.contact_email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_hours">Business hours</Label>
                        <Input id="contact_hours" v-model="form.contact_hours" placeholder="Mon-Fri 8am-3pm" />
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <Label for="map_embed_url">Map embed URL</Label>
                        <Input id="map_embed_url" v-model="form.map_embed_url" />
                    </div>
                </div>

                <div>
                    <Label class="mb-2 block">Social links</Label>
                    <RepeaterList
                        v-model="form.social_links"
                        add-label="Add social link"
                        empty-text="No social links yet."
                        :make-row="() => ({ platform: '', url: '' })"
                    >
                        <template #default="{ row }">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <Label>Platform *</Label>
                                    <Input v-model="row.platform" placeholder="facebook" />
                                </div>
                                <div class="space-y-1">
                                    <Label>URL</Label>
                                    <Input v-model="row.url" />
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
            </CardContent>
        </Card>

        <!-- Academics -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="book-open" class="mr-2 h-5 w-5" />
                    Academics Page
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="academics_hero_headline">Hero headline</Label>
                    <Input id="academics_hero_headline" v-model="form.academics_hero_headline" />
                </div>
                <div class="space-y-2">
                    <Label for="academics_hero_subtext">Hero subtext</Label>
                    <textarea id="academics_hero_subtext" v-model="form.academics_hero_subtext" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                </div>
                <div>
                    <Label class="mb-2 block">Programs</Label>
                    <RepeaterList
                        v-model="form.academics_programs"
                        add-label="Add program"
                        empty-text="No programs yet."
                        :make-row="() => ({ title: '', description: '' })"
                    >
                        <template #default="{ row }">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <Label>Title *</Label>
                                    <Input v-model="row.title" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Description</Label>
                                    <textarea v-model="row.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
                <div>
                    <Label class="mb-2 block">FAQ</Label>
                    <RepeaterList
                        v-model="form.academics_faq"
                        add-label="Add FAQ"
                        empty-text="No FAQ entries yet."
                        :make-row="() => ({ question: '', answer: '' })"
                    >
                        <template #default="{ row }">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <Label>Question *</Label>
                                    <Input v-model="row.question" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Answer</Label>
                                    <textarea v-model="row.answer" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
            </CardContent>
        </Card>

        <!-- Admissions -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <Icon icon="clipboard-list" class="mr-2 h-5 w-5" />
                    Admissions Page
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="admissions_hero_headline">Hero headline</Label>
                    <Input id="admissions_hero_headline" v-model="form.admissions_hero_headline" />
                </div>
                <div class="space-y-2">
                    <Label for="admissions_hero_subtext">Hero subtext</Label>
                    <textarea id="admissions_hero_subtext" v-model="form.admissions_hero_subtext" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                </div>
                <div>
                    <Label class="mb-2 block">Admission steps</Label>
                    <RepeaterList
                        v-model="form.admission_steps"
                        add-label="Add step"
                        empty-text="No steps yet."
                        :make-row="() => ({ title: '', description: '' })"
                    >
                        <template #default="{ row }">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <Label>Title *</Label>
                                    <Input v-model="row.title" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Description</Label>
                                    <textarea v-model="row.description" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
                <div>
                    <Label class="mb-2 block">FAQ</Label>
                    <RepeaterList
                        v-model="form.admissions_faq"
                        add-label="Add FAQ"
                        empty-text="No FAQ entries yet."
                        :make-row="() => ({ question: '', answer: '' })"
                    >
                        <template #default="{ row }">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <Label>Question *</Label>
                                    <Input v-model="row.question" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Answer</Label>
                                    <textarea v-model="row.answer" rows="2" class="w-full rounded-md border border-border px-3 py-2 shadow-sm focus:border-primary focus:ring-primary focus:outline-none"></textarea>
                                </div>
                            </div>
                        </template>
                    </RepeaterList>
                </div>
            </CardContent>
        </Card>

        <div class="flex flex-wrap justify-end gap-2">
            <Button type="submit" :disabled="processing">
                <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                Save Changes
            </Button>
        </div>
    </form>
</template>
