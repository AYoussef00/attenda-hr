<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Building2,
    Users,
    Shield,
    CreditCard,
    Clock,
    Activity,
    TrendingUp,
    BarChart3,
    Phone,
    Mail,
    DollarSign,
} from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        total_companies: number;
        active_subscriptions: number;
        total_employees: number;
        total_admin_users: number;
        pending_subscriptions: number;
        total_income: number;
        monthly_income: number;
        annual_income: number;
        subscription_chart: {
            active: number;
            expired: number;
            cancelled: number;
        };
        recent_companies: Array<{
            id: number;
            name: string;
            email: string | null;
            phone: string | null;
            employee_count: number;
            subscription_status: string;
            plan_name: string | null;
        }>;
        recent_subscriptions: Array<{
            id: number;
            company_name: string;
            plan_name: string;
            start_date: string;
            end_date: string;
            status: string;
        }>;
        employee_distribution: Array<{
            company_name: string;
            employee_count: number;
        }>;
        subscription_stats_by_plan: Array<{
            plan_name: string;
            subscription_count: number;
        }>;
        activity_stats: Array<{
            date: string;
            activity_count: number;
            formatted_date: string;
        }>;
        recent_activity: Array<{
            id: number;
            action: string;
            model: string | null;
            user: { name: string; email: string } | null;
            company: { name: string } | null;
            created_at: string;
        }>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
];

// Calculate chart percentages
const totalChartValue = computed(() => {
    return (
        props.stats.subscription_chart.active +
        props.stats.subscription_chart.expired +
        props.stats.subscription_chart.cancelled
    );
});

const activePercentage = computed(() => {
    if (totalChartValue.value === 0) return 0;
    return Math.round(
        (props.stats.subscription_chart.active / totalChartValue.value) * 100
    );
});

const expiredPercentage = computed(() => {
    if (totalChartValue.value === 0) return 0;
    return Math.round(
        (props.stats.subscription_chart.expired / totalChartValue.value) * 100
    );
});

const cancelledPercentage = computed(() => {
    if (totalChartValue.value === 0) return 0;
    return Math.round(
        (props.stats.subscription_chart.cancelled / totalChartValue.value) * 100
    );
});

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'expired':
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

// Format date
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Calculate max values for charts
const maxEmployeeCount = computed(() => {
    if (props.stats.employee_distribution.length === 0) return 1;
    return Math.max(...props.stats.employee_distribution.map((item) => item.employee_count));
});

const maxSubscriptionCount = computed(() => {
    if (props.stats.subscription_stats_by_plan.length === 0) return 1;
    return Math.max(...props.stats.subscription_stats_by_plan.map((item) => item.subscription_count));
});

const maxActivityCount = computed(() => {
    if (props.stats.activity_stats.length === 0) return 1;
    return Math.max(...props.stats.activity_stats.map((item) => item.activity_count));
});

// Calculate bar width percentage
const getBarWidth = (value: number, max: number) => {
    if (max === 0) return 0;
    return Math.round((value / max) * 100);
};
</script>

