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
import { FolderTree, Plus, Users, UserCheck, XCircle, Edit, Trash2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    departments: Array<{
        id: number;
        name: string;
        employees_count: number;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Departments',
        href: '/company/departments',
    },
];

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

// Delete department
const deleteDepartment = (id: number) => {
    if (confirm('Are you sure you want to delete this department?')) {
        router.delete(`/company/departments/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};
</script>

<template>
    <Head title="Departments" />

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
                                <FolderTree class="h-5 w-5" />
                                Departments
                            </CardTitle>
                            <CardDescription>
                                Manage your company departments
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/company/departments/create">
                                <Plus class="h-4 w-4" />
                                Add New Department
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
                                        Department Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Employees Count
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
                                    v-if="departments.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No departments found
                                    </td>
                                </tr>
                                <tr
                                    v-for="department in departments"
                                    :key="department.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ department.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            {{ department.employees_count }} employee{{ department.employees_count !== 1 ? 's' : '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ department.created_at }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/company/departments/${department.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteDepartment(department.id)"
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

