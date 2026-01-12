<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CreditCard, Plus, Edit, Trash2, Users, CheckCircle2, XCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    plans: Array<{
        id: number;
        name: string;
        price: string;
        max_employees: number;
        features: string[];
        subscriptions_count: number;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Plans',
        href: '/system/plans',
    },
];

// Delete plan
const deletePlan = (id: number) => {
    if (confirm('Are you sure you want to delete this plan?')) {
        router.delete(`/system/plans/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Subscription Plans | Pricing Plans Management">
        <meta name="description" content="إدارة خطط الاشتراك في Attenda. إنشاء وتعديل خطط الاشتراك، الأسعار، والمميزات المتاحة لكل خطة." />
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
                                <CreditCard class="h-5 w-5" />
                                Subscription Plans
                            </CardTitle>
                            <CardDescription>
                                Manage subscription plans for companies
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/system/plans/create">
                                <Plus class="h-4 w-4" />
                                Add New Plan
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
                                        Plan Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Price
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Max Employees
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Features
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Subscriptions
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Created At
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="plans.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No plans found
                                    </td>
                                </tr>
                                <tr
                                    v-for="plan in plans"
                                    :key="plan.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ plan.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        ${{ plan.price }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4" />
                                            {{ plan.max_employees }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div
                                            v-if="plan.features && plan.features.length > 0"
                                            class="flex flex-wrap gap-1"
                                        >
                                            <span
                                                v-for="(feature, index) in plan.features"
                                                :key="index"
                                                class="text-xs bg-muted px-2 py-1 rounded"
                                            >
                                                {{ feature }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground">No features</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ plan.subscriptions_count }} subscription{{ plan.subscriptions_count !== 1 ? 's' : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ plan.created_at }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/system/plans/${plan.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deletePlan(plan.id)"
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

