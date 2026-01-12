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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Clock, Filter, Calendar, LogIn, LogOut, MapPin, Smartphone, Trash2, AlertTriangle, CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const props = defineProps<{
    attendanceRecords: Array<{
        employee_id: number;
        employee_name: string;
        employee_code: string;
        position: string | null;
        date: string;
        check_in_time: string | null;
        check_out_time: string | null;
        duration: string | null;
        status?: string | null;
    }>;
    employees: Array<{
        id: number;
        name: string;
        employee_code: string;
    }>;
    filters: {
        date: string;
        employee_id: string | null;
        type: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Attendance',
        href: '/company/attendance',
    },
];

const localFilters = ref({
    date: props.filters.date || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    employee_id: props.filters.employee_id || '',
    type: props.filters.type || '',
});

const applyFilters = () => {
    // Only send filters if they're actually set
    const filters: Record<string, string> = {};
    if (localFilters.value.date) {
        filters.date = localFilters.value.date;
    }
    if (localFilters.value.start_date) {
        filters.start_date = localFilters.value.start_date;
    }
    if (localFilters.value.end_date) {
        filters.end_date = localFilters.value.end_date;
    }
    if (localFilters.value.employee_id) {
        filters.employee_id = localFilters.value.employee_id;
    }
    if (localFilters.value.type) {
        filters.type = localFilters.value.type;
    }
    
    router.get('/company/attendance', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    localFilters.value = {
        date: '',
        start_date: '',
        end_date: '',
        employee_id: '',
        type: '',
    };
    applyFilters();
};

const getInitials = (name: string) => {
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
};

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const deleteAllRecords = () => {
    if (confirm('Are you sure you want to delete ALL attendance records? This action cannot be undone and will delete all attendance data for all employees.')) {
        router.delete('/company/attendance/delete-all', {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};
</script>

<template>
    <Head title="Attenda - Attendance Management | Track Employee Attendance">
        <meta name="description" content="إدارة الحضور والانصراف في Attenda. تتبع حضور الموظفين، ساعات العمل، والتأخيرات بسهولة." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success/Error Messages -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <CheckCircle2 class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>

            <Alert
                v-if="flash?.error"
                variant="destructive"
            >
                <XCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                Attendance Records
                            </CardTitle>
                            <CardDescription>
                                View and manage employee attendance records
                            </CardDescription>
                        </div>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="deleteAllRecords"
                            v-if="attendanceRecords.length > 0"
                        >
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete All Records
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 rounded-lg border p-4">
                        <div class="flex items-center gap-2 mb-4">
                            <Filter class="h-4 w-4" />
                            <h3 class="font-semibold">Filters</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                            <!-- Date Filter -->
                            <div class="grid gap-2">
                                <Label for="date">Single Date</Label>
                                <Input
                                    id="date"
                                    v-model="localFilters.date"
                                    type="date"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date Range Start -->
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    v-model="localFilters.start_date"
                                    type="date"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Date Range End -->
                            <div class="grid gap-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    v-model="localFilters.end_date"
                                    type="date"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Employee Filter -->
                            <div class="grid gap-2">
                                <Label for="employee_id">Employee</Label>
                                <select
                                    id="employee_id"
                                    v-model="localFilters.employee_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    @change="applyFilters"
                                >
                                    <option value="">All Employees</option>
                                    <option
                                        v-for="employee in employees"
                                        :key="employee.id"
                                        :value="employee.id"
                                    >
                                        {{ employee.name }} ({{ employee.employee_code }})
                                    </option>
                                </select>
                            </div>

                            <!-- Type Filter -->
                            <div class="grid gap-2">
                                <Label for="type">Type</Label>
                                <select
                                    id="type"
                                    v-model="localFilters.type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    @change="applyFilters"
                                >
                                    <option value="">All Types</option>
                                    <option value="in">Check In</option>
                                    <option value="out">Check Out</option>
                                </select>
                            </div>

                            <!-- Clear Filters Button -->
                            <div class="flex items-end">
                                <Button
                                    variant="outline"
                                    @click="clearFilters"
                                >
                                    Clear Filters
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Records Table -->
                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Employee Code
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Position
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Check In
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Check Out
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Late (min)
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Duration
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-if="attendanceRecords.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No attendance records found
                                    </td>
                                </tr>
                                <tr
                                    v-for="record in attendanceRecords"
                                    :key="`${record.employee_id}-${record.date}`"
                                    :class="[
                                        'hover:bg-slate-50 transition-colors',
                                        record.status === 'absent' 
                                            ? 'bg-rose-50/30' 
                                            : record.status === 'partial'
                                            ? 'bg-amber-50/30'
                                            : ''
                                    ]"
                                >
                                    <!-- Date -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-slate-900">
                                                {{ new Date(record.date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ record.date }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Employee Name -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div
                                                :class="[
                                                    'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold',
                                                    record.status === 'absent'
                                                        ? 'bg-rose-100 text-rose-700'
                                                        : record.status === 'partial'
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : 'bg-blue-100 text-blue-700'
                                                ]"
                                            >
                                                {{ getInitials(record.employee_name) }}
                                            </div>
                                            <span class="text-sm font-medium text-slate-900">
                                                {{ record.employee_name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Employee Code -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-slate-600 font-mono">
                                            {{ record.employee_code }}
                                        </span>
                                    </td>

                                    <!-- Position -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-slate-600">
                                            {{ record.position || '—' }}
                                        </span>
                                    </td>

                                    <!-- Check In -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            v-if="record.check_in_time"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md"
                                        >
                                            <LogIn class="h-3 w-3" />
                                            {{ record.check_in_time }}
                                        </span>
                                        <span 
                                            v-else
                                            class="text-sm text-slate-400"
                                        >
                                            —
                                        </span>
                                    </td>

                                    <!-- Check Out -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            v-if="record.check_out_time"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-orange-700 bg-orange-50 px-2 py-1 rounded-md"
                                        >
                                            <LogOut class="h-3 w-3" />
                                            {{ record.check_out_time }}
                                        </span>
                                        <span 
                                            v-else
                                            class="text-sm text-slate-400"
                                        >
                                            —
                                        </span>
                                    </td>

                                    <!-- Late -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            v-if="record.late_minutes || record.late_minutes === 0"
                                            class="text-sm font-medium text-amber-700"
                                        >
                                            {{ record.late_minutes ?? 0 }}m
                                        </span>
                                        <span 
                                            v-else
                                            class="text-sm text-slate-400"
                                        >
                                            —
                                        </span>
                                    </td>

                                    <!-- Duration -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span 
                                            v-if="record.duration"
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            {{ record.duration }}
                                        </span>
                                        <span 
                                            v-else
                                            class="text-sm text-slate-400"
                                        >
                                            —
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <Badge
                                            :variant="record.status === 'present' ? 'default' : record.status === 'partial' ? 'secondary' : 'destructive'"
                                            class="text-xs font-medium capitalize"
                                        >
                                            {{ record.status === 'present' ? 'Present' : record.status === 'partial' ? 'Partial' : 'Absent' }}
                                        </Badge>
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

