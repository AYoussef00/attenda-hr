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
import { Head, router } from '@inertiajs/vue3';
import { Download, Printer, ArrowLeft } from 'lucide-vue-next';

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
        name: string;
    };
}>();

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

const printPayslip = () => {
    window.print();
};

const goBack = () => {
    // Go back to payroll page
    router.visit('/company/payroll');
};
</script>

<template>
    <Head :title="`Payslip - ${entry.employee.name}`" />

    <CompanyLayout>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Actions -->
            <div class="flex items-center justify-between mb-4">
                <Button
                    variant="ghost"
                    @click="goBack"
                >
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Button>
                <div class="flex gap-2">
                    <Button variant="outline" @click="printPayslip">
                        <Printer class="h-4 w-4 mr-2" />
                        Print
                    </Button>
                    <Button variant="outline">
                        <Download class="h-4 w-4 mr-2" />
                        Download PDF
                    </Button>
                </div>
            </div>

            <!-- Payslip Card -->
            <Card class="max-w-4xl mx-auto print:shadow-none print:border-0">
                <CardContent class="p-8">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-8 pb-6 border-b">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900">{{ company.name }}</h1>
                            <p class="text-slate-600">Payslip</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-600">Period</p>
                            <p class="font-semibold">{{ formatMonth(entry.cycle.month) }}</p>
                        </div>
                    </div>

                    <!-- Employee Info -->
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-sm font-medium text-slate-600 mb-2">Employee Information</h3>
                            <p class="font-semibold">{{ entry.employee.name }}</p>
                            <p class="text-sm text-slate-600">Code: {{ entry.employee.employee_code }}</p>
                            <p class="text-sm text-slate-600">Department: {{ entry.employee.department }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-slate-600 mb-2">Payment Status</h3>
                            <Badge :variant="entry.status === 'paid' ? 'default' : 'secondary'">
                                {{ entry.status }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Salary Breakdown -->
                    <div class="space-y-4 mb-8">
                        <h3 class="text-lg font-semibold text-slate-900">Salary Breakdown</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-slate-600">Basic Salary</span>
                                <span class="font-medium">{{ formatCurrency(entry.basic_salary) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-slate-600">Allowances</span>
                                <span class="font-medium text-emerald-600">
                                    +{{ formatCurrency(entry.total_allowances) }}
                                </span>
                            </div>
                            
                            <div v-if="entry.total_overtime_amount > 0" class="flex justify-between items-center py-2 border-b">
                                <span class="text-slate-600">Overtime</span>
                                <span class="font-medium text-emerald-600">
                                    +{{ formatCurrency(entry.total_overtime_amount) }}
                                </span>
                            </div>
                            
                            <!-- Deductions Breakdown -->
                            <div class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Deductions Breakdown</h4>
                                <div class="space-y-2 pl-4">
                                    <div v-if="entry.fixed_deductions > 0" class="flex justify-between items-center py-1">
                                        <span class="text-sm text-slate-600">Fixed Deductions</span>
                                        <span class="text-sm font-medium text-red-600">
                                            -{{ formatCurrency(entry.fixed_deductions) }}
                                        </span>
                                    </div>
                                    <div v-if="entry.attendance_deductions > 0" class="flex justify-between items-center py-1">
                                        <span class="text-sm text-slate-600">Attendance Deductions</span>
                                        <span class="text-sm font-medium text-red-600">
                                            -{{ formatCurrency(entry.attendance_deductions) }}
                                        </span>
                                    </div>
                                    <div v-else-if="entry.attendance_deductions < 0" class="flex justify-between items-center py-1">
                                        <span class="text-sm text-slate-600">Attendance Bonus</span>
                                        <span class="text-sm font-medium text-emerald-600">
                                            +{{ formatCurrency(Math.abs(entry.attendance_deductions)) }}
                                        </span>
                                    </div>
                                    <div v-if="entry.leave_deductions > 0" class="flex justify-between items-center py-1">
                                        <span class="text-sm text-slate-600">Leave Deductions</span>
                                        <span class="text-sm font-medium text-red-600">
                                            -{{ formatCurrency(entry.leave_deductions) }}
                                        </span>
                                    </div>
                                    <div v-if="entry.manual_deductions > 0" class="flex justify-between items-center py-1">
                                        <span class="text-sm text-slate-600">Manual Deductions</span>
                                        <span class="text-sm font-medium text-red-600">
                                            -{{ formatCurrency(entry.manual_deductions) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center py-2 border-b border-t mt-2">
                                <span class="text-slate-600 font-semibold">Total Deductions</span>
                                <span class="font-medium text-red-600">
                                    <span v-if="entry.total_deductions > 0">-{{ formatCurrency(entry.total_deductions) }}</span>
                                    <span v-else class="text-slate-400">{{ formatCurrency(0) }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Net Salary -->
                    <div class="bg-slate-50 rounded-lg p-6 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-slate-900">Net Salary</span>
                            <span class="text-2xl font-bold text-emerald-600">
                                {{ formatCurrency(entry.net_salary) }}
                            </span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="entry.notes" class="mt-6 pt-6 border-t">
                        <h3 class="text-sm font-medium text-slate-600 mb-2">Notes</h3>
                        <p class="text-sm text-slate-700">{{ entry.notes }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 pt-6 border-t text-center text-sm text-slate-500">
                        <p>This is a computer-generated payslip. No signature required.</p>
                        <p class="mt-2">Generated on {{ new Date().toLocaleDateString() }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

<style scoped>
@media print {
    .print\:shadow-none {
        box-shadow: none;
    }
    .print\:border-0 {
        border: 0;
    }
}
</style>

