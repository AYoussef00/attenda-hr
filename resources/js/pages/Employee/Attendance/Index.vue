<script setup lang="ts">
import EmployeeLayout from '@/layouts/employee/EmployeeLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Clock,
    LogIn,
    LogOut,
    Calendar,
    Filter,
    CheckCircle2,
    XCircle,
    AlertCircle,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const props = defineProps<{
    employee: {
        id: number;
        employee_code: string;
        name: string;
        position: string | null;
        department: string;
        shift: string;
    };
    attendance_records: Array<{
        date: string;
        check_in: {
            id: number;
            time: string;
            datetime: string;
        } | null;
        check_out: {
            id: number;
            time: string;
            datetime: string;
        } | null;
        duration: number | null;
        duration_formatted: string | null;
        records: Array<{
            id: number;
            type: string;
            datetime: string;
            date: string;
            time: string;
            method: string;
            ip_address: string | null;
        }>;
    }>;
    today_status: {
        check_in: {
            id: number;
            time: string;
            datetime: string;
        } | null;
        check_out: {
            id: number;
            time: string;
            datetime: string;
        } | null;
        can_check_in: boolean;
        can_check_out: boolean;
    };
    filters: {
        date: string | null;
        start_date: string | null;
        end_date: string | null;
        type: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'My Attendance',
        href: '/company/employee/attendance',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const filterForm = useForm({
    date: props.filters.date || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    type: props.filters.type || '',
});

const attendanceForm = useForm({
    type: '',
    datetime: '',
});

const applyFilters = () => {
    filterForm.get('/company/employee/attendance', {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.reset();
    filterForm.get('/company/employee/attendance', {
        preserveState: true,
        preserveScroll: true,
    });
};

const checkIn = () => {
    // Get current time from device (local time)
    const now = new Date();
    // Format as YYYY-MM-DD HH:mm:ss in local timezone
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const localDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    
    attendanceForm.type = 'in';
    attendanceForm.datetime = localDateTime;
    attendanceForm.post('/company/employee/attendance', {
        preserveState: false,
        preserveScroll: false,
    });
};

const checkOut = () => {
    // Get current time from device (local time)
    const now = new Date();
    // Format as YYYY-MM-DD HH:mm:ss in local timezone
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const localDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    
    attendanceForm.type = 'out';
    attendanceForm.datetime = localDateTime;
    attendanceForm.post('/company/employee/attendance', {
        preserveState: false,
        preserveScroll: false,
    });
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Attenda - My Attendance | Track My Work Hours">
        <meta name="description" content="حضوري في Attenda. عرض سجل الحضور والانصراف، ساعات العمل، التأخيرات، والإجازات." />
    </Head>

    <EmployeeLayout :breadcrumbs="breadcrumbs">
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

            <!-- Employee Info -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                {{ employee.name }}
                            </CardTitle>
                            <CardDescription>
                                {{ employee.employee_code }} | {{ employee.position || 'N/A' }} | {{ employee.department }} | {{ employee.shift }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Check In/Out Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Today's Attendance
                    </CardTitle>
                    <CardDescription>
                        Check in and check out for today
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Check In -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold flex items-center gap-2">
                                        <LogIn class="h-4 w-4 text-green-600" />
                                        Check In
                                    </h3>
                                    <p
                                        v-if="today_status.check_in"
                                        class="text-sm text-muted-foreground mt-1"
                                    >
                                        Last check in: {{ today_status.check_in.time }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-muted-foreground mt-1"
                                    >
                                        Not checked in yet
                                    </p>
                                </div>
                                <Button
                                    @click="checkIn"
                                    :disabled="!today_status.can_check_in || attendanceForm.processing"
                                    class="bg-green-600 hover:bg-green-700"
                                >
                                    <LogIn class="h-4 w-4 mr-2" />
                                    Check In
                                </Button>
                            </div>
                        </div>

                        <!-- Check Out -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold flex items-center gap-2">
                                        <LogOut class="h-4 w-4 text-blue-600" />
                                        Check Out
                                    </h3>
                                    <p
                                        v-if="today_status.check_out"
                                        class="text-sm text-muted-foreground mt-1"
                                    >
                                        Last check out: {{ today_status.check_out.time }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-sm text-muted-foreground mt-1"
                                    >
                                        Not checked out yet
                                    </p>
                                </div>
                                <Button
                                    @click="checkOut"
                                    :disabled="!today_status.can_check_out || attendanceForm.processing"
                                    variant="default"
                                    class="bg-blue-600 hover:bg-blue-700"
                                >
                                    <LogOut class="h-4 w-4 mr-2" />
                                    Check Out
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Date Filter -->
                            <div class="grid gap-2">
                                <Label for="date">Date</Label>
                                <Input
                                    id="date"
                                    v-model="filterForm.date"
                                    type="date"
                                    name="date"
                                />
                            </div>

                            <!-- Start Date -->
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    v-model="filterForm.start_date"
                                    type="date"
                                    name="start_date"
                                />
                            </div>

                            <!-- End Date -->
                            <div class="grid gap-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    v-model="filterForm.end_date"
                                    type="date"
                                    name="end_date"
                                />
                            </div>

                            <!-- Type Filter -->
                            <div class="grid gap-2">
                                <Label for="type">Type</Label>
                                <select
                                    id="type"
                                    v-model="filterForm.type"
                                    name="type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="in">Check In</option>
                                    <option value="out">Check Out</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                :disabled="filterForm.processing"
                            >
                                Apply Filters
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="clearFilters"
                            >
                                Clear Filters
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Attendance Records Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Attendance Records
                    </CardTitle>
                    <CardDescription>
                        Your attendance history
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Check In
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Check Out
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Duration
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Method
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="attendance_records.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No attendance records found
                                    </td>
                                </tr>
                                <tr
                                    v-for="record in attendance_records"
                                    :key="record.date"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        <div class="flex flex-col">
                                            <span>{{ formatDate(record.date) }}</span>
                                            <span class="text-xs text-muted-foreground">{{ record.date }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="record.check_in"
                                            class="flex items-center gap-2"
                                        >
                                            <Badge
                                                class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white border-0"
                                            >
                                                <LogIn class="h-3 w-3" />
                                                {{ record.check_in.time }}
                                            </Badge>
                                        </div>
                                        <span
                                            v-else
                                            class="text-sm text-muted-foreground"
                                        >
                                            -
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="record.check_out"
                                            class="flex items-center gap-2"
                                        >
                                            <Badge
                                                class="flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white border-0"
                                            >
                                                <LogOut class="h-3 w-3" />
                                                {{ record.check_out.time }}
                                            </Badge>
                                        </div>
                                        <span
                                            v-else
                                            class="text-sm text-muted-foreground"
                                        >
                                            -
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="record.duration_formatted"
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            {{ record.duration_formatted }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-sm text-muted-foreground"
                                        >
                                            -
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground uppercase">
                                        <span
                                            v-if="record.check_in"
                                        >
                                            {{ record.check_in.method || 'manual' }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                v-for="rec in record.records"
                                                :key="rec.id"
                                                class="text-xs"
                                            >
                                                {{ rec.type === 'in' ? 'IN' : 'OUT' }}: {{ rec.time }}
                                                <span
                                                    v-if="rec.ip_address"
                                                    class="text-muted-foreground"
                                                >
                                                    ({{ rec.ip_address }})
                                                </span>
                                            </span>
                                        </div>
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

