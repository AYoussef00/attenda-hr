<script setup lang="ts">
import EmployeeLayout from '@/layouts/employee/EmployeeLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Receipt, ArrowLeft, Download, Building2, User, Calendar } from 'lucide-vue-next';

const props = defineProps<{
    entry: {
        id: number;
        employee: {
            id: number;
            name: string;
            employee_code: string;
            department: string;
        };
        cycle: {
            month: string;
            month_formatted: string;
        };
        basic_salary: number;
        total_allowances: number;
        total_overtime_amount: number;
        total_deductions: number;
        attendance_deductions: number;
        leave_deductions: number;
        manual_deductions: number;
        fixed_deductions: number;
        net_salary: number;
        notes: string | null;
        status: string;
    };
    company: {
        id: number;
        name: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'Payslips',
        href: '/company/employee/payslips',
    },
    {
        title: props.entry.cycle.month_formatted,
        href: `/company/employee/payslips/${props.entry.id}`,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(amount);
};
</script>

<template>
    <Head :title="`Payslip - ${entry.cycle.month_formatted}`" />

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-8 w-8 p-0"
                            @click="router.visit('/company/employee/payslips')"
                        >
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                        <p class="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">
                            Payslip
                        </p>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        {{ entry.cycle.month_formatted }}
                    </h1>
                    <p class="text-sm text-slate-500">
                        Payslip details for {{ entry.cycle.month_formatted }}
                    </p>
                </div>
            </div>

            <!-- Payslip Card -->
            <Card class="border-0 bg-white shadow-sm rounded-2xl max-w-4xl mx-auto w-full">
                <CardHeader class="border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                                <Receipt class="h-8 w-8 text-emerald-600" />
                            </div>
                            <div>
                                <CardTitle class="text-xl">{{ company.name }}</CardTitle>
                                <CardDescription class="text-sm">
                                    Payslip for {{ entry.cycle.month_formatted }}
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-6">
                    <!-- Employee Information -->
                    <div class="grid gap-4 mb-6 pb-6 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <User class="h-5 w-5 text-slate-400" />
                            <div>
                                <p class="text-sm text-slate-500">Employee</p>
                                <p class="font-semibold text-slate-900">{{ entry.employee.name }}</p>
                                <p class="text-xs text-slate-500">Code: {{ entry.employee.employee_code }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Building2 class="h-5 w-5 text-slate-400" />
                            <div>
                                <p class="text-sm text-slate-500">Department</p>
                                <p class="font-semibold text-slate-900">{{ entry.employee.department }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Calendar class="h-5 w-5 text-slate-400" />
                            <div>
                                <p class="text-sm text-slate-500">Period</p>
                                <p class="font-semibold text-slate-900">{{ entry.cycle.month_formatted }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings -->
                    <div class="space-y-3 mb-6">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Earnings</h3>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Basic Salary</span>
                            <span class="font-medium text-slate-900">{{ formatCurrency(entry.basic_salary) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Allowances</span>
                            <span class="font-medium text-slate-900">{{ formatCurrency(entry.total_allowances) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Overtime</span>
                            <span class="font-medium text-slate-900">{{ formatCurrency(entry.total_overtime_amount) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b-2 border-slate-300 pt-2">
                            <span class="font-semibold text-slate-900">Total Earnings</span>
                            <span class="font-bold text-slate-900">
                                {{ formatCurrency(entry.basic_salary + entry.total_allowances + entry.total_overtime_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="space-y-3 mb-6">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Deductions</h3>
                        <div v-if="entry.fixed_deductions > 0" class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Fixed Deductions</span>
                            <span class="font-medium text-red-600">-{{ formatCurrency(entry.fixed_deductions) }}</span>
                        </div>
                        <div v-if="entry.attendance_deductions > 0" class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Attendance Deductions</span>
                            <span class="font-medium text-red-600">-{{ formatCurrency(entry.attendance_deductions) }}</span>
                        </div>
                        <div v-if="entry.leave_deductions > 0" class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Leave Deductions</span>
                            <span class="font-medium text-red-600">-{{ formatCurrency(entry.leave_deductions) }}</span>
                        </div>
                        <div v-if="entry.manual_deductions > 0" class="flex justify-between items-center py-2 border-b">
                            <span class="text-slate-600">Manual Deductions</span>
                            <span class="font-medium text-red-600">-{{ formatCurrency(entry.manual_deductions) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b-2 border-slate-300 pt-2">
                            <span class="font-semibold text-slate-900">Total Deductions</span>
                            <span class="font-bold text-red-600">
                                <span v-if="entry.total_deductions > 0">-{{ formatCurrency(entry.total_deductions) }}</span>
                                <span v-else class="text-slate-400">{{ formatCurrency(0) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Net Salary -->
                    <div class="flex justify-between items-center py-4 bg-emerald-50 rounded-lg px-4 mb-4">
                        <span class="text-lg font-bold text-slate-900">Net Salary</span>
                        <span class="text-2xl font-bold text-emerald-600">{{ formatCurrency(entry.net_salary) }}</span>
                    </div>

                    <!-- Notes -->
                    <div v-if="entry.notes" class="mt-4 p-3 bg-slate-50 rounded-lg">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Notes</p>
                        <p class="text-sm text-slate-700">{{ entry.notes }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </EmployeeLayout>
</template>

