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
import { Head, router, usePage } from '@inertiajs/vue3';
import { CreditCard, CheckCircle2, Clock, FileText, DollarSign, ArrowLeft, Download, Check } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    cycle: {
        id: number;
        month: string;
        status: string;
        generated_at: string | null;
        paid_at: string | null;
    };
    entries: Array<{
        id: number;
        employee_id: number;
        employee_name: string;
        employee_code: string;
        basic_salary: number;
        total_allowances: number;
        total_overtime_amount: number;
        total_deductions: number;
        net_salary: number;
        notes: string | null;
        status: string;
        created_at: string;
    }>;
    summary: {
        total_employees: number;
        total_basic_salary: number;
        total_allowances: number;
        total_overtime: number;
        total_deductions: number;
        total_net_salary: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Payroll',
        href: '/company/payroll',
    },
    {
        title: `Cycle ${props.cycle.month}`,
        href: `/company/payroll/cycle/${props.cycle.id}`,
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const formatMonth = (month: string) => {
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

const getStatusVariant = (status: string) => {
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

const approveEntry = (id: number) => {
    router.post(`/company/payroll/entry/approve/${id}`, {}, {
        preserveScroll: true,
    });
};

const markPaid = (id: number) => {
    router.post(`/company/payroll/entry/pay/${id}`, {}, {
        preserveScroll: true,
    });
};

const markAllPaid = () => {
    if (confirm('Are you sure you want to mark all entries as paid?')) {
        router.post(`/company/payroll/cycle/${props.cycle.id}/pay-all`, {}, {
            preserveScroll: true,
        });
    }
};

const viewPayslip = (id: number) => {
    window.open(`/company/payroll/entry/payslip/${id}`, '_blank');
};
</script>

<template>
    <Head :title="`Payroll Cycle - ${formatMonth(cycle.month)}`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Flash Messages -->
            <Alert v-if="flash?.success" class="bg-emerald-50 border-emerald-200">
                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                <AlertTitle class="text-emerald-800">Success</AlertTitle>
                <AlertDescription class="text-emerald-700">
                    {{ flash.success }}
                </AlertDescription>
            </Alert>

            <!-- Header -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="router.visit('/company/payroll')"
                                >
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back
                                </Button>
                            </div>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Payroll Cycle: {{ formatMonth(cycle.month) }}
                            </CardTitle>
                            <CardDescription>
                                <Badge :variant="getStatusVariant(cycle.status)" class="mt-2">
                                    {{ cycle.status }}
                                </Badge>
                                <span v-if="cycle.generated_at" class="ml-4">
                                    Generated: {{ new Date(cycle.generated_at).toLocaleDateString() }}
                                </span>
                                <span v-if="cycle.paid_at" class="ml-4">
                                    Paid: {{ new Date(cycle.paid_at).toLocaleDateString() }}
                                </span>
                            </CardDescription>
                        </div>
                        <Button
                            v-if="cycle.status !== 'paid'"
                            @click="markAllPaid"
                        >
                            <Check class="h-4 w-4 mr-2" />
                            Mark All as Paid
                        </Button>
                    </div>
                </CardHeader>
            </Card>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Total Employees
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ summary.total_employees }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Total Net Salary
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ formatCurrency(summary.total_net_salary) }}
                        </div>
                    </CardContent>
                </Card>
                <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="router.visit(`/company/payroll/cycle/${cycle.id}/deductions`)">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center justify-between">
                            <span>Total Deductions</span>
                            <span class="text-xs text-slate-400">Click to view details</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_deductions) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Payroll Entries Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Payroll Entries</CardTitle>
                    <CardDescription>
                        Detailed breakdown for each employee
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-3 font-semibold text-slate-700">Employee</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Basic Salary</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Allowances</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Overtime</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Deductions</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Net Salary</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Status</th>
                                    <th class="text-left p-3 font-semibold text-slate-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="entry in entries"
                                    :key="entry.id"
                                    class="border-b hover:bg-slate-50 transition-colors"
                                >
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium">{{ entry.employee_name }}</div>
                                            <div class="text-sm text-slate-500">{{ entry.employee_code }}</div>
                                        </div>
                                    </td>
                                    <td class="p-3">{{ formatCurrency(entry.basic_salary) }}</td>
                                    <td class="p-3">{{ formatCurrency(entry.total_allowances) }}</td>
                                    <td class="p-3">{{ formatCurrency(entry.total_overtime_amount) }}</td>
                                    <td class="p-3">
                                        <Button
                                            variant="link"
                                            class="p-0 h-auto font-semibold text-red-600"
                                            @click="router.visit(`/company/payroll/cycle/${cycle.id}/deductions?employee_id=${entry.employee_id}`)"
                                        >
                                            {{ formatCurrency(entry.total_deductions) }}
                                        </Button>
                                    </td>
                                    <td class="p-3 font-semibold">
                                        {{ formatCurrency(entry.net_salary) }}
                                    </td>
                                    <td class="p-3">
                                        <Badge :variant="getStatusVariant(entry.status)">
                                            {{ entry.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <Button
                                                v-if="entry.status === 'pending'"
                                                variant="outline"
                                                size="sm"
                                                @click="approveEntry(entry.id)"
                                            >
                                                Approve
                                            </Button>
                                            <Button
                                                v-if="entry.status === 'approved'"
                                                variant="outline"
                                                size="sm"
                                                @click="markPaid(entry.id)"
                                            >
                                                Mark Paid
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="viewPayslip(entry.id)"
                                            >
                                                <Download class="h-4 w-4" />
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

