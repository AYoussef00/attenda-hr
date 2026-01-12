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
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { TrendingUp, Award, ArrowDownCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PerformanceItem {
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
    employee_code: string | null;
    check_in: string | null;
    check_out: string | null;
    is_late: boolean;
    is_early_leave: boolean;
    score: number;
    status: string;
}

const props = defineProps<{
    month: string;
    monthsWithAttendance?: string[];
    items: PerformanceItem[];
    bestEmployees: PerformanceItem[];
    worstEmployees: PerformanceItem[];
    dailyPerformance: DailyPerformanceItem[];
    employees: Array<{
        id: number;
        name: string;
        employee_code: string | null;
    }>;
    filters: {
        employee_id: string | null;
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
];

const localEmployeeId = ref(props.filters.employee_id || '');

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (localEmployeeId.value) {
        params.employee_id = localEmployeeId.value;
    }
    
    router.get(
        '/company/performance',
        params,
        { preserveState: true, preserveScroll: true },
    );
};

const overallAverage = computed(() => {
    const withScore = props.items.filter((item) => item.score !== null);
    if (!withScore.length) return null;
    const total = withScore.reduce((sum, item) => sum + (item.score ?? 0), 0);
    return Math.round((total / withScore.length) * 100) / 100;
});

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

const getCircularProgress = (score: number | null) => {
    if (score === null) return 0;
    const percentage = Math.min(100, Math.max(0, score));
    const circumference = 2 * Math.PI * 20; // radius = 20
    const offset = circumference - (percentage / 100) * circumference;
    return { circumference, offset };
};
</script>

<template>
    <Head title="Performance" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div class="space-y-1.5">
                    <p class="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">
                        Performance
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Attendance performance overview
                    </h1>
                    <p class="text-sm text-slate-500">
                        Daily and monthly scores based on attendance, absence, and shift adherence.
                    </p>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium text-slate-500">
                            Tracked employees
                        </CardTitle>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1e3b3b]/10">
                            <TrendingUp class="h-4 w-4 text-[#1e3b3b]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-slate-900">
                            {{ items.length }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Employees with performance records.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium text-slate-500">
                            Average monthly score
                        </CardTitle>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/10">
                            <Award class="h-4 w-4 text-emerald-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-slate-900">
                            {{ overallAverage ?? '—' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Across all employees with scores.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-xs font-medium text-slate-500">
                            Perfect attendance days
                        </CardTitle>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1e3b3b]/10">
                            <ArrowDownCircle class="h-4 w-4 text-[#1e3b3b]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-slate-900">
                            {{
                                items.reduce(
                                    (sum, item) => sum + (item.perfect_days || 0),
                                    0,
                                )
                            }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Days with no lateness, early leaves or absences.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Best / worst employees -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Award class="h-4 w-4 text-emerald-600" />
                            Top performers
                        </CardTitle>
                        <CardDescription>
                            Employees with the highest monthly attendance score.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="bestEmployees.length === 0" class="py-4 text-xs text-slate-400">
                            No performance data for this month.
                        </div>
                        <div
                            v-else
                            class="space-y-3"
                        >
                            <div
                                v-for="employee in bestEmployees"
                                :key="employee.employee_id"
                                class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white"
                                    >
                                        {{ employee.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">
                                            {{ employee.name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ employee.position || 'Employee' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Circular Progress -->
                                    <div class="relative flex h-14 w-14 items-center justify-center">
                                        <svg class="h-14 w-14 -rotate-90 transform">
                                            <!-- Background circle -->
                                            <circle
                                                cx="28"
                                                cy="28"
                                                r="20"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                                class="text-slate-200"
                                            />
                                            <!-- Progress circle -->
                                            <circle
                                                v-if="employee.score !== null"
                                                cx="28"
                                                cy="28"
                                                r="20"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                                :class="getScoreRingColor(employee.score)"
                                                stroke-dasharray="125.6"
                                                :stroke-dashoffset="125.6 - (employee.score / 100) * 125.6"
                                                stroke-linecap="round"
                                                class="transition-all duration-500"
                                            />
                                        </svg>
                                        <!-- Score text -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span
                                                :class="[
                                                    'text-sm font-bold',
                                                    getScoreColor(employee.score)
                                                ]"
                                            >
                                                {{ employee.score !== null ? Math.round(employee.score) : '—' }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Month info -->
                                    <div class="text-right">
                                        <p class="text-[11px] text-slate-500">
                                            Monthly score
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            {{ new Date(employee.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <ArrowDownCircle class="h-4 w-4 text-rose-600" />
                            Needs attention
                        </CardTitle>
                        <CardDescription>
                            Employees with the lowest monthly attendance score.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="worstEmployees.length === 0" class="py-4 text-xs text-slate-400">
                            No performance data for this month.
                        </div>
                        <div
                            v-else
                            class="space-y-3"
                        >
                            <div
                                v-for="employee in worstEmployees"
                                :key="`${employee.employee_id}-${employee.month}`"
                                class="flex items-center justify-between rounded-xl bg-rose-50 px-3 py-2"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-600 text-xs font-semibold text-white"
                                    >
                                        {{ employee.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">
                                            {{ employee.name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ employee.position || 'Employee' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Circular Progress -->
                                    <div class="relative flex h-14 w-14 items-center justify-center">
                                        <svg class="h-14 w-14 -rotate-90 transform">
                                            <!-- Background circle -->
                                            <circle
                                                cx="28"
                                                cy="28"
                                                r="20"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                                class="text-slate-200"
                                            />
                                            <!-- Progress circle -->
                                            <circle
                                                v-if="employee.score !== null"
                                                cx="28"
                                                cy="28"
                                                r="20"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                                :class="getScoreRingColor(employee.score)"
                                                stroke-dasharray="125.6"
                                                :stroke-dashoffset="125.6 - (employee.score / 100) * 125.6"
                                                stroke-linecap="round"
                                                class="transition-all duration-500"
                                            />
                                        </svg>
                                        <!-- Score text -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span
                                                :class="[
                                                    'text-sm font-bold',
                                                    getScoreColor(employee.score)
                                                ]"
                                            >
                                                {{ employee.score !== null ? Math.round(employee.score) : '—' }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Month info -->
                                    <div class="text-right">
                                        <p class="text-[11px] text-slate-500">
                                            Monthly score
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            {{ new Date(employee.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Monthly Performance Evaluation - Months Table -->
            <Card class="border-0 bg-white shadow-sm rounded-2xl">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <TrendingUp class="h-4 w-4 text-[#1e3b3b]" />
                            Monthly Performance Evaluation
                        </CardTitle>
                        <CardDescription>
                            Click on a month to view detailed performance records for all employees.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-xs text-slate-500">
                                    <th class="px-4 py-3 text-left font-medium">
                                        Month
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        Year
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="!monthsWithAttendance || monthsWithAttendance.length === 0"
                                    class="border-b border-slate-100 bg-white/70"
                                >
                                    <td colspan="3" class="px-4 py-6 text-center text-xs text-slate-400">
                                        No attendance records found. Months will appear here automatically when attendance data is available.
                                    </td>
                                </tr>
                                <tr
                                    v-for="monthItem in monthsWithAttendance"
                                    :key="monthItem"
                                    @click="router.visit(`/company/performance/${monthItem}`)"
                                    class="border-b border-slate-100 bg-white/60 last:border-b-0 hover:bg-slate-50/80 cursor-pointer transition-colors"
                                >
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ new Date(monthItem + '-01').toLocaleDateString('en-US', { month: 'long' }) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-slate-600">
                                            {{ new Date(monthItem + '-01').toLocaleDateString('en-US', { year: 'numeric' }) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2 text-xs text-[#1e3b3b]">
                                            <TrendingUp class="h-4 w-4" />
                                            <span class="font-medium">View Details</span>
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


