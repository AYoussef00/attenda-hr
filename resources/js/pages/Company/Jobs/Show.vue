<script setup lang="ts">
import CompanyLayout from '@/layouts/company/CompanyLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import {
    Briefcase,
    ArrowLeft,
    Users,
    Calendar,
    FileText,
    Mail,
    Phone,
    TrendingUp,
    Upload,
    X,
    Download,
    UserCheck,
    XCircle,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Check,
    Clock,
    X as XIcon,
    Settings,
} from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    job: {
        id: number;
        title: string;
        description: string;
        skills: string[];
        auto_reject_min: number | null;
        auto_reject_max: number | null;
        auto_accept_min: number | null;
        auto_accept_max: number | null;
        candidates_count: number;
        created_at: string;
        updated_at: string;
        accepted_candidates: Array<{
            id: number;
            name: string;
            email: string;
            phone: string;
            title: string;
            skills: string[];
            experience_years: number;
            match_percentage: number;
            resume_path: string;
            status: string;
            created_at: string;
            strengths: string[];
            weaknesses: string[];
        }>;
        pending_candidates: Array<{
            id: number;
            name: string;
            email: string;
            phone: string;
            title: string;
            skills: string[];
            experience_years: number;
            match_percentage: number;
            resume_path: string;
            status: string;
            created_at: string;
            strengths: string[];
            weaknesses: string[];
        }>;
        rejected_candidates: Array<{
            id: number;
            name: string;
            email: string;
            phone: string;
            title: string;
            skills: string[];
            experience_years: number;
            match_percentage: number;
            resume_path: string;
            status: string;
            created_at: string;
            strengths: string[];
            weaknesses: string[];
        }>;
    };
}>();

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const uploadDialogOpen = ref(false);
const settingsDialogOpen = ref(false);
const selectedFiles = ref<File[]>([]);

const form = useForm({
    resumes: [] as File[],
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        selectedFiles.value = Array.from(target.files);
        form.resumes = selectedFiles.value;
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    form.resumes = selectedFiles.value;
};

const submitUpload = () => {
    if (selectedFiles.value.length === 0) {
        return;
    }

    // Prepare form data
    const formData = new FormData();
    
    selectedFiles.value.forEach((file) => {
        formData.append('resumes[]', file);
    });

    router.post(`/company/jobs/${props.job.id}/upload-candidates`, formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            uploadDialogOpen.value = false;
            // Reset form
            selectedFiles.value = [];
            form.resumes = [];
        },
    });
};

const downloadResume = (resumePath: string) => {
    window.open(`/storage/${resumePath}`, '_blank');
};