<template>
    <Head title="Attenda - Admin Dashboard | System Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Total Companies -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Companies
                        </CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_companies }}</div>
                        <CardDescription>
                            All registered companies
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Active Subscriptions -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Active Subscriptions
                        </CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active_subscriptions }}</div>
                        <CardDescription>
                            Currently active subscriptions
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Total Employees -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Employees
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_employees }}</div>
                        <CardDescription>
                            Across all companies
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Total Admin Users -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Admin Users
                        </CardTitle>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_admin_users }}</div>
                        <CardDescription>
                            Admin and HR users
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Pending Subscriptions -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Pending Subscriptions
                        </CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_subscriptions }}</div>
                        <CardDescription>
                            Awaiting activation
                        </CardDescription>
                    </CardContent>
                </Card>

                <!-- Total Income -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Income
                        </CardTitle>
                        <DollarSign class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            ${{ stats.total_income.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                        <CardDescription>
                            From active subscriptions
                        </CardDescription>
                        <div class="mt-2 flex gap-4 text-xs text-muted-foreground">
                            <span>
                                Monthly: ${{ stats.monthly_income.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </span>
                            <span>
                                Annual: ${{ stats.annual_income.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Companies Overview -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Building2 class="h-5 w-5" />
                        Companies Overview
                    </CardTitle>
                    <CardDescription>
                        Recent registered companies and their details
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Company Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Email
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Phone
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Employees
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Subscription Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="stats.recent_companies.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No companies found
                                    </td>
                                </tr>
                                <tr
                                    v-for="company in stats.recent_companies"
                                    :key="company.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ company.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Mail class="h-4 w-4" />
                                            {{ company.email || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Phone class="h-4 w-4" />
                                            {{ company.phone || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ company.employee_count }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="getStatusVariant(company.subscription_status)">
                                            {{ company.subscription_status }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Subscriptions Overview -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            Subscriptions Chart
                        </CardTitle>
                        <CardDescription>
                            Active vs Expired vs Cancelled subscriptions
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- Chart Bars -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Active</span>
                                    <span class="font-medium">{{ activePercentage }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-green-500 transition-all"
                                        :style="{ width: `${activePercentage}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Expired</span>
                                    <span class="font-medium">{{ expiredPercentage }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-red-500 transition-all"
                                        :style="{ width: `${expiredPercentage}%` }"
                                    ></div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Cancelled</span>
                                    <span class="font-medium">{{ cancelledPercentage }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-gray-500 transition-all"
                                        :style="{ width: `${cancelledPercentage}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Chart Legend -->
                            <div class="mt-4 grid grid-cols-3 gap-4 text-center text-xs text-muted-foreground">
                                <div>
                                    <div class="font-medium text-foreground">
                                        {{ stats.subscription_chart.active }}
                                    </div>
                                    <div>Active</div>
                                </div>
                                <div>
                                    <div class="font-medium text-foreground">
                                        {{ stats.subscription_chart.expired }}
                                    </div>
                                    <div>Expired</div>
                                </div>
                                <div>
                                    <div class="font-medium text-foreground">
                                        {{ stats.subscription_chart.cancelled }}
                                    </div>
                                    <div>Cancelled</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Subscriptions Table -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CreditCard class="h-5 w-5" />
                            Subscriptions Table
                        </CardTitle>
                        <CardDescription>
                            Recent subscription details
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                            <table class="w-full">
                                <thead class="sticky top-0 bg-background">
                                    <tr class="border-b">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">
                                            Company
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">
                                            Plan
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">
                                            Start
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">
                                            End
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-if="stats.recent_subscriptions.length === 0"
                                        class="border-b"
                                    >
                                        <td colspan="5" class="px-4 py-8 text-center text-xs text-muted-foreground">
                                            No subscriptions found
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="subscription in stats.recent_subscriptions"
                                        :key="subscription.id"
                                        class="border-b hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-2 text-xs font-medium">
                                            {{ subscription.company_name }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-muted-foreground">
                                            {{ subscription.plan_name }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-muted-foreground">
                                            {{ formatDate(subscription.start_date) }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-muted-foreground">
                                            {{ formatDate(subscription.end_date) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <Badge :variant="getStatusVariant(subscription.status)" class="text-xs">
                                                {{ subscription.status }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts & Graphs -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Employee Distribution Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Employee Distribution
                        </CardTitle>
                        <CardDescription>
                            Number of employees per company
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-if="stats.employee_distribution.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No data available
                            </div>
                            <div
                                v-for="(item, index) in stats.employee_distribution"
                                :key="index"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="truncate pr-2">{{ item.company_name }}</span>
                                    <span class="font-medium">{{ item.employee_count }}</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-blue-500 transition-all"
                                        :style="{ width: `${getBarWidth(item.employee_count, maxEmployeeCount)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Subscription Stats by Plan -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CreditCard class="h-5 w-5" />
                            Subscription Stats
                        </CardTitle>
                        <CardDescription>
                            Subscriptions by plan type
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-if="stats.subscription_stats_by_plan.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No data available
                            </div>
                            <div
                                v-for="(item, index) in stats.subscription_stats_by_plan"
                                :key="index"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="truncate pr-2">{{ item.plan_name }}</span>
                                    <span class="font-medium">{{ item.subscription_count }}</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-purple-500 transition-all"
                                        :style="{ width: `${getBarWidth(item.subscription_count, maxSubscriptionCount)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Activity Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Activity Stats
                        </CardTitle>
                        <CardDescription>
                            Daily activities (Last 7 days)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-if="stats.activity_stats.length === 0"
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                No data available
                            </div>
                            <div
                                v-for="(item, index) in stats.activity_stats"
                                :key="index"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">{{ item.formatted_date }}</span>
                                    <span class="font-medium">{{ item.activity_count }}</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-green-500 transition-all"
                                        :style="{ width: `${getBarWidth(item.activity_count, maxActivityCount)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Recent Activity
                    </CardTitle>
                    <CardDescription>
                        Latest system activities and logs
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-if="stats.recent_activity.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            No recent activity
                        </div>
                        <div
                            v-for="activity in stats.recent_activity"
                            :key="activity.id"
                            class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0"
                        >
                            <div class="flex flex-col gap-1">
                                <div class="text-sm font-medium">
                                    {{ activity.action }}
                                    <span v-if="activity.model" class="text-muted-foreground">
                                        on {{ activity.model }}
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    <span v-if="activity.user">
                                        by {{ activity.user.name }}
                                    </span>
                                    <span v-if="activity.company">
                                        in {{ activity.company.name }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ activity.created_at }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
