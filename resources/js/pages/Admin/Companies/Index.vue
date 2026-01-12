<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Building2, Plus, Eye, Edit, Trash2, Mail, Phone, CheckCircle2, XCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, onMounted } from 'vue';

const props = defineProps<{
    companies: Array<{
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        employee_count: number;
        subscription_status: string;
        plan_name: string | null;
        status: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Companies',
        href: '/system/companies',
    },
];

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'inactive':
            return 'secondary';
        case 'expired':
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Delete company
const deleteCompany = (id: number) => {
    if (confirm('Are you sure you want to delete this company?')) {
        router.delete(`/system/companies/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Companies Management | All Registered Companies">
        <meta name="description" content="إدارة الشركات في Attenda. عرض جميع الشركات المسجلة، معلوماتها، حالة الاشتراك، وإدارة الحسابات." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success Message -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <CheckCircle2 class="h-4 w-4" />
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
                                <Building2 class="h-5 w-5" />
                                Companies
                            </CardTitle>
                            <CardDescription>
                                Manage all registered companies
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/system/companies/create">
                                <Plus class="h-4 w-4" />
                                Add New Company
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
                                        Company Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Email
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Phone
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Employees
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Plan
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Subscription Status
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="companies.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No companies found
                                    </td>
                                </tr>
                                <tr
                                    v-for="company in companies"
                                    :key="company.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ company.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Mail class="h-4 w-4" />
                                            {{ company.email || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Phone class="h-4 w-4" />
                                            {{ company.phone || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ company.employee_count }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ company.plan_name || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="getStatusVariant(company.subscription_status)">
                                            {{ company.subscription_status }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/system/companies/${company.id}`">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/system/companies/${company.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteCompany(company.id)"
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
    </AdminLayout>
</template>

