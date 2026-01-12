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
import { TrendingUp, ArrowLeft, Calendar, Clock, User, CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface EmployeeSummary {
    employee_id: number;
    name: string;
    employee_code: string | null;
    position: string | null;
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

interface DailyPerformanceItem {
    date: string;
    employee_id: number;
    employee_name: string;
    employee_code: string;
    position: string | null;
    check_in: string | null;
    check_out: string | null;
    is_late: boolean;
    is_early_leave: boolean;
    score: number;
    status: string;
}

const props = defineProps<{
    month: string;
    employeeSummaries: EmployeeSummary[];
    dailyPerformance: DailyPerformanceItem[];
    company: {
        id: number;
        name: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Performance',
        href: '/company/performance',
    },
    {
        title: `${new Date(props.month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`,
        href: `/company/performance/${props.month}`,
    },
];

const getStatusBadgeVariant = (status: string | null) => {
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

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'present':
            return { label: 'Present', variant: 'default', icon: CheckCircle2 };
        case 'partial':
            return { label: 'Partial', variant: 'outline', icon: AlertCircle };
        case 'absent':
            return { label: 'Absent', variant: 'destructive', icon: XCircle };
        default:
            return { label: status, variant: 'outline', icon: Clock };
    }
};

// Group daily performance by employee
const dailyPerformanceByEmployee = computed(() => {
    const grouped: Record<number, DailyPerformanceItem[]> = {};
    props.dailyPerformance.forEach((item) => {
        if (!grouped[item.employee_id]) {
            grouped[item.employee_id] = [];
        }
        grouped[item.employee_id].push(item);
    });
    return grouped;
});

// Get unique dates for table header
const uniqueDates = computed(() => {
    const dates = new Set<string>();
    props.dailyPerformance.forEach((item) => {
        dates.add(item.date);
    });
    return Array.from(dates).sort();
});

// Get employees list
const employees = computed(() => {
    return props.employeeSummaries.map((summary) => ({
        id: summary.employee_id,
        name: summary.name,
        employee_code: summary.employee_code,
        position: summary.position,
        summary: summary,
    }));
});
</script>

<template>
    <Head :title="`Performance - ${new Date(month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
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
                            @click="router.visit('/company/performance')"
                        >
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                        <p class="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">
                            Performance
                        </p>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        {{ new Date(month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}
                    </h1>
                    <p class="text-sm text-slate-500">
                        Detailed attendance records and performance scores for all employees.
                    </p>
                </div>
            </div>

            <!-- Employee Summaries -->
            <Card class="border-0 bg-white shadow-sm rounded-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <User class="h-4 w-4 text-[#1e3b3b]" />
                        Employee Performance Summary
                    </CardTitle>
                    <CardDescription>
                        Monthly performance scores and statistics for each employee.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-xs text-slate-500">
                                    <th class="px-4 py-3 text-left font-medium">
                                        Employee
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
                                        Monthly Score
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="employeeSummaries.length === 0"
                                    class="border-b border-slate-100 bg-white/70"
                                >
                                    <td colspan="8" class="px-4 py-6 text-center text-xs text-slate-400">
                                        No performance data available for this month.
                                    </td>
                                </tr>
                                <tr
                                    v-for="summary in employeeSummaries"
                                    :key="summary.employee_id"
                                    class="border-b border-slate-100 bg-white/60 last:border-b-0 hover:bg-slate-50/80"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-slate-900">
                                                {{ summary.name }}
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ summary.employee_code || '—' }}
                                                <span v-if="summary.position"> • {{ summary.position }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ summary.working_days }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ summary.late_count }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ summary.early_leave_count }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ summary.absence_days }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ summary.perfect_days }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="summary.score !== null" class="flex items-center justify-center">
                                            <div class="relative flex h-12 w-12 items-center justify-center">
                                                <svg class="h-12 w-12 -rotate-90 transform">
                                                    <circle
                                                        cx="24"
                                                        cy="24"
                                                        r="18"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        fill="none"
                                                        class="text-slate-200"
                                                    />
                                                    <circle
                                                        cx="24"
                                                        cy="24"
                                                        r="18"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        fill="none"
                                                        :class="getScoreRingColor(summary.score)"
                                                        stroke-dasharray="113.1"
                                                        :stroke-dashoffset="113.1 - (summary.score / 100) * 113.1"
                                                        stroke-linecap="round"
                                                        class="transition-all duration-500"
                                                    />
                                                </svg>
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span
                                                        :class="[
                                                            'text-xs font-bold',
                                                            getScoreColor(summary.score)
                                                        ]"
                                                    >
                                                        {{ Math.round(summary.score) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <Badge
                                            v-if="summary.status"
                                            :variant="getStatusBadgeVariant(summary.status)"
                                            class="text-[11px] capitalize"
                                        >
                                            {{ summary.status }}
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

            <!-- Daily Performance Records -->
            <Card class="border-0 bg-white shadow-sm rounded-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <Calendar class="h-4 w-4 text-[#1e3b3b]" />
                        Daily Attendance Records
                    </CardTitle>
                    <CardDescription>
                        Detailed daily attendance records for all employees in this month.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-slate-500">
                                    <th class="sticky left-0 z-10 bg-slate-50 px-3 py-2 text-left font-medium">
                                        Employee
                                    </th>
                                    <th
                                        v-for="date in uniqueDates"
                                        :key="date"
                                        class="min-w-[100px] px-2 py-2 text-center font-medium"
                                    >
                                        <div class="flex flex-col">
                                            <span class="text-[10px]">
                                                {{ new Date(date).toLocaleDateString('en-US', { weekday: 'short' }) }}
                                            </span>
                                            <span class="text-[10px]">
                                                {{ new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employees"
                                    :key="employee.id"
                                    class="border-b border-slate-100 bg-white/60 last:border-b-0 hover:bg-slate-50/80"
                                >
                                    <td class="sticky left-0 z-10 bg-white px-3 py-2">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-medium text-slate-900">
                                                {{ employee.name }}
                                            </span>
                                            <span class="text-[10px] text-slate-500">
                                                {{ employee.employee_code || '—' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        v-for="date in uniqueDates"
                                        :key="`${employee.id}-${date}`"
                                        class="px-2 py-2 text-center"
                                    >
                                        <div
                                            v-if="dailyPerformanceByEmployee[employee.id]"
                                            class="flex flex-col items-center gap-0.5"
                                        >
                                            <div
                                                v-for="record in dailyPerformanceByEmployee[employee.id].filter(r => r.date === date)"
                                                :key="record.date"
                                                class="flex flex-col items-center gap-0.5"
                                            >
                                                <div
                                                    v-if="record.check_in || record.check_out"
                                                    class="flex flex-col items-center gap-0.5"
                                                >
                                                    <div
                                                        v-if="record.check_in"
                                                        :class="[
                                                            'text-[10px] font-medium',
                                                            record.is_late ? 'text-rose-600' : 'text-emerald-600'
                                                        ]"
                                                    >
                                                        {{ record.check_in }}
                                                        <span v-if="record.is_late" class="text-rose-500">⚠</span>
                                                    </div>
                                                    <div
                                                        v-if="record.check_out"
                                                        :class="[
                                                            'text-[10px] font-medium',
                                                            record.is_early_leave ? 'text-amber-600' : 'text-slate-600'
                                                        ]"
                                                    >
                                                        {{ record.check_out }}
                                                        <span v-if="record.is_early_leave" class="text-amber-500">⚠</span>
                                                    </div>
                                                </div>
                                                <Badge
                                                    :variant="getStatusBadge(record.status).variant"
                                                    class="text-[9px] px-1.5 py-0.5 h-auto"
                                                >
                                                    {{ getStatusBadge(record.status).label }}
                                                </Badge>
                                            </div>
                                            <div
                                                v-if="!dailyPerformanceByEmployee[employee.id]?.some(r => r.date === date)"
                                                class="text-[10px] text-slate-400"
                                            >
                                                —
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-[10px] text-slate-400"
                                        >
                                            —
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