const deleteCandidate = (candidateId: number, candidateName: string) => {
    if (confirm(`Are you sure you want to delete ${candidateName}? This action cannot be undone.`)) {
        router.delete(`/company/jobs/${props.job.id}/candidates/${candidateId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

const updateCandidateStatus = (candidateId: number, status: string) => {
    router.put(`/company/jobs/${props.job.id}/candidates/${candidateId}/status`, {
        status: status,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success message will be shown via flash
        },
    });
};

const settingsForm = useForm({
    auto_reject_min: props.job.auto_reject_min,
    auto_reject_max: props.job.auto_reject_max,
    auto_accept_min: props.job.auto_accept_min,
    auto_accept_max: props.job.auto_accept_max,
});

// Watch for dialog open to update form values
watch(settingsDialogOpen, (isOpen) => {
    if (isOpen) {
        settingsForm.auto_reject_min = props.job.auto_reject_min;
        settingsForm.auto_reject_max = props.job.auto_reject_max;
        settingsForm.auto_accept_min = props.job.auto_accept_min;
        settingsForm.auto_accept_max = props.job.auto_accept_max;
    }
});

const updateSettings = () => {
    settingsForm.put(`/company/jobs/${props.job.id}/settings`, {
        preserveScroll: true,
        onSuccess: () => {
            settingsDialogOpen.value = false;
        },
    });
};


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
        title: props.job.title,
        href: `/company/jobs/${props.job.id}`,
    },
];

const getMatchColor = (percentage: number) => {
    if (percentage >= 80) return 'text-emerald-600 bg-emerald-50';
    if (percentage >= 60) return 'text-blue-600 bg-blue-50';
    if (percentage >= 40) return 'text-amber-600 bg-amber-50';
    return 'text-red-600 bg-red-50';
};
</script>

<template>
    <Head :title="`${job.title} - Job Details`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Success/Error Messages -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <UserCheck class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>

            <Alert
                v-if="flash?.error"
                variant="destructive"
            >
                <XCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button
                        as-child
                        variant="ghost"
                        size="icon"
                        class="rounded-full"
                    >
                        <Link href="/company/jobs">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                            Job Details
                        </h1>
                        <p class="text-sm text-slate-500">
                            View detailed information about this job posting
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Dialog v-model:open="settingsDialogOpen">
                        <DialogTrigger as-child>
                            <Button variant="outline">
                                <Settings class="h-4 w-4 mr-2" />
                                Settings
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Auto Status Settings</DialogTitle>
                                <DialogDescription>
                                    Configure automatic acceptance and rejection based on match percentage. Leave empty to disable auto status.
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="updateSettings" class="space-y-6 py-4">
                                <!-- Auto Reject Range -->
                                <div class="grid gap-4">
                                    <div>
                                        <Label class="text-base font-medium">Auto Reject Range</Label>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            Candidates with match percentage in this range will be automatically rejected
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-2">
                                            <Label for="settings_auto_reject_min">Minimum %</Label>
                                            <Input
                                                id="settings_auto_reject_min"
                                                v-model.number="settingsForm.auto_reject_min"
                                                type="number"
                                                min="0"
                                                max="100"
                                                placeholder="0"
                                                class="w-full"
                                            />
                                            <InputError :message="settingsForm.errors.auto_reject_min" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="settings_auto_reject_max">Maximum %</Label>
                                            <Input
                                                id="settings_auto_reject_max"
                                                v-model.number="settingsForm.auto_reject_max"
                                                type="number"
                                                min="0"
                                                max="100"
                                                placeholder="100"
                                                class="w-full"
                                            />
                                            <InputError :message="settingsForm.errors.auto_reject_max" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Auto Accept Range -->
                                <div class="grid gap-4">
                                    <div>
                                        <Label class="text-base font-medium">Auto Accept Range</Label>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            Candidates with match percentage in this range will be automatically accepted
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-2">
                                            <Label for="settings_auto_accept_min">Minimum %</Label>
                                            <Input
                                                id="settings_auto_accept_min"
                                                v-model.number="settingsForm.auto_accept_min"
                                                type="number"
                                                min="0"
                                                max="100"
                                                placeholder="0"
                                                class="w-full"
                                            />
                                            <InputError :message="settingsForm.errors.auto_accept_min" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="settings_auto_accept_max">Maximum %</Label>
                                            <Input
                                                id="settings_auto_accept_max"
                                                v-model.number="settingsForm.auto_accept_max"
                                                type="number"
                                                min="0"
                                                max="100"
                                                placeholder="100"
                                                class="w-full"
                                            />
                                            <InputError :message="settingsForm.errors.auto_accept_max" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-4 pt-4 border-t">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="settingsDialogOpen = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="settingsForm.processing"
                                    >
                                        <span v-if="settingsForm.processing">Saving...</span>
                                        <span v-else>Save Settings</span>
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Dialog v-model:open="uploadDialogOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <Upload class="h-4 w-4 mr-2" />
                                Upload CV Files
                            </Button>
                        </DialogTrigger>
                    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Upload Candidate CV Files</DialogTitle>
                            <DialogDescription>
                                Upload CV files. Candidate information will be extracted automatically from the files.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-4 py-4">
                            <div class="grid gap-2">
                                <Label for="resumes">
                                    CV Files <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="resumes"
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    multiple
                                    @change="handleFileChange"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Accepted formats: PDF, DOC, DOCX (Max 10MB per file)
                                </p>
                            </div>

                            <!-- Selected Files List -->
                            <div v-if="selectedFiles.length > 0" class="space-y-2">
                                <Label>Selected Files ({{ selectedFiles.length }})</Label>
                                <div class="space-y-2 max-h-60 overflow-y-auto border rounded-lg p-3">
                                    <div
                                        v-for="(file, index) in selectedFiles"
                                        :key="index"
                                        class="flex items-center justify-between p-2 bg-slate-50 rounded-md"
                                    >
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <FileText class="h-4 w-4 text-slate-500 shrink-0" />
                                            <span class="text-sm text-slate-700 truncate">{{ file.name }}</span>
                                            <span class="text-xs text-slate-500 shrink-0">
                                                ({{ (file.size / 1024 / 1024).toFixed(2) }} MB)
                                            </span>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="removeFile(index)"
                                            class="h-6 w-6 shrink-0"
                                        >
                                            <X class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                @click="uploadDialogOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                @click="submitUpload"
                                :disabled="form.processing || selectedFiles.length === 0"
                            >
                                <span v-if="form.processing">Processing...</span>
                                <span v-else>Upload & Extract Data</span>
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Job Details Card -->
                <Card class="border-0 bg-white shadow-sm rounded-2xl md:col-span-1">
                    <CardHeader>
                        <div class="flex flex-col items-center gap-4">
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full bg-[#1e3b3b] text-2xl font-semibold text-emerald-50"
                            >
                                <Briefcase class="h-12 w-12" />
                            </div>
                            <div class="text-center">
                                <CardTitle class="text-lg">{{ job.title }}</CardTitle>
                                <CardDescription class="mt-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <Users class="h-4 w-4" />
                                        {{ job.candidates_count }} candidate{{ job.candidates_count !== 1 ? 's' : '' }}
                                    </div>
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Calendar class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Created At</p>
                                    <p class="font-medium text-slate-900">
                                        {{ new Date(job.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        }) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Users class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Total Candidates</p>
                                    <p class="font-medium text-slate-900">
                                        {{ job.candidates_count }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Job Description & Skills -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Description Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <FileText class="h-4 w-4 text-[#1e3b3b]" />
                                Job Description
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">
                                {{ job.description }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Required Skills Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <TrendingUp class="h-4 w-4 text-[#1e3b3b]" />
                                Required Skills
                            </CardTitle>
                            <CardDescription>
                                Skills required for this position
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="skill in job.skills"
                                    :key="skill"
                                    class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>


                    <!-- Accepted Candidates Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                Accepted Candidates
                            </CardTitle>
                            <CardDescription>
                                Accepted candidates sorted by match percentage (highest to lowest)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="job.accepted_candidates.length === 0"
                                class="py-8 text-center"
                            >
                                <CheckCircle2 class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                <p class="text-sm text-slate-400">
                                    No accepted candidates yet.
                                </p>
                            </div>
                            <div
                                v-else
                                class="space-y-4"
                            >
                                <div
                                    v-for="candidate in job.accepted_candidates"
                                    :key="candidate.id"
                                    class="flex items-start justify-between rounded-lg border border-emerald-200 bg-emerald-50/30 p-4 hover:bg-emerald-50/50 transition-colors"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="font-semibold text-slate-900">
                                                {{ candidate.name }}
                                            </h3>
                                            <Badge
                                                :class="getMatchColor(candidate.match_percentage)"
                                                class="font-medium"
                                            >
                                                {{ candidate.match_percentage }}% Match
                                            </Badge>
                                            <Badge variant="default" class="bg-emerald-600 text-white">
                                                Accepted
                                            </Badge>
                                        </div>
                                        <div class="space-y-1 text-sm text-slate-600">
                                            <div v-if="candidate.email" class="flex items-center gap-2">
                                                <Mail class="h-3 w-3" />
                                                {{ candidate.email }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Phone class="h-3 w-3" />
                                                {{ candidate.phone }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Briefcase class="h-3 w-3" />
                                                {{ candidate.title }}
                                            </div>
                                            <div v-if="candidate.experience_years > 0" class="flex items-center gap-2">
                                                <Calendar class="h-3 w-3" />
                                                {{ candidate.experience_years }} years of experience
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="downloadResume(candidate.resume_path)"
                                            >
                                                <Download class="h-3 w-3 mr-2" />
                                                Download CV
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="updateCandidateStatus(candidate.id, 'pending')"
                                            >
                                                <Clock class="h-3 w-3 mr-2" />
                                                Move to Pending
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="deleteCandidate(candidate.id, candidate.name)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-3 w-3 mr-2" />
                                                Delete
                                            </Button>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span
                                                v-for="skill in candidate.skills"
                                                :key="skill"
                                                class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs"
                                            >
                                                {{ skill }}
                                            </span>
                                        </div>

                                        <!-- Strengths -->
                                        <div v-if="candidate.strengths && candidate.strengths.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-emerald-700 flex items-center gap-2">
                                                <CheckCircle2 class="h-4 w-4" />
                                                Strengths
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(strength, index) in candidate.strengths"
                                                    :key="index"
                                                    class="text-sm text-emerald-700 bg-emerald-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <CheckCircle2 class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ strength }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Weaknesses -->
                                        <div v-if="candidate.weaknesses && candidate.weaknesses.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-amber-700 flex items-center gap-2">
                                                <AlertCircle class="h-4 w-4" />
                                                Areas for Improvement
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(weakness, index) in candidate.weaknesses"
                                                    :key="index"
                                                    class="text-sm text-amber-700 bg-amber-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <AlertCircle class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ weakness }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <p class="text-xs text-slate-400 mt-2">
                                            Applied on {{ new Date(candidate.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pending Candidates Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <Clock class="h-4 w-4 text-amber-600" />
                                Pending Candidates
                            </CardTitle>
                            <CardDescription>
                                Candidates awaiting review
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="job.pending_candidates.length === 0"
                                class="py-8 text-center"
                            >
                                <Clock class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                <p class="text-sm text-slate-400">
                                    No pending candidates.
                                </p>
                            </div>
                            <div
                                v-else
                                class="space-y-4"
                            >
                                <div
                                    v-for="candidate in job.pending_candidates"
                                    :key="candidate.id"
                                    class="flex items-start justify-between rounded-lg border border-amber-200 bg-amber-50/30 p-4 hover:bg-amber-50/50 transition-colors"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="font-semibold text-slate-900">
                                                {{ candidate.name }}
                                            </h3>
                                            <Badge
                                                :class="getMatchColor(candidate.match_percentage)"
                                                class="font-medium"
                                            >
                                                {{ candidate.match_percentage }}% Match
                                            </Badge>
                                            <Badge variant="secondary" class="bg-amber-500 text-white">
                                                Pending
                                            </Badge>
                                        </div>
                                        <div class="space-y-1 text-sm text-slate-600">
                                            <div v-if="candidate.email" class="flex items-center gap-2">
                                                <Mail class="h-3 w-3" />
                                                {{ candidate.email }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Phone class="h-3 w-3" />
                                                {{ candidate.phone }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Briefcase class="h-3 w-3" />
                                                {{ candidate.title }}
                                            </div>
                                            <div v-if="candidate.experience_years > 0" class="flex items-center gap-2">
                                                <Calendar class="h-3 w-3" />
                                                {{ candidate.experience_years }} years of experience
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2 flex-wrap">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="downloadResume(candidate.resume_path)"
                                            >
                                                <Download class="h-3 w-3 mr-2" />
                                                Download CV
                                            </Button>
                                            <Button
                                                size="sm"
                                                @click="updateCandidateStatus(candidate.id, 'accepted')"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white"
                                            >
                                                <Check class="h-3 w-3 mr-2" />
                                                Accept
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="updateCandidateStatus(candidate.id, 'rejected')"
                                                class="text-red-600 hover:text-red-700 border-red-300"
                                            >
                                                <XIcon class="h-3 w-3 mr-2" />
                                                Reject
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="deleteCandidate(candidate.id, candidate.name)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-3 w-3 mr-2" />
                                                Delete
                                            </Button>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span
                                                v-for="skill in candidate.skills"
                                                :key="skill"
                                                class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs"
                                            >
                                                {{ skill }}
                                            </span>
                                        </div>

                                        <!-- Strengths -->
                                        <div v-if="candidate.strengths && candidate.strengths.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-emerald-700 flex items-center gap-2">
                                                <CheckCircle2 class="h-4 w-4" />
                                                Strengths
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(strength, index) in candidate.strengths"
                                                    :key="index"
                                                    class="text-sm text-emerald-700 bg-emerald-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <CheckCircle2 class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ strength }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Weaknesses -->
                                        <div v-if="candidate.weaknesses && candidate.weaknesses.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-amber-700 flex items-center gap-2">
                                                <AlertCircle class="h-4 w-4" />
                                                Areas for Improvement
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(weakness, index) in candidate.weaknesses"
                                                    :key="index"
                                                    class="text-sm text-amber-700 bg-amber-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <AlertCircle class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ weakness }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <p class="text-xs text-slate-400 mt-2">
                                            Applied on {{ new Date(candidate.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Rejected Candidates Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <XCircle class="h-4 w-4 text-red-600" />
                                Rejected Candidates
                            </CardTitle>
                            <CardDescription>
                                Rejected candidates for this position
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="job.rejected_candidates.length === 0"
                                class="py-8 text-center"
                            >
                                <XCircle class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                <p class="text-sm text-slate-400">
                                    No rejected candidates.
                                </p>
                            </div>
                            <div
                                v-else
                                class="space-y-4"
                            >
                                <div
                                    v-for="candidate in job.rejected_candidates"
                                    :key="candidate.id"
                                    class="flex items-start justify-between rounded-lg border border-red-200 bg-red-50/30 p-4 hover:bg-red-50/50 transition-colors"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="font-semibold text-slate-900">
                                                {{ candidate.name }}
                                            </h3>
                                            <Badge
                                                :class="getMatchColor(candidate.match_percentage)"
                                                class="font-medium"
                                            >
                                                {{ candidate.match_percentage }}% Match
                                            </Badge>
                                            <Badge variant="destructive" class="bg-red-600 text-white">
                                                Rejected
                                            </Badge>
                                        </div>
                                        <div class="space-y-1 text-sm text-slate-600">
                                            <div v-if="candidate.email" class="flex items-center gap-2">
                                                <Mail class="h-3 w-3" />
                                                {{ candidate.email }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Phone class="h-3 w-3" />
                                                {{ candidate.phone }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Briefcase class="h-3 w-3" />
                                                {{ candidate.title }}
                                            </div>
                                            <div v-if="candidate.experience_years > 0" class="flex items-center gap-2">
                                                <Calendar class="h-3 w-3" />
                                                {{ candidate.experience_years }} years of experience
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="downloadResume(candidate.resume_path)"
                                            >
                                                <Download class="h-3 w-3 mr-2" />
                                                Download CV
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="updateCandidateStatus(candidate.id, 'pending')"
                                            >
                                                <Clock class="h-3 w-3 mr-2" />
                                                Move to Pending
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="deleteCandidate(candidate.id, candidate.name)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-3 w-3 mr-2" />
                                                Delete
                                            </Button>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <span
                                                v-for="skill in candidate.skills"
                                                :key="skill"
                                                class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs"
                                            >
                                                {{ skill }}
                                            </span>
                                        </div>

                                        <!-- Strengths -->
                                        <div v-if="candidate.strengths && candidate.strengths.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-emerald-700 flex items-center gap-2">
                                                <CheckCircle2 class="h-4 w-4" />
                                                Strengths
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(strength, index) in candidate.strengths"
                                                    :key="index"
                                                    class="text-sm text-emerald-700 bg-emerald-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <CheckCircle2 class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ strength }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Weaknesses -->
                                        <div v-if="candidate.weaknesses && candidate.weaknesses.length > 0" class="mt-4 space-y-2">
                                            <h4 class="text-sm font-semibold text-amber-700 flex items-center gap-2">
                                                <AlertCircle class="h-4 w-4" />
                                                Areas for Improvement
                                            </h4>
                                            <ul class="space-y-1">
                                                <li
                                                    v-for="(weakness, index) in candidate.weaknesses"
                                                    :key="index"
                                                    class="text-sm text-amber-700 bg-amber-50 rounded-md p-2 flex items-start gap-2"
                                                >
                                                    <AlertCircle class="h-3 w-3 mt-0.5 shrink-0" />
                                                    <span>{{ weakness }}</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <p class="text-xs text-slate-400 mt-2">
                                            Applied on {{ new Date(candidate.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>

