<script setup lang="ts">
import CompanyLayout from '@/layouts/company/CompanyLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Wrench, Plus, ArrowLeft, UserCheck, XCircle, Package, DollarSign, Calendar } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    maintenance: Array<{
        id: number;
        asset_id: number;
        asset_code: string;
        asset_type: string;
        asset_model: string;
        maintenance_type: string;
        problem_description: string;
        cost: string;
        vendor: string | null;
        start_date: string;
        completion_date: string | null;
        status: string;
    }>;
    filters: {
        status?: string;
        maintenance_type?: string;
        asset_id?: string;
    };
    assets: Array<{
        id: number;
        asset_code: string;
        type: string;
        model: string;
    }>;
    statuses: string[];
    maintenance_types: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Assets',
        href: '/company/assets',
    },
    {
        title: 'Maintenance',
        href: '/company/assets/maintenance',
    },
];

const filterForm = useForm({
    status: props.filters.status || '',
    maintenance_type: props.filters.maintenance_type || '',
    asset_id: props.filters.asset_id || '',
});

const applyFilters = () => {
    filterForm.get('/company/assets/maintenance', {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'Completed':
            return 'default';
        case 'In_Progress':
            return 'secondary';
        case 'Open':
            return 'outline';
        default:
            return 'outline';
    }
};

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Asset Maintenance" />

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
                                <Wrench class="h-5 w-5" />
                                Maintenance Tickets
                            </CardTitle>
                            <CardDescription>
                                Manage asset maintenance records
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" as-child>
                                <Link href="/company/assets">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back to Assets
                                </Link>
                            </Button>
                            <Button as-child>
                                <Link href="/company/assets/maintenance/create">
                                    <Plus class="h-4 w-4" />
                                    Create Ticket
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 rounded-lg border bg-gray-50 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="space-y-2">
                                <Label>Status</Label>
                                <select
                                    v-model="filterForm.status"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option
                                        v-for="status in statuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status.replace('_', ' ') }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Type</Label>
                                <select
                                    v-model="filterForm.maintenance_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                >
                                    <option value="">All Types</option>
                                    <option
                                        v-for="type in maintenance_types"
                                        :key="type"
                                        :value="type"
                                    >
                                        {{ type }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Asset</Label>
                                <select
                                    v-model="filterForm.asset_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                >
                                    <option value="">All Assets</option>
                                    <option
                                        v-for="asset in assets"
                                        :key="asset.id"
                                        :value="asset.id.toString()"
                                    >
                                        {{ asset.asset_code }} - {{ asset.type }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <Button
                            size="sm"
                            class="mt-4"
                            @click="applyFilters"
                            :disabled="filterForm.processing"
                        >
                            Apply Filters
                        </Button>
                    </div>

                    <!-- Maintenance Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Asset
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Problem
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Cost
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Start Date
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
                                    v-if="maintenance.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No maintenance tickets found
                                    </td>
                                </tr>
                                <tr
                                    v-for="ticket in maintenance"
                                    :key="ticket.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">{{ ticket.asset_code }}</p>
                                            <p class="text-xs text-muted-foreground">{{ ticket.asset_type }} - {{ ticket.asset_model }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ ticket.maintenance_type }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground max-w-xs truncate">
                                        {{ ticket.problem_description }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        ${{ ticket.cost }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ ticket.start_date }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :variant="getStatusVariant(ticket.status)"
                                            class="rounded-full px-2 py-0.5 text-xs"
                                        >
                                            {{ ticket.status.replace('_', ' ') }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/company/assets/maintenance/${ticket.id}/edit`">
                                                    <Wrench class="h-4 w-4" />
                                                </Link>
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
    </CompanyLayout>
</template>

