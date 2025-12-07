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
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Building2,
    Users,
    Clock,
    LogIn,
    LogOut,
    Calendar,
    TrendingUp,
    CalendarDays,
} from 'lucide-vue-next';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        logo: string | null;
    };
    stats: {
        total_employees: number;
        total_attendance: number;
        total_leaves: number;
    };
    recent_employees: Array<{
        id: number;
        name: string;
        employee_code: string;
        position: string | null;
        status: string;
        hire_date: string | null;
    }>;
    attendance_overview: {
        today: {
            check_ins: number;
            check_outs: number;
            total_records: number;
        };
    };
    recent_attendance: Array<{
        id: number;
        employee_name: string;
        employee_code: string;
        type: string;
        datetime: string;
        method: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Company Dashboard',
        href: '/company/dashboard',
    },
];

const getStatusVariant = (status: string) => {
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

const getAttendancePercent = (value: number) => {
    if (!props.stats.total_employees || props.stats.total_employees <= 0) {
        return 0;
    }
    const percent = (value / props.stats.total_employees) * 100;
    return Math.min(100, Math.round(percent));
};
</script>

<template>
    <Head title="Company Dashboard" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-gradient-to-b from-slate-50 to-white p-6"
        >
            <!-- Top header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1.5">
                    <p class="text-xs font-medium tracking-[0.2em] text-slate-500 uppercase">
                        Overview
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Welcome back, {{ company.name }}
                    </h1>
                    <p class="text-sm text-slate-500">
                        High-level snapshot of your people, attendance, and leave activity.
                    </p>
                </div>
                <div
                    class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white/70 px-4 py-2 text-xs text-slate-600 shadow-sm backdrop-blur"
                >
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                    <span class="font-medium">Attenda HR</span>
                    <span class="text-slate-400">•</span>
                    <span>Smart Workforce Management</span>
                </div>
            </div>

            <!-- Main grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left column: company + stats -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Company Info Card -->
                    <Card class="border-0 bg-white/80 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0">
                            <div class="flex items-center gap-4">
                                <div
                                    v-if="company.logo"
                                    class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                                >
                                    <img
                                        :src="company.logo"
                                        :alt="company.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div>
                                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                                        <Building2 class="h-4 w-4 text-slate-500" />
                                        {{ company.name }}
                                    </CardTitle>
                                    <CardDescription class="text-xs text-slate-500">
                                        {{ company.email }} • {{ company.phone }}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                    </Card>

                    <!-- Stats Cards -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <Card class="border-0 bg-white/80 shadow-sm">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                                <CardTitle class="text-xs font-medium text-slate-500">
                                    Total Employees
                                </CardTitle>
                                <Users class="h-4 w-4 text-slate-400" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold text-slate-900">
                                    {{ stats.total_employees }}
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Active headcount in your organization.
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border-0 bg-white/80 shadow-sm">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                                <CardTitle class="text-xs font-medium text-slate-500">
                                    Total Attendance
                                </CardTitle>
                                <Clock class="h-4 w-4 text-slate-400" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold text-slate-900">
                                    {{ stats.total_attendance }}
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Recorded check-ins across your team.
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border-0 bg-white/80 shadow-sm">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                                <CardTitle class="text-xs font-medium text-slate-500">
                                    Total Leaves
                                </CardTitle>
                                <CalendarDays class="h-4 w-4 text-slate-400" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-semibold text-slate-900">
                                    {{ stats.total_leaves }}
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Approved leave requests this period.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Attendance Overview -->
                    <Card class="border-0 bg-white/80 shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Clock class="h-5 w-5 text-slate-500" />
                                Attendance Overview
                            </CardTitle>
                            <CardDescription>
                                Live view of today's attendance activity.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Today's Attendance -->
                            <div class="mb-6">
                                <h3 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <Calendar class="h-4 w-4" />
                                    Today
                                </h3>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <Card class="border border-emerald-100 bg-emerald-50/60 shadow-none">
                                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                            <CardTitle class="text-xs font-medium text-emerald-900">
                                                Check Ins
                                            </CardTitle>
                                            <LogIn class="h-4 w-4 text-emerald-600" />
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-semibold text-emerald-900">
                                                {{ attendance_overview.today.check_ins }}
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card class="border border-sky-100 bg-sky-50/60 shadow-none">
                                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                            <CardTitle class="text-xs font-medium text-sky-900">
                                                Check Outs
                                            </CardTitle>
                                            <LogOut class="h-4 w-4 text-sky-600" />
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-semibold text-sky-900">
                                                {{ attendance_overview.today.check_outs }}
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card class="border border-slate-100 bg-slate-50/80 shadow-none">
                                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                            <CardTitle class="text-xs font-medium text-slate-800">
                                                Total Records
                                            </CardTitle>
                                            <TrendingUp class="h-4 w-4 text-slate-500" />
                                        </CardHeader>
                                        <CardContent>
                                            <div class="text-2xl font-semibold text-slate-900">
                                                {{ attendance_overview.today.total_records }}
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>

                            <!-- Recent Attendance Today -->
                            <div>
                                <h3 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <Clock class="h-4 w-4" />
                                    Recent Attendance (Today)
                                </h3>
                                <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50/60">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-slate-100 bg-white/70 text-xs text-slate-500">
                                                    <th class="px-4 py-3 text-left font-medium">
                                                        Employee
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-medium">
                                                        Time
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-medium">
                                                        Type
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-medium">
                                                        Method
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-if="recent_attendance.length === 0"
                                                    class="border-b border-slate-100 bg-white/70"
                                                >
                                                    <td colspan="4" class="px-4 py-8 text-center text-xs text-slate-400">
                                                        No attendance records found for today.
                                                    </td>
                                                </tr>
                                                <tr
                                                    v-for="record in recent_attendance"
                                                    :key="record.id"
                                                    class="border-b border-slate-100 bg-white/60 last:border-b-0 hover:bg-slate-50/80"
                                                >
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-col">
                                                            <span class="font-medium text-slate-900">
                                                                {{ record.employee_name }}
                                                            </span>
                                                            <span class="text-xs text-slate-500">
                                                                {{ record.employee_code }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs text-slate-500">
                                                        {{ record.datetime }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <Badge
                                                            :variant="record.type === 'in' ? 'default' : 'secondary'"
                                                            class="flex w-fit items-center gap-1 rounded-full px-3 py-1 text-xs"
                                                        >
                                                            <LogIn
                                                                v-if="record.type === 'in'"
                                                                class="h-3 w-3"
                                                            />
                                                            <LogOut
                                                                v-else
                                                                class="h-3 w-3"
                                                            />
                                                            {{ record.type === 'in' ? 'Check In' : 'Check Out' }}
                                                        </Badge>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs uppercase text-slate-500">
                                                        {{ record.method }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right column: recent employees -->
                <div class="space-y-4">
                    <Card class="border-0 bg-white/80 shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <Users class="h-4 w-4 text-slate-500" />
                                Recent Employees
                            </CardTitle>
                            <CardDescription>
                                Latest employees added to your company.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="recent_employees.length === 0" class="py-6 text-center text-xs text-slate-400">
                                No employees found.
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="employee in recent_employees"
                                    :key="employee.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white"
                                        >
                                            {{ employee.name.charAt(0) }}
                                        </div>
                                        <div class="space-y-0.5">
                                            <p class="text-sm font-medium text-slate-900">
                                                {{ employee.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ employee.position || 'Employee' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <Badge :variant="getStatusVariant(employee.status)" class="text-[10px]">
                                            {{ employee.status }}
                                        </Badge>
                                        <p class="mt-1 text-[11px] text-slate-400">
                                            {{ employee.employee_code }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attendance percentage -->
                    <Card class="border-0 bg-white/80 shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-sm">
                                Attendance Rates
                            </CardTitle>
                            <CardDescription>
                                Percentage of employees who checked in and out today.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-1">
                                <div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50/70 px-4 py-3">
                                    <div
                                        class="relative flex h-16 w-16 items-center justify-center rounded-full bg-slate-900/10"
                                        :style="{
                                            background: `conic-gradient(#1e3b3b ${getAttendancePercent(attendance_overview.today.check_ins)}%, rgba(15,23,42,0.08) 0)`,
                                        }"
                                    >
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-800">
                                            {{ getAttendancePercent(attendance_overview.today.check_ins) }}%
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Check-in rate
                                        </p>
                                        <p class="text-sm text-slate-600">
                                            Percentage of employees who checked in today.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50/70 px-4 py-3">
                                    <div
                                        class="relative flex h-16 w-16 items-center justify-center rounded-full bg-slate-900/10"
                                        :style="{
                                            background: `conic-gradient(#1e3b3b ${getAttendancePercent(attendance_overview.today.check_outs)}%, rgba(15,23,42,0.08) 0)`,
                                        }"
                                    >
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-xs font-semibold text-slate-800">
                                            {{ getAttendancePercent(attendance_overview.today.check_outs) }}%
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Check-out rate
                                        </p>
                                        <p class="text-sm text-slate-600">
                                            Percentage of employees who checked out today.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>

