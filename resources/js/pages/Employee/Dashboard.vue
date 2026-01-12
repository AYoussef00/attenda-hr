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
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Building2,
    Clock,
    LogIn,
    LogOut,
    Calendar,
    TrendingUp,
    CalendarDays,
    User,
    Briefcase,
    MapPin,
    Timer,
} from 'lucide-vue-next';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        logo: string | null;
    };
    employee: {
        id: number;
        employee_code: string;
        position: string | null;
        department: string;
        shift: string;
        hire_date: string | null;
        status: string;
    };
    stats: {
        total_attendance: number;
        total_leaves: number;
        pending_leaves: number;
        approved_leaves: number;
    };
    attendance_overview: {
        today: {
            check_ins: number;
            check_outs: number;
            total_records: number;
            last_check_in: string | null;
            last_check_out: string | null;
        };
    };
    recent_attendance: Array<{
        id: number;
        type: string;
        datetime: string;
        date: string;
        time: string;
        method: string;
    }>;
    recent_leaves: Array<{
        id: number;
        leave_type: string;
        start_date: string;
        end_date: string;
        days: number;
        status: string;
        note: string | null;
    }>;
    notifications?: Array<{
        id: number;
        title: string;
        message: string;
        type: string;
        read: boolean;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee Dashboard',
        href: '/company/employee/dashboard',
    },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'approved':
            return 'default';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head title="Attenda - Employee Dashboard | My HR Portal" />

    <EmployeeLayout :breadcrumbs="breadcrumbs" :notifications="notifications">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Company & Employee Info Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                v-if="company.logo"
                                class="h-16 w-16 rounded-lg overflow-hidden border"
                            >
                                <img
                                    :src="company.logo"
                                    :alt="company.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Building2 class="h-5 w-5" />
                                    {{ company.name }}
                                </CardTitle>
                                <CardDescription>
                                    {{ company.email }} | {{ company.phone }}
                                </CardDescription>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-2 mb-1">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <span class="font-semibold">{{ employee.employee_code }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Briefcase class="h-3 w-3" />
                                {{ employee.position || 'N/A' }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <MapPin class="h-3 w-3" />
                                {{ employee.department }}
                            </div>
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Timer class="h-3 w-3" />
                                {{ employee.shift }}
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Attendance
                        </CardTitle>
                        <Clock class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ stats.total_attendance }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Leaves
                        </CardTitle>
                        <CalendarDays class="h-4 w-4 text-purple-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-600">
                            {{ stats.total_leaves }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Pending Leaves
                        </CardTitle>
                        <Calendar class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">
                            {{ stats.pending_leaves }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Approved Leaves
                        </CardTitle>
                        <CalendarDays class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ stats.approved_leaves }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Attendance Overview -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Attendance Overview
                    </CardTitle>
                    <CardDescription>
                        Your attendance statistics for today
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Today's Attendance -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Today
                        </h3>
                        <div class="grid gap-4 md:grid-cols-3">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">
                                        Check Ins
                                    </CardTitle>
                                    <LogIn class="h-4 w-4 text-green-600" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ attendance_overview.today.check_ins }}
                                    </div>
                                    <p
                                        v-if="attendance_overview.today.last_check_in"
                                        class="text-xs text-muted-foreground mt-1"
                                    >
                                        Last: {{ attendance_overview.today.last_check_in }}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">
                                        Check Outs
                                    </CardTitle>
                                    <LogOut class="h-4 w-4 text-blue-600" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ attendance_overview.today.check_outs }}
                                    </div>
                                    <p
                                        v-if="attendance_overview.today.last_check_out"
                                        class="text-xs text-muted-foreground mt-1"
                                    >
                                        Last: {{ attendance_overview.today.last_check_out }}
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle class="text-sm font-medium">
                                        Total Records
                                    </CardTitle>
                                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold">
                                        {{ attendance_overview.today.total_records }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    <div>
                        <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
                            <Clock class="h-4 w-4" />
                            Recent Attendance
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Date
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Time
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Type
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Method
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-if="recent_attendance.length === 0"
                                        class="border-b"
                                    >
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                            No attendance records found
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="record in recent_attendance"
                                        :key="record.id"
                                        class="border-b hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ record.date }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ record.time }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge
                                                :variant="record.type === 'in' ? 'default' : 'secondary'"
                                                class="flex items-center gap-1 w-fit"
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
                                        <td class="px-4 py-3 text-sm text-muted-foreground uppercase">
                                            {{ record.method }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Leave Requests -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarDays class="h-5 w-5" />
                        Recent Leave Requests
                    </CardTitle>
                    <CardDescription>
                        Your recent leave requests
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Leave Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Days
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="recent_leaves.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No leave requests found
                                    </td>
                                </tr>
                                <tr
                                    v-for="leave in recent_leaves"
                                    :key="leave.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ leave.leave_type }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ leave.start_date }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ leave.end_date }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ leave.days }} {{ leave.days === 1 ? 'day' : 'days' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="getStatusVariant(leave.status)">
                                            {{ leave.status }}
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

