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
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, TrendingDown } from 'lucide-vue-next';

const props = defineProps<{
    cycle: {
        id: number;
        month: string;
    };
    company: {
        name: string;
    };
    entries: Array<{
        id: number;
        employee_id: number;
        employee_name: string;
        employee_code: string;
        total_deductions: number;
        attendance_deductions: number;
        leave_deductions: number;
        manual_deductions: number;
        fixed_deductions: number;
    }>;
    summary: {
        total_attendance_deductions: number;
        total_leave_deductions: number;
        total_manual_deductions: number;
        total_fixed_deductions: number;
        total_deductions: number;
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
    {
        title: 'Deductions Breakdown',
        href: `/company/payroll/cycle/${props.cycle.id}/deductions`,
    },
];

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

const goBack = () => {
    router.visit(`/company/payroll/cycle/${props.cycle.id}`);
};
</script>

<template>
    <Head :title="`Deductions Breakdown - ${formatMonth(cycle.month)}`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <TrendingDown class="h-5 w-5 text-red-600" />
                                Deductions Breakdown
                            </CardTitle>
                            <CardDescription>
                                {{ formatMonth(cycle.month) }} - Detailed deductions for all employees
                            </CardDescription>
                        </div>
                        <Button
                            variant="ghost"
                            @click="goBack"
                        >
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Button>
                    </div>
                </CardHeader>
            </Card>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Fixed Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_fixed_deductions) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Attendance Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_attendance_deductions) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Leave Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_leave_deductions) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Manual Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_manual_deductions) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600">
                            Total Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(summary.total_deductions) }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Detailed Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Employee Deductions Details</CardTitle>
                    <CardDescription>
                        Breakdown of deductions for each employee
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-3 font-semibold text-slate-700">Employee</th>
                                    <th class="text-right p-3 font-semibold text-slate-700">Fixed Deductions</th>
                                    <th class="text-right p-3 font-semibold text-slate-700">Attendance Deductions</th>
                                    <th class="text-right p-3 font-semibold text-slate-700">Leave Deductions</th>
                                    <th class="text-right p-3 font-semibold text-slate-700">Manual Deductions</th>
                                    <th class="text-right p-3 font-semibold text-slate-700">Total Deductions</th>
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
                                    <td class="p-3 text-right">
                                        <span :class="entry.fixed_deductions > 0 ? 'text-red-600' : 'text-slate-400'">
                                            {{ formatCurrency(entry.fixed_deductions) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <span :class="entry.attendance_deductions > 0 ? 'text-red-600' : entry.attendance_deductions < 0 ? 'text-emerald-600' : 'text-slate-400'">
                                            <span v-if="entry.attendance_deductions < 0">+{{ formatCurrency(Math.abs(entry.attendance_deductions)) }}</span>
                                            <span v-else>{{ formatCurrency(entry.attendance_deductions) }}</span>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <span :class="entry.leave_deductions > 0 ? 'text-red-600' : 'text-slate-400'">
                                            {{ formatCurrency(entry.leave_deductions) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <span :class="entry.manual_deductions > 0 ? 'text-red-600' : 'text-slate-400'">
                                            {{ formatCurrency(entry.manual_deductions) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-semibold text-red-600">
                                        {{ formatCurrency(entry.total_deductions) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-semibold">
                                    <td class="p-3">Total</td>
                                    <td class="p-3 text-right text-red-600">
                                        {{ formatCurrency(summary.total_fixed_deductions) }}
                                    </td>
                                    <td class="p-3 text-right text-red-600">
                                        {{ formatCurrency(summary.total_attendance_deductions) }}
                                    </td>
                                    <td class="p-3 text-right text-red-600">
                                        {{ formatCurrency(summary.total_leave_deductions) }}
                                    </td>
                                    <td class="p-3 text-right text-red-600">
                                        {{ formatCurrency(summary.total_manual_deductions) }}
                                    </td>
                                    <td class="p-3 text-right text-red-600">
                                        {{ formatCurrency(summary.total_deductions) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

