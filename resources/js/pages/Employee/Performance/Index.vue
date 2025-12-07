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
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { TrendingUp, Calendar, Award } from 'lucide-vue-next';

const props = defineProps<{
    company: {
        id: number;
        name: string;
    };
    employee: {
        id: number;
        name: string;
        employee_code: string;
    };
    performance: {
        monthly: Array<{
            month: string;
            month_formatted: string;
            working_days: number;
            late_count: number;
            early_leave_count: number;
            absence_days: number;
            perfect_days: number;
            score: number | null;
            status: string | null;
        }>;
        average_score: number | null;
        months_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'Performance',
        href: '/company/employee/performance',
    },
];

const getStatusVariant = (status: string | null) => {
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

const formatScore = (score: number | null) => {
    if (score === null || Number.isNaN(score)) return '-';
    return `${score.toFixed(1)}%`;
};
</script>

<template>
    <Head title="My Performance" />

    <EmployeeLayout :breadcrumbs="breadcrumbs">
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
                        My Attendance Performance
                    </h1>
                    <p class="text-sm text-slate-500">
                        Monthly performance score based on your attendance (check in / check out) for
                        each month.
                    </p>
                </div>

                <!-- Summary -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Average Score
                            </CardTitle>
                            <TrendingUp class="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-emerald-600">
                                {{ props.performance.average_score !== null
                                    ? `${props.performance.average_score.toFixed(1)}%`
                                    : '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Overall performance across all tracked months
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Tracked Months
                            </CardTitle>
                            <Calendar class="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ props.performance.months_count }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Months where you had attendance & a performance score
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="border-0 bg-white shadow-sm rounded-2xl">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                Latest Month
                            </CardTitle>
                            <Award class="h-4 w-4 text-amber-600" />
                        </CardHeader>
                        <CardContent>
                            <div v-if="performance.monthly.length">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ performance.monthly[0].month_formatted }}
                                </p>
                                <p class="text-xl font-bold text-amber-600 mt-1">
                                    {{ formatScore(performance.monthly[0].score) }}
                                </p>
                            </div>
                            <div v-else>
                                <p class="text-sm text-slate-500">
                                    No performance data yet.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Monthly performance table -->
            <Card class="border-0 bg-white shadow-sm rounded-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <TrendingUp class="h-4 w-4 text-emerald-600" />
                        Monthly Performance
                    </CardTitle>
                    <CardDescription>
                        One row per month showing your overall performance percentage and key
                        attendance metrics.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="performance.monthly.length === 0" class="py-8 text-center">
                        <p class="text-sm text-slate-500">
                            No performance data yet. Once you have attendance for a full month, your
                            performance will be calculated and shown here.
                        </p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-slate-50/70">
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Month
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Score
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Working Days
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Perfect Days
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Late Arrivals
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Early Leaves
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Absence Days
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left font-medium text-slate-500 whitespace-nowrap"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="month in performance.monthly"
                                    :key="month.month"
                                    class="border-b last:border-0 hover:bg-slate-50/70"
                                >
                                    <td class="px-4 py-3 text-slate-900 whitespace-nowrap">
                                        {{ month.month_formatted }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-emerald-700 whitespace-nowrap">
                                        {{ formatScore(month.score) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ month.working_days }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ month.perfect_days }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ month.late_count }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ month.early_leave_count }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                        {{ month.absence_days }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge
                                            :variant="getStatusVariant(month.status)"
                                            class="capitalize"
                                        >
                                            {{ month.status ?? 'n/a' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </EmployeeLayout>
</template>


