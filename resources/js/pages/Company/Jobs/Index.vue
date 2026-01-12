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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Briefcase, Plus, Users, UserCheck, XCircle, Edit, Trash2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    jobs: Array<{
        id: number;
        title: string;
        description: string;
        skills: string[];
        candidates_count: number;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Job Applications',
        href: '/company/jobs',
    },
];

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

// Delete job
const deleteJob = (id: number, event: Event) => {
    event.stopPropagation();
    if (confirm('Are you sure you want to delete this job? All associated candidates will also be deleted.')) {
        router.delete(`/company/jobs/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};
</script>

<template>
    <Head title="Attenda - Job Applications | Recruitment & Hiring">
        <meta name="description" content="إدارة الوظائف والتوظيف في Attenda. نشر الوظائف، استقبال الطلبات، واختيار المرشحين المناسبين." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success Message -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <UserCheck class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>

            <!-- Error Message -->
            <Alert
                v-if="flash?.error"
                variant="destructive"
            >
                <XCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Briefcase class="h-5 w-5" />
                                Job Applications
                            </CardTitle>
                            <CardDescription>
                                Manage job postings and applications
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/company/jobs/create">
                                <Plus class="h-4 w-4 mr-2" />
                                Create Job
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Job Title
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Description
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Required Skills
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Candidates
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Created At
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="jobs.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No jobs found. Create your first job posting.
                                    </td>
                                </tr>
                                <tr
                                    v-for="job in jobs"
                                    :key="job.id"
                                    class="border-b hover:bg-muted/50 cursor-pointer"
                                    @click="$inertia.visit(`/company/jobs/${job.id}`)"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ job.title }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground max-w-md">
                                        <p class="truncate">{{ job.description }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="skill in job.skills"
                                                :key="skill"
                                                class="px-2 py-1 bg-primary/10 text-primary rounded-md text-xs"
                                            >
                                                {{ skill }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            {{ job.candidates_count }} candidate{{ job.candidates_count !== 1 ? 's' : '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ job.created_at }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                @click.stop
                                            >
                                                <Link :href="`/company/jobs/${job.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteJob(job.id, $event)"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

