<script setup lang="ts">
import CompanyLayout from '@/layouts/company/CompanyLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Briefcase, ArrowLeft, X } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Job Applications',
        href: '/company/jobs',
    },
    {
        title: 'Create Job',
        href: '/company/jobs/create',
    },
];

const skillInput = ref('');
const skills = ref<string[]>([]);

const form = useForm({
    title: '',
    description: '',
    skills: [] as string[],
    auto_reject_min: null as number | null,
    auto_reject_max: null as number | null,
    auto_accept_min: null as number | null,
    auto_accept_max: null as number | null,
});

const addSkill = () => {
    const trimmedSkill = skillInput.value.trim();
    if (trimmedSkill && !skills.value.includes(trimmedSkill)) {
        skills.value.push(trimmedSkill);
        form.skills = [...skills.value];
        skillInput.value = '';
    }
};

const removeSkill = (skill: string) => {
    skills.value = skills.value.filter(s => s !== skill);
    form.skills = [...skills.value];
};

const submit = () => {
    form.post('/company/jobs', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Attenda - Create Job Posting | Post New Job Opportunity">
        <meta name="description" content="إنشاء إعلان وظيفة جديد في Attenda. نشر فرصة عمل جديدة مع تفاصيل الوظيفة والمتطلبات." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Briefcase class="h-5 w-5" />
                                Create Job
                            </CardTitle>
                            <CardDescription>
                                Create a new job posting
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/jobs">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Jobs
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Job Title -->
                        <div class="grid gap-2">
                            <Label for="title">
                                Job Title <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                name="title"
                                required
                                placeholder="Enter job title"
                                :class="{ 'border-destructive': form.errors.title }"
                            />
                            <InputError :message="form.errors.title" />
                            <p class="text-sm text-muted-foreground">
                                Enter the job title (e.g., Senior Software Engineer, Marketing Manager)
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="grid gap-2">
                            <Label for="description">
                                Description <span class="text-destructive">*</span>
                            </Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                name="description"
                                rows="6"
                                required
                                placeholder="Enter job description"
                                class="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :class="{ 'border-destructive': form.errors.description }"
                            ></textarea>
                            <InputError :message="form.errors.description" />
                            <p class="text-sm text-muted-foreground">
                                Provide a detailed description of the job role and responsibilities
                            </p>
                        </div>

                        <!-- Required Skills -->
                        <div class="grid gap-2">
                            <Label for="skills">
                                Required Skills <span class="text-destructive">*</span>
                            </Label>
                            <div class="flex gap-2">
                                <Input
                                    id="skills"
                                    v-model="skillInput"
                                    type="text"
                                    placeholder="Enter a skill and press Enter"
                                    @keydown.enter.prevent="addSkill"
                                    class="flex-1"
                                />
                                <Button
                                    type="button"
                                    @click="addSkill"
                                >
                                    Add
                                </Button>
                            </div>
                            <InputError :message="form.errors.skills" />
                            <p class="text-sm text-muted-foreground">
                                Add required skills for this position
                            </p>
                            
                            <!-- Skills Tags -->
                            <div v-if="skills.length > 0" class="flex flex-wrap gap-2 mt-2">
                                <span
                                    v-for="skill in skills"
                                    :key="skill"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-primary/10 text-primary rounded-full text-sm"
                                >
                                    {{ skill }}
                                    <button
                                        type="button"
                                        @click="removeSkill(skill)"
                                        class="ml-1 hover:text-destructive"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Auto Status Settings -->
                        <div class="grid gap-6 border-t pt-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Auto Status Settings</h3>
                                <p class="text-sm text-muted-foreground mb-4">
                                    Configure automatic acceptance and rejection based on match percentage. Leave empty to disable auto status.
                                </p>
                            </div>

                            <!-- Auto Reject Range -->
                            <div class="grid gap-4">
                                <div>
                                    <Label class="text-base font-medium">Auto Reject Range</Label>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Candidates with match percentage in this range will be automatically rejected
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="auto_reject_min">Minimum %</Label>
                                        <Input
                                            id="auto_reject_min"
                                            v-model.number="form.auto_reject_min"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="0"
                                            class="w-full"
                                        />
                                        <InputError :message="form.errors.auto_reject_min" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="auto_reject_max">Maximum %</Label>
                                        <Input
                                            id="auto_reject_max"
                                            v-model.number="form.auto_reject_max"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="100"
                                            class="w-full"
                                        />
                                        <InputError :message="form.errors.auto_reject_max" />
                                    </div>
                                </div>
                            </div>

                            <!-- Auto Accept Range -->
                            <div class="grid gap-4">
                                <div>
                                    <Label class="text-base font-medium">Auto Accept Range</Label>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Candidates with match percentage in this range will be automatically accepted
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="auto_accept_min">Minimum %</Label>
                                        <Input
                                            id="auto_accept_min"
                                            v-model.number="form.auto_accept_min"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="0"
                                            class="w-full"
                                        />
                                        <InputError :message="form.errors.auto_accept_min" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="auto_accept_max">Maximum %</Label>
                                        <Input
                                            id="auto_accept_max"
                                            v-model.number="form.auto_accept_max"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="100"
                                            class="w-full"
                                        />
                                        <InputError :message="form.errors.auto_accept_max" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/jobs">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Job</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

