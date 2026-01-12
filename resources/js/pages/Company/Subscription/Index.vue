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
    CreditCard,
    Calendar,
    Users,
    DollarSign,
    CheckCircle2,
    XCircle,
    Clock,
    AlertCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        logo: string | null;
    };
    subscription: {
        id: number;
        plan_name: string;
        plan_price: string;
        max_employees: number;
        features: string[];
        start_date: string;
        end_date: string;
        start_date_formatted: string;
        end_date_formatted: string;
        status: string;
        days_remaining: number;
        is_active: boolean;
        is_expired: boolean;
        created_at: string;
    } | null;
    subscriptions_history: Array<{
        id: number;
        plan_name: string;
        plan_price: string;
        max_employees: number;
        features: string[];
        start_date: string;
        end_date: string;
        status: string;
        days_remaining: number;
        is_active: boolean;
        is_expired: boolean;
        created_at: string;
    }>;
    current_employees_count: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Subscription',
        href: '/company/subscription',
    },
];

const getStatusVariant = (status: string, isActive: boolean, isExpired: boolean) => {
    if (isExpired) {
        return 'destructive';
    }
    if (isActive) {
        return 'default';
    }
    switch (status) {
        case 'active':
            return 'default';
        case 'expired':
            return 'destructive';
        case 'cancelled':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getStatusText = (status: string, isActive: boolean, isExpired: boolean) => {
    if (isExpired) {
        return 'Expired';
    }
    if (isActive) {
        return 'Active';
    }
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const remainingEmployees = computed(() => {
    if (!props.subscription) {
        return null;
    }
    return props.subscription.max_employees - props.current_employees_count;
});
</script>

<template>
    <Head title="Subscription" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Company Info -->
            <Card>
                <CardHeader>
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
                                Subscription Details
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Active Subscription -->
            <div v-if="subscription">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <CreditCard class="h-5 w-5" />
                                    Current Subscription
                                </CardTitle>
                                <CardDescription>
                                    Your active subscription plan details
                                </CardDescription>
                            </div>
                            <Badge
                                :variant="getStatusVariant(subscription.status, subscription.is_active, subscription.is_expired)"
                                class="text-sm"
                            >
                                <CheckCircle2
                                    v-if="subscription.is_active"
                                    class="h-3 w-3 mr-1"
                                />
                                <XCircle
                                    v-else-if="subscription.is_expired"
                                    class="h-3 w-3 mr-1"
                                />
                                {{ getStatusText(subscription.status, subscription.is_active, subscription.is_expired) }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Plan Name -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground">Plan Name</div>
                                <div class="text-xl font-semibold">{{ subscription.plan_name }}</div>
                            </div>

                            <!-- Price -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground flex items-center gap-1">
                                    <DollarSign class="h-4 w-4" />
                                    Monthly Price
                                </div>
                                <div class="text-xl font-semibold">${{ subscription.plan_price }}</div>
                            </div>

                            <!-- Max Employees -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Users class="h-4 w-4" />
                                    Max Employees
                                </div>
                                <div class="text-xl font-semibold">
                                    {{ subscription.max_employees }}
                                    <span class="text-sm text-muted-foreground font-normal">
                                        (Current: {{ current_employees_count }})
                                    </span>
                                </div>
                                <div
                                    v-if="remainingEmployees !== null && remainingEmployees >= 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ remainingEmployees }} employee{{ remainingEmployees === 1 ? '' : 's' }} remaining
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    Start Date
                                </div>
                                <div class="text-lg font-semibold">{{ subscription.start_date_formatted }}</div>
                                <div class="text-xs text-muted-foreground">{{ subscription.start_date }}</div>
                            </div>

                            <!-- End Date -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    End Date
                                </div>
                                <div class="text-lg font-semibold">{{ subscription.end_date_formatted }}</div>
                                <div class="text-xs text-muted-foreground">{{ subscription.end_date }}</div>
                            </div>

                            <!-- Days Remaining -->
                            <div class="space-y-2">
                                <div class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-4 w-4" />
                                    Days Remaining
                                </div>
                                <div
                                    class="text-lg font-semibold"
                                    :class="{
                                        'text-green-600': subscription.days_remaining > 30,
                                        'text-orange-600': subscription.days_remaining > 0 && subscription.days_remaining <= 30,
                                        'text-red-600': subscription.days_remaining <= 0,
                                    }"
                                >
                                    {{ subscription.days_remaining > 0 ? subscription.days_remaining : 'Expired' }}
                                    <span class="text-xs font-normal text-muted-foreground">
                                        {{ subscription.days_remaining > 0 ? 'days' : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div
                            v-if="subscription.features && subscription.features.length > 0"
                            class="mt-6 pt-6 border-t"
                        >
                            <h3 class="text-sm font-semibold mb-3">Plan Features</h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <li
                                    v-for="(feature, index) in subscription.features"
                                    :key="index"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <CheckCircle2 class="h-4 w-4 text-green-600 flex-shrink-0" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- No Subscription -->
            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AlertCircle class="h-5 w-5 text-orange-600" />
                        No Active Subscription
                    </CardTitle>
                    <CardDescription>
                        You don't have an active subscription plan
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-muted-foreground">
                        Please contact support to set up a subscription plan for your company.
                    </p>
                </CardContent>
            </Card>

            <!-- Subscription History -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Subscription History
                    </CardTitle>
                    <CardDescription>
                        All your subscription records
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Plan
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Price
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Max Employees
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Start Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        End Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Days Remaining
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="subscriptions_history.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No subscription history found
                                    </td>
                                </tr>
                                <tr
                                    v-for="sub in subscriptions_history"
                                    :key="sub.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ sub.plan_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        ${{ sub.plan_price }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ sub.max_employees }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ sub.start_date }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ sub.end_date }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <span
                                            :class="{
                                                'text-green-600': sub.days_remaining > 30,
                                                'text-orange-600': sub.days_remaining > 0 && sub.days_remaining <= 30,
                                                'text-red-600': sub.days_remaining <= 0,
                                            }"
                                        >
                                            {{ sub.days_remaining > 0 ? sub.days_remaining : 'Expired' }}
                                            {{ sub.days_remaining > 0 ? 'days' : '' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :variant="getStatusVariant(sub.status, sub.is_active, sub.is_expired)"
                                        >
                                            <CheckCircle2
                                                v-if="sub.is_active"
                                                class="h-3 w-3 mr-1"
                                            />
                                            <XCircle
                                                v-else-if="sub.is_expired"
                                                class="h-3 w-3 mr-1"
                                            />
                                            {{ getStatusText(sub.status, sub.is_active, sub.is_expired) }}
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

