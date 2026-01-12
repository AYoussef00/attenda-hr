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
import { Head, router, usePage } from '@inertiajs/vue3';
import { Calendar, Filter, User, CheckCircle2, XCircle, Clock, AlertCircle, UserCheck, X, ThumbsUp, ThumbsDown } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';

const props = defineProps<{
    leaveRequests: Array<{
        id: number;
        employee_name: string;
        employee_code: string;
        leave_type: string;
        start_date: string;
        end_date: string;
        start_date_formatted: string;
        end_date_formatted: string;
        days: number;
        status: string;
        approved_by: {
            name: string;
            email: string;
        } | null;
        note: string | null;
        created_at: string;
        created_at_formatted: string;
    }>;
    employees: Array<{
        id: number;
        name: string;
        employee_code: string;
    }>;
    leaveTypes: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        status: string | null;
        employee_id: string | null;
        leave_type_id: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Leaves',
        href: '/company/leaves',
    },
];

const localFilters = ref({
    status: props.filters.status || '',
    employee_id: props.filters.employee_id || '',
    leave_type_id: props.filters.leave_type_id || '',
});

const applyFilters = () => {
    router.get('/company/leaves', localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    localFilters.value = {
        status: '',
        employee_id: '',
        leave_type_id: '',
    };
    applyFilters();
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return { variant: 'default', class: 'bg-green-600 hover:bg-green-700 text-white border-0' };
        case 'pending':
            return { variant: 'secondary', class: 'bg-yellow-600 hover:bg-yellow-700 text-white border-0' };
        case 'rejected':
            return { variant: 'destructive', class: 'bg-red-600 hover:bg-red-700 text-white border-0' };
        default:
            return { variant: 'outline', class: '' };
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'approved':
            return CheckCircle2;
        case 'pending':
            return Clock;
        case 'rejected':
            return XCircle;
        default:
            return AlertCircle;
    }
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

// Approve leave request
const approveLeave = (id: number) => {
    if (confirm('Are you sure you want to approve this leave request?')) {
        router.post(`/company/leaves/${id}/approve`, {}, {
            preserveScroll: true,
        });
    }
};

// Reject leave request
const rejectNote = ref('');
const showRejectModal = ref(false);
const selectedLeaveId = ref<number | null>(null);

const openRejectModal = (id: number) => {
    selectedLeaveId.value = id;
    rejectNote.value = '';
    showRejectModal.value = true;
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    selectedLeaveId.value = null;
    rejectNote.value = '';
};

const rejectLeave = () => {
    if (selectedLeaveId.value) {
        router.post(`/company/leaves/${selectedLeaveId.value}/reject`, {
            note: rejectNote.value || null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closeRejectModal();
            },
        });
    }
};
</script>

<template>
    <Head title="Attenda - Leave Requests Management | Employee Leave Tracking">
        <meta name="description" content="إدارة طلبات الإجازات في Attenda. عرض ومراجعة طلبات إجازات الموظفين، الموافقة أو الرفض بسهولة." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success Message -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <UserCheck class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>

            <!-- Error Message -->
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
                                <Calendar class="h-5 w-5" />
                                Leave Requests
                            </CardTitle>
                            <CardDescription>
                                View and manage employee leave requests
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 rounded-lg border p-4">
                        <div class="flex items-center gap-2 mb-4">
                            <Filter class="h-4 w-4" />
                            <h3 class="font-semibold">Filters</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <!-- Status Filter -->
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="localFilters.status"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    @change="applyFilters"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
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

                            <!-- Leave Type Filter -->
                            <div class="grid gap-2">
                                <Label for="leave_type_id">Leave Type</Label>
                                <select
                                    id="leave_type_id"
                                    v-model="localFilters.leave_type_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    @change="applyFilters"
                                >
                                    <option value="">All Leave Types</option>
                                    <option
                                        v-for="leaveType in leaveTypes"
                                        :key="leaveType.id"
                                        :value="leaveType.id"
                                    >
                                        {{ leaveType.name }}
                                    </option>
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

                    <!-- Leave Requests Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Employee
                                    </th>
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
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Approved By
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Note
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="leaveRequests.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No leave requests found
                                    </td>
                                </tr>
                                <tr
                                    v-for="request in leaveRequests"
                                    :key="request.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ request.employee_name }}</span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ request.employee_code }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ request.leave_type }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="h-4 w-4" />
                                                {{ request.start_date }}
                                            </div>
                                            <span class="text-xs text-muted-foreground mt-1">
                                                {{ request.start_date_formatted }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="h-4 w-4" />
                                                {{ request.end_date }}
                                            </div>
                                            <span class="text-xs text-muted-foreground mt-1">
                                                {{ request.end_date_formatted }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ request.days }} day{{ request.days !== 1 ? 's' : '' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :class="getStatusVariant(request.status).class">
                                            <component
                                                :is="getStatusIcon(request.status)"
                                                class="h-3 w-3 mr-1"
                                            />
                                            {{ request.status === 'approved' ? 'Approved' : request.status === 'rejected' ? 'Rejected' : 'Pending' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <span v-if="request.approved_by">
                                            {{ request.approved_by.name }}
                                        </span>
                                        <span v-else>N/A</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <span v-if="request.note" class="truncate max-w-xs block" :title="request.note">
                                            {{ request.note }}
                                        </span>
                                        <span v-else>N/A</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            v-if="request.status === 'pending'"
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                variant="default"
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700 text-white"
                                                @click="approveLeave(request.id)"
                                            >
                                                <ThumbsUp class="h-4 w-4 mr-1" />
                                                Approve
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                class="bg-red-600 hover:bg-red-700 text-white"
                                                @click="openRejectModal(request.id)"
                                            >
                                                <ThumbsDown class="h-4 w-4 mr-1" />
                                                Reject
                                            </Button>
                                        </div>
                                        <div v-else class="flex items-center justify-end">
                                            <Badge
                                                :class="getStatusVariant(request.status).class"
                                            >
                                                <component
                                                    :is="getStatusIcon(request.status)"
                                                    class="h-3 w-3 mr-1"
                                                />
                                                {{ request.status === 'approved' ? 'Approved' : 'Rejected' }}
                                            </Badge>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Reject Modal -->
            <div
                v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeRejectModal"
            >
                <Card class="w-full max-w-md">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ThumbsDown class="h-5 w-5 text-red-600" />
                            Reject Leave Request
                        </CardTitle>
                        <CardDescription>
                            Enter a reason for rejection (optional)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="rejectLeave" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="reject_note">Rejection Reason (Optional)</Label>
                                <textarea
                                    id="reject_note"
                                    v-model="rejectNote"
                                    rows="4"
                                    placeholder="Enter a reason for rejecting this leave request..."
                                    class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="closeRejectModal"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    class="bg-red-600 hover:bg-red-700"
                                >
                                    <ThumbsDown class="h-4 w-4 mr-2" />
                                    Reject Request
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </CompanyLayout>
</template>

