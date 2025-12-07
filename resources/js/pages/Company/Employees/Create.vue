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
import { Users, ArrowLeft, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    departments: Array<{ id: number; name: string }>;
    shifts: Array<{ id: number; name: string }>;
    subscription_info: {
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
    {
        title: 'Add New Employee',
        href: '/company/employees/create',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    employee_code: '',
    position: '',
    department_id: null as number | null,
    shift_id: null as number | null,
    hire_date: '',
    contract_type: '',
    status: 'active',
});

const remainingEmployees = computed(() => {
    if (!props.subscription_info.max_employees) {
        return null;
    }
    return props.subscription_info.max_employees - props.subscription_info.current_employees;
});

const submit = () => {
    form.post('/company/employees', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Add New Employee" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Add New Employee
                            </CardTitle>
                            <CardDescription>
                                Create a new employee account
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/employees">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Employees
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Subscription Info Alert -->
                    <div v-if="subscription_info.max_employees !== null" class="mb-6">
                        <Alert
                            v-if="!subscription_info.can_add_employee"
                            variant="destructive"
                        >
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>Employee Limit Reached</AlertTitle>
                            <AlertDescription>
                                You have reached the maximum number of employees ({{ subscription_info.max_employees }}) allowed by your {{ subscription_info.plan_name || 'subscription' }} plan. Please upgrade your plan to add more employees.
                            </AlertDescription>
                        </Alert>
                        <Alert
                            v-else
                            variant="default"
                            class="border-blue-500 bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <AlertTitle>Subscription Limit</AlertTitle>
                            <AlertDescription>
                                <div class="flex flex-wrap items-center gap-4 mt-2">
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
                    </div>
                    <Alert
                        v-else
                        variant="default"
                        class="mb-6 border-yellow-500 bg-yellow-50 text-yellow-900 dark:bg-yellow-900/20 dark:text-yellow-100"
                    >
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>No Active Subscription</AlertTitle>
                        <AlertDescription>
                            You currently have {{ subscription_info.current_employees }} employee{{ subscription_info.current_employees === 1 ? '' : 's' }}. Please contact support to set up a subscription plan.
                        </AlertDescription>
                    </Alert>

                    <!-- Employee Limit Error -->
                    <Alert
                        v-if="form.errors.employee_limit"
                        variant="destructive"
                        class="mb-6"
                    >
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{{ form.errors.employee_limit }}</AlertDescription>
                    </Alert>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Personal Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Personal Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Name -->
                                <div class="grid gap-2">
                                    <Label for="name">
                                        Full Name <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        name="name"
                                        required
                                        placeholder="Enter employee name"
                                        :class="{ 'border-destructive': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="grid gap-2">
                                    <Label for="email">
                                        Email <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        name="email"
                                        required
                                        placeholder="employee@example.com"
                                        :class="{ 'border-destructive': form.errors.email }"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <!-- Phone -->
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        name="phone"
                                        placeholder="+1234567890"
                                        :class="{ 'border-destructive': form.errors.phone }"
                                    />
                                    <InputError :message="form.errors.phone" />
                                </div>

                                <!-- Employee Code -->
                                <div class="grid gap-2">
                                    <Label for="employee_code">
                                        Employee Code <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="employee_code"
                                        v-model="form.employee_code"
                                        type="text"
                                        name="employee_code"
                                        required
                                        placeholder="EMP001"
                                        :class="{ 'border-destructive': form.errors.employee_code }"
                                    />
                                    <InputError :message="form.errors.employee_code" />
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Account Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Password -->
                                <div class="grid gap-2">
                                    <Label for="password">
                                        Password <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        name="password"
                                        required
                                        placeholder="Enter password"
                                        :class="{ 'border-destructive': form.errors.password }"
                                    />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <!-- Password Confirmation -->
                                <div class="grid gap-2">
                                    <Label for="password_confirmation">
                                        Confirm Password <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        required
                                        placeholder="Confirm password"
                                        :class="{ 'border-destructive': form.errors.password_confirmation }"
                                    />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <!-- Job Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Job Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Position -->
                                <div class="grid gap-2">
                                    <Label for="position">Position</Label>
                                    <Input
                                        id="position"
                                        v-model="form.position"
                                        type="text"
                                        name="position"
                                        placeholder="e.g. Software Developer"
                                        :class="{ 'border-destructive': form.errors.position }"
                                    />
                                    <InputError :message="form.errors.position" />
                                </div>

                                <!-- Department -->
                                <div class="grid gap-2">
                                    <Label for="department_id">Department</Label>
                                    <select
                                        id="department_id"
                                        v-model="form.department_id"
                                        name="department_id"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        :class="{ 'border-destructive': form.errors.department_id }"
                                    >
                                        <option :value="null">Select Department</option>
                                        <option
                                            v-for="department in departments"
                                            :key="department.id"
                                            :value="department.id"
                                        >
                                            {{ department.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.department_id" />
                                </div>

                                <!-- Shift -->
                                <div class="grid gap-2">
                                    <Label for="shift_id">Shift</Label>
                                    <select
                                        id="shift_id"
                                        v-model="form.shift_id"
                                        name="shift_id"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        :class="{ 'border-destructive': form.errors.shift_id }"
                                    >
                                        <option :value="null">Select Shift</option>
                                        <option
                                            v-for="shift in shifts"
                                            :key="shift.id"
                                            :value="shift.id"
                                        >
                                            {{ shift.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.shift_id" />
                                </div>

                                <!-- Hire Date -->
                                <div class="grid gap-2">
                                    <Label for="hire_date">
                                        Hire Date <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="hire_date"
                                        v-model="form.hire_date"
                                        type="date"
                                        name="hire_date"
                                        required
                                        :class="{ 'border-destructive': form.errors.hire_date }"
                                    />
                                    <InputError :message="form.errors.hire_date" />
                                </div>

                                <!-- Contract Type -->
                                <div class="grid gap-2">
                                    <Label for="contract_type">Contract Type</Label>
                                    <Input
                                        id="contract_type"
                                        v-model="form.contract_type"
                                        type="text"
                                        name="contract_type"
                                        placeholder="e.g. Full-time, Part-time"
                                        :class="{ 'border-destructive': form.errors.contract_type }"
                                    />
                                    <InputError :message="form.errors.contract_type" />
                                </div>

                                <!-- Status -->
                                <div class="grid gap-2">
                                    <Label for="status">
                                        Status <span class="text-destructive">*</span>
                                    </Label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        name="status"
                                        required
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        :class="{ 'border-destructive': form.errors.status }"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                    <InputError :message="form.errors.status" />
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
                                <Link href="/company/employees">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing || !subscription_info.can_add_employee"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else-if="!subscription_info.can_add_employee">Limit Reached</span>
                                <span v-else>Create Employee</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

