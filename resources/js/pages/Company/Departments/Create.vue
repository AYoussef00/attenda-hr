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
import { FolderTree, ArrowLeft } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Departments',
        href: '/company/departments',
    },
    {
        title: 'Add New Department',
        href: '/company/departments/create',
    },
];

const form = useForm({
    name: '',
});

const submit = () => {
    form.post('/company/departments', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Attenda - Add New Department | Create Department">
        <meta name="description" content="إضافة قسم جديد في Attenda. إنشاء قسم جديد في الشركة وتنظيم هيكل المؤسسة بسهولة." />
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
                                <FolderTree class="h-5 w-5" />
                                Add New Department
                            </CardTitle>
                            <CardDescription>
                                Create a new department for your company
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/departments">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Departments
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Department Name -->
                        <div class="grid gap-2">
                            <Label for="name">
                                Department Name <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                name="name"
                                required
                                placeholder="Enter department name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                            <p class="text-sm text-muted-foreground">
                                Enter a unique name for the department (e.g., Human Resources, IT, Sales)
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/departments">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Department</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

