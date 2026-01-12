<script setup lang="ts">
import EmployeeLayout from '@/layouts/employee/EmployeeLayout.vue';
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
import { Receipt, Calendar, DollarSign, Eye } from 'lucide-vue-next';

interface PayslipEntry {
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

interface PayslipMonth {
    month: string;
    month_formatted: string;
    entries: PayslipEntry[];
}

const props = defineProps<{
    employee: {
        id: number;
        name: string;
        employee_code: string;
    };
    payslips: PayslipMonth[];
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
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(amount);
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default';
        case 'approved':
            return 'secondary';
        case 'pending':
            return 'outline';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head title="My Payslips" />

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div class="space-y-1.5">
                    <p class="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">
                        Payslips
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        My Payslips
                    </h1>
                    <p class="text-sm text-slate-500">
                        View your payslips organized by month.
                    </p>
                </div>
            </div>

            <!-- Payslips by Month -->
            <div v-if="payslips.length === 0" class="flex flex-col items-center justify-center py-12">
                <Receipt class="h-12 w-12 text-slate-300 mb-4" />
                <p class="text-sm text-slate-500">No payslips available yet.</p>
            </div>

            <div v-else class="space-y-6">
                <Card
                    v-for="monthGroup in payslips"
                    :key="monthGroup.month"
                    class="border-0 bg-white shadow-sm rounded-2xl"
                >
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                                    <Calendar class="h-5 w-5 text-emerald-600" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg">{{ monthGroup.month_formatted }}</CardTitle>
                                    <CardDescription>
                                        {{ monthGroup.entries.length }} payslip{{ monthGroup.entries.length !== 1 ? 's' : '' }}
                                    </CardDescription>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="entry in monthGroup.entries"
                                :key="entry.id"
                                class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100 transition-colors"
                            >
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                                        <DollarSign class="h-6 w-6 text-emerald-600" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <p class="text-sm font-semibold text-slate-900">
                                                Payslip #{{ entry.id }}
                                            </p>
                                            <Badge
                                                :variant="getStatusBadgeVariant(entry.status)"
                                                class="text-xs capitalize"
                                            >
                                                {{ entry.status }}
                                            </Badge>
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-slate-500">
                                            <span>Basic: {{ formatCurrency(entry.basic_salary) }}</span>
                                            <span>Allowances: {{ formatCurrency(entry.total_allowances) }}</span>
                                            <span>Overtime: {{ formatCurrency(entry.total_overtime_amount) }}</span>
                                            <span>Deductions: {{ formatCurrency(entry.total_deductions) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-emerald-600 mb-1">
                                            {{ formatCurrency(entry.net_salary) }}
                                        </p>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="router.visit(`/company/employee/payslips/${entry.id}`)"
                                        >
                                            <Eye class="h-4 w-4 mr-2" />
                                            View Details
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </EmployeeLayout>
</template>

