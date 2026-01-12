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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    User,
    Mail,
    Phone,
    Calendar,
    Briefcase,
    Building2,
    Clock,
    FileText,
    TrendingUp,
    ArrowLeft,
    Download,
    File,
    Image,
    FileCheck,
    AlertCircle,
    Bell,
    CreditCard,
    Eye,
    DollarSign,
    Settings,
    X,
} from 'lucide-vue-next';

interface Employee {
    id: number;
    name: string;
    email: string;
    phone: string;
    employee_code: string;
    position: string | null;
    department: string;
    department_id: number | null;
    shift: string;
    shift_id: number | null;
    hire_date: string | null;
    contract_type: string | null;
    status: string;
    basic_salary: number | null;
    hourly_rate: number | null;
    overtime_rate: number | null;
    allowances_fixed: Array<{ type: string; amount: number }> | null;
    deductions_fixed: Array<{ type: string; reason: string; amount: number }> | null;
    working_hours_per_day: number | null;
    working_days_per_month: number | null;
    created_at: string;
}

interface PerformanceItem {
    month: string;
    working_days: number;
    late_count: number;
    early_leave_count: number;
    absence_days: number;
    perfect_days: number;
    score: number | null;
    daily_score: number | null;
    status: string | null;
}

interface DocumentType {
    id: number;
    name_ar: string;
    name_en: string;
    slug: string;
    reminder_sent_today?: boolean;
}

interface Document {
    id: number;
    title: string;
    document_type_id: number | null;
    type: DocumentType | null;
    file_path: string;
    file_type: string | null;
    issued_date: string | null;
    expiry_date: string | null;
    uploaded_by: number | null;
    uploaded_by_name: string;
    note: string | null;
    status: string;
    created_at: string;
}

interface Payslip {
    id: number;
    month: string;
    basic_salary: number;
    total_allowances: number;
    total_overtime_amount: number;
    total_deductions: number;
    net_salary: number;
    status: string;
    created_at: string;
}

const props = defineProps<{
    employee: Employee;
    performanceHistory: PerformanceItem[];
    documents: Document[];
    missingDocumentTypes: DocumentType[];
    payslips: Payslip[];
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
        title: props.employee.name,
        href: `/company/employees/${props.employee.id}`,
    },
];

