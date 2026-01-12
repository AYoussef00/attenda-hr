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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Users, Plus, Eye, Edit, Trash2, Mail, Phone, UserCheck, XCircle, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    employees: Array<{
        id: number;
        name: string;
        email: string;
        phone: string;
        employee_code: string;
        position: string | null;
        department: string;
        shift: string;
        hire_date: string | null;
        contract_type: string | null;
        status: string;
    }>;
    subscription_info?: {
        current_employees: number;
        max_employees: number | null;
        plan_name: string | null;
        can_add_employee: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Employees',
        href: '/company/employees',
    },
];

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'inactive':
            return 'secondary';
        case 'terminated':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

// Delete employee
const deleteEmployee = (id: number) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(`/company/employees/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

const remainingEmployees = computed(() => {
    if (!props.subscription_info?.max_employees) {
        return null;
    }
    return props.subscription_info.max_employees - props.subscription_info.current_employees;
});
</script>

<template>
    <Head title="Attenda - Employees Management | Employee Directory">
        <meta name="description" content="إدارة الموظفين في Attenda. عرض وإدارة جميع موظفي الشركة، معلوماتهم الشخصية، الوظائف، والأقسام بسهولة." />
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

            <!-- Subscription Info Alert -->
            <Alert
                v-if="subscription_info && subscription_info.max_employees !== null"
                :variant="subscription_info.can_add_employee ? 'default' : 'destructive'"
                :class="subscription_info.can_add_employee 
                    ? 'border-blue-500 bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100' 
                    : ''"
            >
                <AlertCircle
                    v-if="!subscription_info.can_add_employee"
                    class="h-4 w-4"
                />
                <CheckCircle2
                    v-else
                    class="h-4 w-4"
                />
                <AlertTitle>
                    {{ subscription_info.can_add_employee ? 'Subscription Limit' : 'Employee Limit Reached' }}
                </AlertTitle>
                <AlertDescription>
                    <div v-if="!subscription_info.can_add_employee" class="mt-2">
                        You have reached the maximum number of employees ({{ subscription_info.max_employees }}) allowed by your {{ subscription_info.plan_name || 'subscription' }} plan. Please upgrade your plan to add more employees.
                    </div>
                    <div v-else class="flex flex-wrap items-center gap-4 mt-2">
                        <span>
                            Current Employees: <strong>{{ subscription_info.current_employees }}</strong> / {{ subscription_info.max_employees }}
                        </span>
                        <span v-if="remainingEmployees !== null && remainingEmployees > 0">
                            Remaining: <strong>{{ remainingEmployees }}</strong> employee{{ remainingEmployees === 1 ? '' : 's' }}
                        </span>
                        <span v-if="subscription_info.plan_name">
                            Plan: <strong>{{ subscription_info.plan_name }}</strong>
                        </span>
                    </div>
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Employees
                            </CardTitle>
                            <CardDescription>
                                Manage your company employees
                            </CardDescription>
                        </div>
                        <Button
                            as-child
                            :disabled="subscription_info && !subscription_info.can_add_employee"
                        >
                            <Link href="/company/employees/create">
                                <Plus class="h-4 w-4" />
                                Add New Employee
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="employees.length === 0"
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        No employees found
                    </div>

                    <div
                        v-else
                        class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Card
                            v-for="employee in employees"
                            :key="employee.id"
                            class="flex flex-col justify-between rounded-2xl border bg-white/90 p-5 shadow-sm transition hover:shadow-md"
                        >
                            <div class="space-y-4">
                                <!-- Top row: avatar + menu -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 text-lg font-semibold"
                                        >
                                            {{ employee.name.charAt(0) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ employee.name }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ employee.position || 'Employee' }}
                                            </div>
                                        </div>
                                    </div>
                                    <Badge
                                        :variant="getStatusVariant(employee.status)"
                                        class="rounded-full px-2 py-0.5 text-[11px]"
                                    >
                                        {{ employee.status }}
                                    </Badge>
                                </div>

                                <!-- Meta info -->
                                <div class="space-y-2 text-xs text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <Mail class="h-3 w-3" />
                                        <span class="truncate">{{ employee.email }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Phone class="h-3 w-3" />
                                        <span class="truncate">{{ employee.phone || 'No phone' }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1 pt-1">
                                        <span
                                            v-if="employee.employee_code"
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600"
                                        >
                                            #{{ employee.employee_code }}
                                        </span>
                                        <span
                                            v-if="employee.department"
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600"
                                        >
                                            {{ employee.department }}
                                        </span>
                                        <span
                                            v-if="employee.shift"
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600"
                                        >
                                            {{ employee.shift }}
                                        </span>
                                        <span
                                            v-if="employee.contract_type"
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600"
                                        >
                                            {{ employee.contract_type }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-5 flex gap-3">
                                <Button
                                    as-child
                                    class="flex-1 rounded-full bg-[#1e3b3b] text-xs font-semibold text-emerald-50 hover:bg-[#234444]"
                                >
                                    <Link :href="`/company/employees/${employee.id}`">
                                        View profile
                                    </Link>
                                </Button>
                                <Button
                                    as-child
                                    variant="outline"
                                    class="flex-1 rounded-full text-xs font-semibold"
                                >
                                    <Link :href="`/company/employees/${employee.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="rounded-full"
                                    @click="deleteEmployee(employee.id)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </Card>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

