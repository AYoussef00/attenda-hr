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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FileText, ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    companies: Array<{
        id: number;
        name: string;
    }>;
    plans: Array<{
        id: number;
        name: string;
        price: string;
        max_employees: number;
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
    {
        title: 'Add New Subscription',
        href: '/system/subscriptions/create',
    },
];

const form = useForm({
    company_id: '',
    plan_id: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    status: 'active',
});

// Calculate end date when plan is selected or start date changes
const calculateEndDate = () => {
    if (form.start_date && form.plan_id) {
        // Default to 1 year from start date
        const startDate = new Date(form.start_date);
        const endDate = new Date(startDate);
        endDate.setFullYear(endDate.getFullYear() + 1);
        form.end_date = endDate.toISOString().split('T')[0];
    }
};

const selectedPlan = computed(() => {
    return props.plans.find(plan => plan.id === Number(form.plan_id));
});

const submit = () => {
    form.post('/system/subscriptions', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Attenda - Add New Subscription | Create Company Subscription">
        <meta name="description" content="إنشاء اشتراك جديد في Attenda. إضافة اشتراك لشركة مع تحديد الخطة، تاريخ البدء، وفترة الاشتراك." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Add New Subscription
                            </CardTitle>
                            <CardDescription>
                                Create a new subscription for a company
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/system/subscriptions">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Subscriptions
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Company -->
                            <div class="grid gap-2">
                                <Label for="company_id">
                                    Company <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="company_id"
                                    v-model="form.company_id"
                                    name="company_id"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.company_id }"
                                >
                                    <option value="">Select a company</option>
                                    <option
                                        v-for="company in companies"
                                        :key="company.id"
                                        :value="company.id"
                                    >
                                        {{ company.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.company_id" />
                            </div>

                            <!-- Plan -->
                            <div class="grid gap-2">
                                <Label for="plan_id">
                                    Plan <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="plan_id"
                                    v-model="form.plan_id"
                                    name="plan_id"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.plan_id }"
                                    @change="calculateEndDate"
                                >
                                    <option value="">Select a plan</option>
                                    <option
                                        v-for="plan in plans"
                                        :key="plan.id"
                                        :value="plan.id"
                                    >
                                        {{ plan.name }} - ${{ plan.price }} (Max {{ plan.max_employees }} employees)
                                    </option>
                                </select>
                                <InputError :message="form.errors.plan_id" />
                                <p
                                    v-if="selectedPlan"
                                    class="text-xs text-muted-foreground"
                                >
                                    Plan Price: ${{ selectedPlan.price }} | Max Employees: {{ selectedPlan.max_employees }}
                                </p>
                            </div>

                            <!-- Start Date -->
                            <div class="grid gap-2">
                                <Label for="start_date">
                                    Start Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    name="start_date"
                                    required
                                    :class="{ 'border-destructive': form.errors.start_date }"
                                    @change="calculateEndDate"
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>

                            <!-- End Date -->
                            <div class="grid gap-2">
                                <Label for="end_date">
                                    End Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    name="end_date"
                                    required
                                    :class="{ 'border-destructive': form.errors.end_date }"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>

                            <!-- Status -->
                            <div class="grid gap-2">
                                <Label for="status">
                                    Status <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    name="status"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.status }"
                                >
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/system/subscriptions">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Subscription</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