const sendReminder = (documentTypeId: number) => {
    router.post(`/company/employees/${props.employee.id}/remind-document`, {
        document_type_id: documentTypeId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

const getStatusBadgeVariant = (status: string) => {
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

const getPerformanceStatusBadgeVariant = (status: string | null) => {
    switch (status) {
        case 'excellent':
            return 'default';
        case 'good':
            return 'secondary';
        case 'fair':
            return 'outline';
        case 'poor':
            return 'destructive';
        default:
            return 'outline';
    }
};

const getScoreColor = (score: number | null) => {
    if (score === null) return 'text-slate-400';
    if (score >= 90) return 'text-emerald-600';
    if (score >= 75) return 'text-blue-600';
    if (score >= 60) return 'text-amber-600';
    return 'text-rose-600';
};

const getScoreRingColor = (score: number | null) => {
    if (score === null) return 'stroke-slate-200';
    if (score >= 90) return 'stroke-emerald-600';
    if (score >= 75) return 'stroke-blue-600';
    if (score >= 60) return 'stroke-amber-600';
    return 'stroke-rose-600';
};

const getInitials = (name: string) => {
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};

const getFileIcon = (fileType: string | null) => {
    if (!fileType) return File;
    if (fileType === 'image') return Image;
    if (fileType === 'pdf') return FileText;
    return File;
};

const downloadDocument = (document: Document) => {
    window.open(`/storage/${document.file_path}`, '_blank');
};

const formatMonth = (month: string) => {
    if (month === 'N/A') return 'N/A';
    const [year, monthNum] = month.split('-');
    const date = new Date(parseInt(year), parseInt(monthNum) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const getPayrollStatusVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default';
        case 'approved':
            return 'default';
        case 'pending':
            return 'secondary';
        default:
            return 'outline';
    }
};

const viewPayslip = (id: number) => {
    window.open(`/company/payroll/entry/payslip/${id}`, '_blank');
};

const salarySettingsDialogOpen = ref(false);

const salaryForm = useForm({
    basic_salary: props.employee.basic_salary ?? null,
    hourly_rate: props.employee.hourly_rate ?? null,
    overtime_rate: props.employee.overtime_rate ?? null,
    allowances_fixed: props.employee.allowances_fixed ?? [],
    deductions_fixed: props.employee.deductions_fixed ?? [],
    working_hours_per_day: props.employee.working_hours_per_day ?? 8,
    working_days_per_month: props.employee.working_days_per_month ?? 26,
});

const openSalarySettingsDialog = () => {
    // Reset form with current employee values
    salaryForm.basic_salary = props.employee.basic_salary ?? null;
    salaryForm.hourly_rate = props.employee.hourly_rate ?? null;
    salaryForm.overtime_rate = props.employee.overtime_rate ?? null;
    salaryForm.allowances_fixed = props.employee.allowances_fixed ? [...props.employee.allowances_fixed] : [];
    salaryForm.deductions_fixed = props.employee.deductions_fixed ? [...props.employee.deductions_fixed] : [];
    salaryForm.working_hours_per_day = props.employee.working_hours_per_day ?? 8;
    salaryForm.working_days_per_month = props.employee.working_days_per_month ?? 26;
    salarySettingsDialogOpen.value = true;
};

const addAllowance = () => {
    salaryForm.allowances_fixed.push({ type: '', amount: 0 });
};

const removeAllowance = (index: number) => {
    salaryForm.allowances_fixed.splice(index, 1);
};

const addDeduction = () => {
    salaryForm.deductions_fixed.push({ type: '', reason: '', amount: 0 });
};

const removeDeduction = (index: number) => {
    salaryForm.deductions_fixed.splice(index, 1);
};

const totalAllowances = computed(() => {
    if (!props.employee.allowances_fixed || props.employee.allowances_fixed.length === 0) {
        return 0;
    }
    return props.employee.allowances_fixed.reduce((sum, allowance) => sum + (allowance.amount || 0), 0);
});

const totalDeductions = computed(() => {
    if (!props.employee.deductions_fixed || props.employee.deductions_fixed.length === 0) {
        return 0;
    }
    return props.employee.deductions_fixed.reduce((sum, deduction) => sum + (deduction.amount || 0), 0);
});

const updateSalarySettings = () => {
    console.log('Form data before submit:', salaryForm.data());
    
    salaryForm.put(`/company/employees/${props.employee.id}/salary-settings`, {
        preserveScroll: true,
        onSuccess: () => {
            salarySettingsDialogOpen.value = false;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
        onFinish: () => {
            console.log('Form processing finished');
        },
    });
};

const isExpired = (expiryDate: string | null) => {
    if (!expiryDate) return false;
    return new Date(expiryDate) < new Date();
};
</script>

<template>
    <Head :title="`${employee.name} - Employee Profile`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button
                        as-child
                        variant="ghost"
                        size="icon"
                        class="rounded-full"
                    >
                        <Link href="/company/employees">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                            Employee Profile
                        </h1>
                        <p class="text-sm text-slate-500">
                            View detailed information about {{ employee.name }}
                        </p>
                    </div>
                </div>
                <Button
                    as-child
                    class="rounded-full bg-[#1e3b3b] text-emerald-50 hover:bg-[#234444]"
                >
                    <Link :href="`/company/employees/${employee.id}/edit`">
                        Edit Employee
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Employee Details Card -->
                <Card class="border-0 bg-white shadow-sm rounded-2xl md:col-span-1">
                    <CardHeader>
                        <div class="flex flex-col items-center gap-4">
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full bg-[#1e3b3b] text-2xl font-semibold text-emerald-50"
                            >
                                {{ getInitials(employee.name) }}
                            </div>
                            <div class="text-center">
                                <CardTitle class="text-lg">{{ employee.name }}</CardTitle>
                                <CardDescription class="mt-1">
                                    {{ employee.position || 'Employee' }}
                                </CardDescription>
                                <Badge
                                    :variant="getStatusBadgeVariant(employee.status)"
                                    class="mt-2 capitalize"
                                >
                                    {{ employee.status }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Mail class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Email</p>
                                    <p class="font-medium text-slate-900">
                                        {{ employee.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Phone class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Phone</p>
                                    <p class="font-medium text-slate-900">
                                        {{ employee.phone || '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <User class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Employee Code</p>
                                    <p class="font-medium text-slate-900 font-mono">
                                        {{ employee.employee_code }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Building2 class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Department</p>
                                    <p class="font-medium text-slate-900">
                                        {{ employee.department }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Clock class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Shift</p>
                                    <p class="font-medium text-slate-900">
                                        {{ employee.shift }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <Calendar class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Hire Date</p>
                                    <p class="font-medium text-slate-900">
                                        {{
                                            employee.hire_date
                                                ? new Date(
                                                      employee.hire_date,
                                                  ).toLocaleDateString('en-US', {
                                                      year: 'numeric',
                                                      month: 'long',
                                                      day: 'numeric',
                                                  })
                                                : '—'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="employee.basic_salary"
                                class="flex items-center gap-3 text-sm"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"
                                >
                                    <DollarSign class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Basic Salary</p>
                                    <p class="font-medium text-slate-900">
                                        {{ formatCurrency(employee.basic_salary) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contract Type & Performance History -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Contract Type Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <FileText class="h-4 w-4 text-[#1e3b3b]" />
                                Contract Information
                            </CardTitle>
                            <CardDescription>
                                Employee contract type and employment details
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div>
                                        <p class="text-xs font-medium text-slate-500">
                                            Contract Type
                                        </p>
                                        <p
                                            v-if="employee.contract_type"
                                            class="mt-1 text-lg font-semibold text-slate-900"
                                        >
                                            {{ employee.contract_type }}
                                        </p>
                                        <p
                                            v-else
                                            class="mt-1 text-sm text-slate-400"
                                        >
                                            Not specified
                                        </p>
                                    </div>
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-[#1e3b3b]/10"
                                    >
                                        <Briefcase class="h-5 w-5 text-[#1e3b3b]" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Salary Settings Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2 text-sm">
                                        <Settings class="h-4 w-4 text-emerald-600" />
                                        Salary Settings
                                    </CardTitle>
                                    <CardDescription>
                                        Configure hourly rate, overtime, allowances, and deductions
                                    </CardDescription>
                                </div>
                                <Dialog v-model:open="salarySettingsDialogOpen">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" size="sm" @click="openSalarySettingsDialog">
                                            <Settings class="h-4 w-4 mr-2" />
                                            Edit Settings
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                                        <DialogHeader>
                                            <DialogTitle>Edit Salary Settings</DialogTitle>
                                            <DialogDescription>
                                                Update salary configuration for {{ employee.name }}
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="updateSalarySettings" class="space-y-6 py-4">
                                            <!-- Basic Salary Information -->
                                            <div class="space-y-4">
                                                <div class="border-b border-slate-200 pb-2">
                                                    <h3 class="text-sm font-semibold text-slate-900">Basic Salary Information</h3>
                                                    <p class="text-xs text-slate-500 mt-1">Configure the employee's base salary and rates</p>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <Label for="basic_salary" class="text-sm font-medium">Basic Salary</Label>
                                                        <Input
                                                            id="basic_salary"
                                                            :model-value="salaryForm.basic_salary ?? ''"
                                                            @update:model-value="salaryForm.basic_salary = $event ? parseFloat(String($event)) : null"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            placeholder="0.00"
                                                            class="h-10"
                                                        />
                                                    </div>
                                                    <div class="space-y-2">
                                                        <Label for="hourly_rate" class="text-sm font-medium">Hourly Rate</Label>
                                                        <Input
                                                            id="hourly_rate"
                                                            :model-value="salaryForm.hourly_rate ?? ''"
                                                            @update:model-value="salaryForm.hourly_rate = $event ? parseFloat(String($event)) : null"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            placeholder="0.00"
                                                            class="h-10"
                                                        />
                                                    </div>
                                                    <div class="space-y-2">
                                                        <Label for="overtime_rate" class="text-sm font-medium">Overtime Rate (Multiplier)</Label>
                                                        <Input
                                                            id="overtime_rate"
                                                            :model-value="salaryForm.overtime_rate ?? ''"
                                                            @update:model-value="salaryForm.overtime_rate = $event ? parseFloat(String($event)) : null"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            placeholder="e.g., 1.25, 1.5, 2.0"
                                                            class="h-10"
                                                        />
                                                        <p class="text-xs text-slate-500">Leave empty if overtime is not applicable</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Allowances and Deductions -->
                                            <div class="space-y-4">
                                                <div class="border-b border-slate-200 pb-2">
                                                    <h3 class="text-sm font-semibold text-slate-900">Allowances & Deductions</h3>
                                                    <p class="text-xs text-slate-500 mt-1">Add fixed allowances and deductions for this employee</p>
                                                </div>
                                                <div class="grid grid-cols-2 gap-6">
                                                    <!-- Fixed Allowances -->
                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between">
                                                            <Label class="text-sm font-medium">Fixed Allowances</Label>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                @click="addAllowance"
                                                                class="h-8 text-xs"
                                                            >
                                                                + Add Allowance
                                                            </Button>
                                                        </div>
                                                        <div v-if="salaryForm.allowances_fixed.length === 0" class="text-sm text-slate-400 italic py-4 text-center border border-dashed border-slate-200 rounded-lg bg-slate-50">
                                                            No allowances added yet
                                                        </div>
                                                        <div v-else class="space-y-3">
                                                            <div
                                                                v-for="(allowance, index) in salaryForm.allowances_fixed"
                                                                :key="index"
                                                                class="p-3 border border-slate-200 rounded-lg bg-slate-50/50 space-y-2"
                                                            >
                                                                <div class="flex gap-2 items-end">
                                                                    <div class="flex-1">
                                                                        <Label :for="`allowance_type_${index}`" class="text-xs">Type</Label>
                                                                        <Input
                                                                            :id="`allowance_type_${index}`"
                                                                            v-model="allowance.type"
                                                                            type="text"
                                                                            placeholder="e.g., Transportation"
                                                                            class="h-9 text-sm"
                                                                        />
                                                                    </div>
                                                                    <div class="flex-1">
                                                                        <Label :for="`allowance_amount_${index}`" class="text-xs">Amount</Label>
                                                                        <Input
                                                                            :id="`allowance_amount_${index}`"
                                                                            v-model.number="allowance.amount"
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            placeholder="0.00"
                                                                            class="h-9 text-sm"
                                                                        />
                                                                    </div>
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        @click="removeAllowance(index)"
                                                                        class="h-9 w-9 mb-0 hover:bg-red-50"
                                                                    >
                                                                        <X class="h-4 w-4 text-red-600" />
                                                                    </Button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Fixed Deductions -->
                                                    <div class="space-y-3">
                                                        <div class="flex items-center justify-between">
                                                            <Label class="text-sm font-medium">Fixed Deductions</Label>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                @click="addDeduction"
                                                                class="h-8 text-xs"
                                                            >
                                                                + Add Deduction
                                                            </Button>
                                                        </div>
                                                        <div v-if="salaryForm.deductions_fixed.length === 0" class="text-sm text-slate-400 italic py-4 text-center border border-dashed border-slate-200 rounded-lg bg-slate-50">
                                                            No deductions added yet
                                                        </div>
                                                        <div v-else class="space-y-3">
                                                            <div
                                                                v-for="(deduction, index) in salaryForm.deductions_fixed"
                                                                :key="index"
                                                                class="p-3 border border-slate-200 rounded-lg bg-red-50/30 space-y-2"
                                                            >
                                                                <div class="flex gap-2 items-end">
                                                                    <div class="flex-1">
                                                                        <Label :for="`deduction_type_${index}`" class="text-xs">Type</Label>
                                                                        <Input
                                                                            :id="`deduction_type_${index}`"
                                                                            v-model="deduction.type"
                                                                            type="text"
                                                                            placeholder="e.g., Insurance"
                                                                            class="h-9 text-sm"
                                                                        />
                                                                    </div>
                                                                    <div class="flex-1">
                                                                        <Label :for="`deduction_amount_${index}`" class="text-xs">Amount</Label>
                                                                        <Input
                                                                            :id="`deduction_amount_${index}`"
                                                                            v-model.number="deduction.amount"
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            placeholder="0.00"
                                                                            class="h-9 text-sm"
                                                                        />
                                                                    </div>
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        @click="removeDeduction(index)"
                                                                        class="h-9 w-9 mb-0 hover:bg-red-50"
                                                                    >
                                                                        <X class="h-4 w-4 text-red-600" />
                                                                    </Button>
                                                                </div>
                                                                <div>
                                                                    <Label :for="`deduction_reason_${index}`" class="text-xs">Reason</Label>
                                                                    <Input
                                                                        :id="`deduction_reason_${index}`"
                                                                        v-model="deduction.reason"
                                                                        type="text"
                                                                        placeholder="e.g., Health insurance premium"
                                                                        class="h-9 text-sm"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Working Schedule -->
                                            <div class="space-y-4">
                                                <div class="border-b border-slate-200 pb-2">
                                                    <h3 class="text-sm font-semibold text-slate-900">Working Schedule</h3>
                                                    <p class="text-xs text-slate-500 mt-1">Set the employee's working hours and days</p>
                                                </div>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <Label for="working_hours_per_day" class="text-sm font-medium">Working Hours Per Day</Label>
                                                        <Input
                                                            id="working_hours_per_day"
                                                            v-model="salaryForm.working_hours_per_day"
                                                            type="number"
                                                            min="1"
                                                            max="24"
                                                            placeholder="8"
                                                            class="h-10"
                                                        />
                                                    </div>
                                                    <div class="space-y-2">
                                                        <Label for="working_days_per_month" class="text-sm font-medium">Working Days Per Month</Label>
                                                        <Input
                                                            id="working_days_per_month"
                                                            v-model="salaryForm.working_days_per_month"
                                                            type="number"
                                                            min="1"
                                                            max="31"
                                                            placeholder="26"
                                                            class="h-10"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    @click="salarySettingsDialogOpen = false"
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    :disabled="salaryForm.processing"
                                                >
                                                    {{ salaryForm.processing ? 'Saving...' : 'Save Changes' }}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Basic Salary</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ employee.basic_salary ? formatCurrency(employee.basic_salary) : '—' }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Hourly Rate</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ employee.hourly_rate ? formatCurrency(employee.hourly_rate) : '—' }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Overtime Rate</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ employee.overtime_rate ? `${employee.overtime_rate}x` : '—' }}
                                    </p>
                                </div>
                                <div class="space-y-1 col-span-2">
                                    <p class="text-xs text-slate-500">Fixed Allowances</p>
                                    <div v-if="!employee.allowances_fixed || employee.allowances_fixed.length === 0" class="text-sm text-slate-400">
                                        —
                                    </div>
                                    <div v-else class="space-y-1">
                                        <div
                                            v-for="(allowance, index) in employee.allowances_fixed"
                                            :key="index"
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span class="text-slate-600">{{ allowance.type }}:</span>
                                            <span class="font-semibold text-emerald-600">+{{ formatCurrency(allowance.amount) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                                            <span class="text-xs font-medium text-slate-700">Total:</span>
                                            <span class="text-sm font-bold text-emerald-600">+{{ formatCurrency(totalAllowances) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1 col-span-2">
                                    <p class="text-xs text-slate-500">Fixed Deductions</p>
                                    <div v-if="!employee.deductions_fixed || employee.deductions_fixed.length === 0" class="text-sm text-slate-400">
                                        —
                                    </div>
                                    <div v-else class="space-y-1">
                                        <div
                                            v-for="(deduction, index) in employee.deductions_fixed"
                                            :key="index"
                                            class="space-y-0.5"
                                        >
                                            <div class="flex items-center justify-between text-sm">
                                                <div class="flex flex-col">
                                                    <span class="text-slate-600 font-medium">{{ deduction.type }}</span>
                                                    <span v-if="deduction.reason" class="text-xs text-slate-400">{{ deduction.reason }}</span>
                                                </div>
                                                <span class="font-semibold text-red-600">-{{ formatCurrency(deduction.amount) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                                            <span class="text-xs font-medium text-slate-700">Total:</span>
                                            <span class="text-sm font-bold text-red-600">-{{ formatCurrency(totalDeductions) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Working Hours/Day</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ employee.working_hours_per_day ?? '—' }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-slate-500">Working Days/Month</p>
                                    <p class="font-semibold text-slate-900">
                                        {{ employee.working_days_per_month ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Performance History Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <TrendingUp class="h-4 w-4 text-[#1e3b3b]" />
                                Performance History
                            </CardTitle>
                            <CardDescription>
                                Monthly attendance performance evaluation history
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="performanceHistory.length === 0"
                                class="py-8 text-center"
                            >
                                <p class="text-sm text-slate-400">
                                    No performance history available yet.
                                </p>
                            </div>
                            <div
                                v-else
                                class="overflow-x-auto"
                            >
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="border-b border-slate-100 bg-slate-50 text-xs text-slate-500"
                                        >
                                            <th class="px-4 py-3 text-left font-medium">
                                                Month
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Working Days
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Late
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Early Leave
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Absence
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Perfect Days
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Daily Score
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Monthly Score
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in performanceHistory"
                                            :key="item.month"
                                            class="border-b border-slate-100 bg-white/60 last:border-b-0 hover:bg-slate-50/80"
                                        >
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                <span class="font-medium text-[#1e3b3b]">
                                                    {{
                                                        new Date(
                                                            item.month + '-01',
                                                        ).toLocaleDateString('en-US', {
                                                            month: 'short',
                                                            year: 'numeric',
                                                        })
                                                    }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                {{ item.working_days }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                {{ item.late_count }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                {{ item.early_leave_count }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                {{ item.absence_days }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-600">
                                                {{ item.perfect_days }}
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-900">
                                                {{ item.daily_score ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div
                                                    v-if="item.score !== null"
                                                    class="flex items-center justify-center"
                                                >
                                                    <div
                                                        class="relative flex h-12 w-12 items-center justify-center"
                                                    >
                                                        <svg
                                                            class="h-12 w-12 -rotate-90 transform"
                                                        >
                                                            <!-- Background circle -->
                                                            <circle
                                                                cx="24"
                                                                cy="24"
                                                                r="18"
                                                                stroke="currentColor"
                                                                stroke-width="3"
                                                                fill="none"
                                                                class="text-slate-200"
                                                            />
                                                            <!-- Progress circle -->
                                                            <circle
                                                                cx="24"
                                                                cy="24"
                                                                r="18"
                                                                stroke="currentColor"
                                                                stroke-width="3"
                                                                fill="none"
                                                                :class="getScoreRingColor(item.score)"
                                                                stroke-dasharray="113.1"
                                                                :stroke-dashoffset="113.1 - (item.score / 100) * 113.1"
                                                                stroke-linecap="round"
                                                                class="transition-all duration-500"
                                                            />
                                                        </svg>
                                                        <!-- Score text -->
                                                        <div
                                                            class="absolute inset-0 flex items-center justify-center"
                                                        >
                                                            <span
                                                                :class="[
                                                                    'text-xs font-bold',
                                                                    getScoreColor(item.score),
                                                                ]"
                                                            >
                                                                {{ Math.round(item.score) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span
                                                    v-else
                                                    class="text-xs text-slate-400"
                                                >
                                                    —
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs">
                                                <Badge
                                                    v-if="item.status"
                                                    :variant="
                                                        getPerformanceStatusBadgeVariant(
                                                            item.status,
                                                        )
                                                    "
                                                    class="text-[11px] capitalize"
                                                >
                                                    {{ item.status }}
                                                </Badge>
                                                <span
                                                    v-else
                                                    class="text-slate-400"
                                                >
                                                    —
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Documents Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <FileCheck class="h-4 w-4 text-[#1e3b3b]" />
                                Documents
                            </CardTitle>
                            <CardDescription>
                                Employee documents and files
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="documents.length === 0"
                                class="py-8 text-center"
                            >
                                <FileText class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                <p class="text-sm text-slate-400">
                                    No documents uploaded yet.
                                </p>
                            </div>
                            <div
                                v-else
                                class="space-y-3"
                            >
                                <div
                                    v-for="document in documents"
                                    :key="document.id"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50 transition-colors"
                                >
                                    <div class="flex items-center gap-4 flex-1">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#1e3b3b]/10"
                                        >
                                            <component
                                                :is="getFileIcon(document.file_type)"
                                                class="h-5 w-5 text-[#1e3b3b]"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-medium text-slate-900 truncate">
                                                    {{ document.title }}
                                                </p>
                                                <Badge
                                                    v-if="document.type"
                                                    variant="outline"
                                                    class="text-[10px] shrink-0"
                                                >
                                                    {{ document.type.name_en }}
                                                </Badge>
                                                <Badge
                                                    v-if="document.status === 'expired'"
                                                    variant="destructive"
                                                    class="text-[10px] shrink-0"
                                                >
                                                    Expired
                                                </Badge>
                                                <Badge
                                                    v-else-if="document.status === 'active'"
                                                    variant="default"
                                                    class="text-[10px] shrink-0"
                                                >
                                                    Active
                                                </Badge>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                                <span v-if="document.file_type" class="capitalize">
                                                    {{ document.file_type }}
                                                </span>
                                                <span v-if="document.issued_date">
                                                    • Issued: {{ new Date(document.issued_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                                </span>
                                                <span v-if="document.expiry_date">
                                                    • Expires: {{ new Date(document.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                                </span>
                                                <span v-if="document.uploaded_by_name">
                                                    • Uploaded by: {{ document.uploaded_by_name }}
                                                </span>
                                            </div>
                                            <p
                                                v-if="document.note"
                                                class="text-xs text-slate-400 mt-1 line-clamp-1"
                                            >
                                                {{ document.note }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="shrink-0"
                                        @click="downloadDocument(document)"
                                    >
                                        <Download class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Missing Documents Card -->
                    <Card
                        v-if="missingDocumentTypes.length > 0"
                        class="border-0 bg-white shadow-sm rounded-2xl"
                    >
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <AlertCircle class="h-4 w-4 text-amber-600" />
                                Missing Documents
                            </CardTitle>
                            <CardDescription>
                                Document types that haven't been uploaded for this employee
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="type in missingDocumentTypes"
                                    :key="type.id"
                                    class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50/50 p-3"
                                >
                                    <div class="flex items-center gap-3 flex-1">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100"
                                        >
                                            <FileText class="h-4 w-4 text-amber-600" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">
                                                {{ type.name_en }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                Not uploaded yet
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            class="text-[10px] border-amber-300 text-amber-700"
                                        >
                                            Missing
                                        </Badge>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-8 px-3 text-xs"
                                            :disabled="type.reminder_sent_today"
                                            @click="sendReminder(type.id)"
                                        >
                                            <Bell class="h-3 w-3 mr-1" />
                                            {{ type.reminder_sent_today ? 'Reminded Today' : 'Remind' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payslips Card -->
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <CreditCard class="h-4 w-4 text-emerald-600" />
                                Payslips
                            </CardTitle>
                            <CardDescription>
                                Monthly payslips for this employee
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="payslips.length === 0" class="text-center py-8 text-slate-500">
                                No payslips found for this employee
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="payslip in payslips"
                                    :key="payslip.id"
                                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-slate-50 transition-colors"
                                >
                                    <div class="flex items-center gap-4 flex-1">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100">
                                            <CreditCard class="h-6 w-6 text-emerald-600" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="font-semibold text-lg">
                                                    {{ formatMonth(payslip.month) }}
                                                </h3>
                                                <Badge :variant="getPayrollStatusVariant(payslip.status)">
                                                    {{ payslip.status }}
                                                </Badge>
                                            </div>
                                            <div class="text-sm text-slate-500 mt-1">
                                                <span>Net Salary: <strong class="text-emerald-600">{{ formatCurrency(payslip.net_salary) }}</strong></span>
                                                <span class="ml-4">Basic: {{ formatCurrency(payslip.basic_salary) }}</span>
                                                <span v-if="payslip.total_allowances > 0" class="ml-4">
                                                    Allowances: +{{ formatCurrency(payslip.total_allowances) }}
                                                </span>
                                                <span v-if="payslip.total_overtime_amount > 0" class="ml-4">
                                                    Overtime: +{{ formatCurrency(payslip.total_overtime_amount) }}
                                                </span>
                                                <span class="ml-4" :class="payslip.total_deductions > 0 ? 'text-red-600' : 'text-slate-500'">
                                                    Deductions: <span v-if="payslip.total_deductions > 0">-{{ formatCurrency(payslip.total_deductions) }}</span>
                                                    <span v-else>{{ formatCurrency(0) }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="viewPayslip(payslip.id)"
                                    >
                                        <Eye class="h-4 w-4 mr-2" />
                                        View
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>

