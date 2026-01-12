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
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Plus, Edit, Trash2, Building2, CreditCard, CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    subscriptions: Array<{
        id: number;
        company_name: string;
        company_id: number;
        plan_name: string;
        plan_id: number;
        start_date: string;
        end_date: string;
        status: string;
        price: string;
        billing_period: string;
        max_employees: number;
        created_at: string;
        late_days: number;
        bypass_expired: boolean;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Subscriptions',
        href: '/system/subscriptions',
    },
];

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'expired':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        case 'pending':
            return 'outline';
        default:
            return 'outline';
    }
};

// Delete subscription
const deleteSubscription = (id: number) => {
    if (confirm('Are you sure you want to delete this subscription?')) {
        router.delete(`/system/subscriptions/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

// Approve pending subscription
const approveSubscription = (id: number) => {
    if (confirm('Are you sure you want to approve this subscription and activate the company?')) {
        router.post(`/system/subscriptions/${id}/approve`, {
            onSuccess: () => {
                // flash handled globally
            },
        });
    }
};

// Reject pending subscription
const rejectSubscription = (id: number) => {
    if (confirm('Are you sure you want to reject this subscription?')) {
        router.post(`/system/subscriptions/${id}/reject`, {
            onSuccess: () => {
                // flash handled globally
            },
        });
    }
};

// Toggle bypass for expired subscription
const toggleBypass = (id: number, currentBypass: boolean) => {
    const action = currentBypass ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} access for this company?`)) {
        router.post(`/system/subscriptions/${id}/toggle-bypass`, {
            onSuccess: () => {
                // flash handled globally
            },
        });
    }
};

// Check if subscription is expired
const isExpired = (endDate: string) => {
    return new Date(endDate) < new Date();
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Subscriptions Management | Company Subscriptions">
        <meta name="description" content="إدارة الاشتراكات في Attenda. عرض اشتراكات الشركات، حالة الدفع، تاريخ التجديد، وتحديث الخطط." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success Message -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <CheckCircle2 class="h-4 w-4" />
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
                                <FileText class="h-5 w-5" />
                                Company Subscriptions
                            </CardTitle>
                            <CardDescription>
                                Manage company subscription plans
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/system/subscriptions/create">
                                <Plus class="h-4 w-4" />
                                Add New Subscription
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
                                        Company
                                    </th>
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
                                        Late
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="subscriptions.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No subscriptions found
                                    </td>
                                </tr>
                                <tr
                                    v-for="subscription in subscriptions"
                                    :key="subscription.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 text-muted-foreground" />
                                            {{ subscription.company_name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <CreditCard class="h-4 w-4" />
                                            {{ subscription.plan_name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        ${{ subscription.price }}
                                        <span class="text-xs text-gray-500 ml-1">
                                            / {{ subscription.billing_period === 'yearly' ? 'year' : 'month' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ subscription.max_employees }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ subscription.start_date }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div
                                            class="flex items-center gap-2"
                                            :class="isExpired(subscription.end_date) ? 'text-red-600 font-medium' : 'text-muted-foreground'"
                                        >
                                            {{ subscription.end_date }}
                                            <AlertCircle
                                                v-if="isExpired(subscription.end_date)"
                                                class="h-4 w-4 text-red-600"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span
                                            v-if="subscription.late_days > 0"
                                            class="text-red-600 font-medium"
                                        >
                                            {{ subscription.late_days }} days
                                        </span>
                                        <span v-else class="text-muted-foreground">
                                            -
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="getStatusVariant(subscription.status)">
                                            {{ subscription.status }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Pending actions -->
                                            <template v-if="subscription.status === 'pending'">
                                                <Button
                                                    size="sm"
                                                    variant="default"
                                                    class="bg-green-600 hover:bg-green-700 text-white"
                                                    @click="approveSubscription(subscription.id)"
                                                >
                                                    Accept
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="border-red-500 text-red-600 hover:bg-red-50"
                                                    @click="rejectSubscription(subscription.id)"
                                                >
                                                    Reject
                                                </Button>
                                            </template>

                                            <!-- Activate/Deactivate for expired subscriptions -->
                                            <Button
                                                v-if="isExpired(subscription.end_date)"
                                                size="sm"
                                                :variant="subscription.bypass_expired ? 'outline' : 'default'"
                                                :class="subscription.bypass_expired ? 'border-orange-500 text-orange-600 hover:bg-orange-50' : 'bg-green-600 hover:bg-green-700 text-white'"
                                                @click="toggleBypass(subscription.id, subscription.bypass_expired)"
                                            >
                                                {{ subscription.bypass_expired ? 'Deactivate' : 'Activate' }}
                                            </Button>

                                            <!-- Edit / Delete (for all statuses) -->
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/system/subscriptions/${subscription.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteSubscription(subscription.id)"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

