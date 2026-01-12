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
import { ref } from 'vue';

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
        attendance_details?: Array<{
            date: string;
            check_in: string | null;
            check_out: string | null;
            late_minutes: number | null;
            early_minutes: number | null;
            deduction: number;
            notes: string[];
        }>;
    }>;
    summary: {
        total_attendance_deductions: number;
        total_leave_deductions: number;
        total_manual_deductions: number;
        total_fixed_deductions: number;
        total_deductions: number;
    };
}>();

const expandedEntryId = ref<number | null>(null);
const detailsSection = ref<HTMLElement | null>(null);

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

const toggleDetails = (entryId: number) => {
    expandedEntryId.value = expandedEntryId.value === entryId ? null : entryId;
};

const scrollToDetails = () => {
    const el = (detailsSection.value as any)?.$el ?? detailsSection.value;
    if (el && typeof el.scrollIntoView === 'function') {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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
                <Card
                    class="cursor-pointer transition-shadow hover:shadow-md"
                    tabindex="0"
                    @click="scrollToDetails"
                    @keyup.enter.space="scrollToDetails"
                >
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
            <div ref="detailsSection">
                <Card>
                    <CardHeader>
                        <CardTitle>Employee Deductions Details</CardTitle>
                        <CardDescription>
                            Breakdown of deductions for each employee
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!entries || entries.length === 0" class="py-8 text-center text-sm text-slate-500">
                            No deductions found for this cycle.
                        </div>
                        <div v-else class="overflow-x-auto">
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
                                            <button
                                                class="underline underline-offset-2 hover:text-red-700"
                                                @click="toggleDetails(entry.id)"
                                            >
                                                {{ formatCurrency(entry.total_deductions) }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="expandedEntryId === entry.id" :key="`${entry.id}-details`" class="bg-slate-50/60">
                                        <td colspan="6" class="p-3">
                                            <div v-if="entry.attendance_details && entry.attendance_details.length" class="overflow-x-auto">
                                                <table class="min-w-full text-sm text-slate-700 border border-slate-200 rounded-lg bg-white">
                                                    <thead>
                                                        <tr class="bg-slate-50 text-xs text-slate-600">
                                                            <th class="px-3 py-2 text-left font-semibold">Date</th>
                                                            <th class="px-3 py-2 text-left font-semibold">In</th>
                                                            <th class="px-3 py-2 text-left font-semibold">Out</th>
                                                            <th class="px-3 py-2 text-left font-semibold">Late</th>
                                                            <th class="px-3 py-2 text-left font-semibold">Early</th>
                                                            <th class="px-3 py-2 text-right font-semibold">Deduction</th>
                                                            <th class="px-3 py-2 text-left font-semibold">Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="detail in entry.attendance_details"
                                                            :key="`${entry.id}-${detail.date}-${detail.check_in}-${detail.check_out}`"
                                                            class="border-t border-slate-200"
                                                        >
                                                            <td class="px-3 py-2 font-semibold text-slate-900">{{ detail.date }}</td>
                                                            <td class="px-3 py-2">{{ detail.check_in || '—' }}</td>
                                                            <td class="px-3 py-2">{{ detail.check_out || '—' }}</td>
                                                            <td class="px-3 py-2">{{ detail.late_minutes ?? '—' }}</td>
                                                            <td class="px-3 py-2">{{ detail.early_minutes ?? '—' }}</td>
                                                            <td class="px-3 py-2 text-right font-semibold text-red-600">
                                                                {{ formatCurrency(detail.deduction) }}
                                                            </td>
                                                            <td class="px-3 py-2 text-slate-600 text-xs">
                                                                <div v-if="detail.notes && detail.notes.length" class="space-y-1">
                                                                    <div v-for="note in detail.notes" :key="note">• {{ note }}</div>
                                                                </div>
                                                                <span v-else class="text-slate-400">—</span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div v-else class="text-sm text-slate-500">
                                                No attendance deductions for this entry.
                                            </div>
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
        </div>
    </CompanyLayout>
</template>

