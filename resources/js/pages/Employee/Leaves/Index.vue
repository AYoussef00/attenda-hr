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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    Filter,
    User,
    CheckCircle2,
    XCircle,
    Clock,
    FileText,
    Plus,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    employee: {
        id: number;
        employee_code: string;
        name: string;
        position: string | null;
        department: string;
        shift: string;
    };
    leave_requests: Array<{
        id: number;
        leave_type: string;
        start_date: string;
        end_date: string;
        start_date_formatted: string;
        end_date_formatted: string;
        days: number;
        status: string;
        note: string | null;
        approved_by: {
            name: string;
            email: string;
        } | null;
        created_at: string;
        created_at_formatted: string;
    }>;
    stats: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    filters: {
        status: string | null;
    };
    leave_balances?: Array<{
        key: string;
        name: string;
        total: number;
        used: number;
        remaining: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'My Leaves',
        href: '/company/employee/leaves',
    },
];

const page = usePage();

const filterForm = useForm({
    status: props.filters.status || '',
});

const applyFilters = () => {
    filterForm.get('/company/employee/leaves', {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.reset();
    filterForm.get('/company/employee/leaves', {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return { variant: 'default', class: 'bg-green-600 hover:bg-green-700 text-white border-0' };
        case 'rejected':
            return { variant: 'destructive', class: 'bg-red-600 hover:bg-red-700 text-white border-0' };
        case 'pending':
            return { variant: 'secondary', class: 'bg-yellow-600 hover:bg-yellow-700 text-white border-0' };
        default:
            return { variant: 'outline', class: '' };
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'approved':
            return 'Approved';
        case 'rejected':
            return 'Rejected';
        case 'pending':
            return 'Pending';
        default:
            return status;
    }
};

const getBalancePercent = (balance: { total: number; remaining: number }) => {
    if (!balance.total || balance.total <= 0) return 0;
    const pct = (balance.remaining / balance.total) * 100;
    return Math.max(0, Math.min(100, Math.round(pct)));
};
</script>

<template>
    <Head title="Attenda - My Leaves | Leave Requests & History">
        <meta name="description" content="إجازاتي في Attenda. عرض طلبات الإجازات، حالة الطلبات، رصيد الإجازات المتبقي، وتاريخ الإجازات." />
    </Head>

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success/Error Messages -->
            <Alert
                v-if="page.props.flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <CheckCircle2 class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ page.props.flash.success }}</AlertDescription>
            </Alert>

            <Alert
                v-if="page.props.flash?.error"
                variant="destructive"
            >
                <XCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ page.props.flash.error }}</AlertDescription>
            </Alert>

            <!-- Digital leave balance circles only -->
            <Card v-if="leave_balances && leave_balances.length">
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium">
                        Leave Balances
                            </CardTitle>
                            <CardDescription>
                        Annual &amp; Sick leave remaining for this year
                            </CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div
                            v-for="balance in leave_balances"
                            :key="balance.key"
                            class="flex flex-col items-center justify-center text-center"
                        >
                            <div
                                class="relative flex items-center justify-center h-24 w-24 rounded-full bg-slate-100 shadow-md"
                            >
                                <div
                                    class="absolute inset-2 rounded-full bg-transparent"
                                    :style="{
                                        background: `conic-gradient(#22c55e ${getBalancePercent(balance)}%, #e5e7eb 0)`,
                                    }"
                                ></div>
                                <div
                                    class="relative flex h-18 w-18 items-center justify-center rounded-full bg-white text-center"
                                >
                                    <span class="text-2xl font-semibold text-slate-900">
                                        {{ balance.remaining }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <p class="text-xs font-semibold text-slate-900">
                                    {{ balance.name }}
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Used {{ balance.used }} of {{ balance.total }} days
                                </p>
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
                            <!-- Status Filter -->
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="filterForm.status"
                                    name="status"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
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

            <!-- Leave Requests Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                My Leave Requests
                            </CardTitle>
                            <CardDescription>
                                Your leave request history
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/company/employee/leaves/create">
                                <Plus class="h-4 w-4 mr-2" />
                                Add New Leave Request
                            </Link>
                        </Button>
                    </div>
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
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Approved By
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Note
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Created At
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="leave_requests.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No leave requests found
                                    </td>
                                </tr>
                                <tr
                                    v-for="request in leave_requests"
                                    :key="request.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ request.leave_type }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ request.start_date_formatted }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ request.end_date_formatted }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ request.days }} {{ request.days === 1 ? 'day' : 'days' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :class="getStatusVariant(request.status).class"
                                        >
                                            {{ getStatusLabel(request.status) }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <span v-if="request.approved_by">
                                            {{ request.approved_by.name }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <span v-if="request.note" class="truncate max-w-xs block" :title="request.note">
                                            {{ request.note }}
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ request.created_at_formatted }}
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

