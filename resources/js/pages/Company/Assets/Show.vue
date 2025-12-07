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
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Package, ArrowLeft, History, Info, Calendar, DollarSign, Wrench, User } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    asset: {
        id: number;
        asset_code: string;
        type: string;
        model: string;
        serial_number: string | null;
        purchase_date: string;
        cost: string;
        status: string;
        warranty_end: string | null;
        notes: string | null;
        created_at: string;
        updated_at: string;
    };
    history: {
        assignments: Array<{
            id: number;
            employee_name: string;
            assign_date: string;
            return_date: string | null;
            condition_on_assign: string;
            condition_on_return: string | null;
            is_active: boolean;
        }>;
        maintenance: Array<{
            id: number;
            maintenance_type: string;
            problem_description: string;
            cost: string;
            vendor: string | null;
            start_date: string;
            completion_date: string | null;
            status: string;
        }>;
    };
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
        title: props.asset.asset_code,
        href: `/company/assets/${props.asset.id}`,
    },
];

const activeTab = ref('details');

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'Available':
            return 'default';
        case 'Assigned':
            return 'secondary';
        case 'Under_Maintenance':
            return 'outline';
        case 'Damaged':
            return 'destructive';
        case 'Retired':
            return 'secondary';
        default:
            return 'outline';
    }
};

const getStatusLabel = (status: string) => {
    return status.replace('_', ' ');
};
</script>

<template>
    <Head :title="`Asset: ${asset.asset_code}`" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Package class="h-5 w-5" />
                                {{ asset.asset_code }}
                            </CardTitle>
                            <CardDescription>
                                Asset Details and History
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge
                                :variant="getStatusVariant(asset.status)"
                                class="rounded-full px-3 py-1"
                            >
                                {{ getStatusLabel(asset.status) }}
                            </Badge>
                            <Button variant="outline" as-child>
                                <Link href="/company/assets">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back to Assets
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Tab Buttons -->
                    <div class="flex gap-2 mb-6 border-b">
                        <Button
                            variant="ghost"
                            :class="activeTab === 'details' ? 'border-b-2 border-primary' : ''"
                            @click="activeTab = 'details'"
                        >
                            <Info class="h-4 w-4 mr-2" />
                            Details
                        </Button>
                        <Button
                            variant="ghost"
                            :class="activeTab === 'history' ? 'border-b-2 border-primary' : ''"
                            @click="activeTab = 'history'"
                        >
                            <History class="h-4 w-4 mr-2" />
                            History
                        </Button>
                    </div>

                    <!-- Details Tab -->
                    <div v-if="activeTab === 'details'" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-4">
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Asset Code</Label>
                                        <p class="text-base font-semibold">{{ asset.asset_code }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Type</Label>
                                        <p class="text-base">{{ asset.type }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Model</Label>
                                        <p class="text-base">{{ asset.model }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Serial Number</Label>
                                        <p class="text-base">{{ asset.serial_number || '-' }}</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                            <Calendar class="h-4 w-4" />
                                            Purchase Date
                                        </Label>
                                        <p class="text-base">{{ asset.purchase_date }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                            <DollarSign class="h-4 w-4" />
                                            Cost
                                        </Label>
                                        <p class="text-base">${{ asset.cost }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Warranty End</Label>
                                        <p class="text-base">{{ asset.warranty_end || '-' }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Created At</Label>
                                        <p class="text-base text-sm text-muted-foreground">{{ asset.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="asset.notes">
                                <Label class="text-sm font-medium text-muted-foreground">Notes</Label>
                                <p class="text-base mt-2 p-3 bg-gray-50 rounded-lg">{{ asset.notes }}</p>
                            </div>
                    </div>

                    <!-- History Tab -->
                    <div v-if="activeTab === 'history'" class="space-y-6">
                            <!-- Assignments History -->
                            <div>
                                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                    <User class="h-5 w-5" />
                                    Assignment History
                                </h3>
                                <div
                                    v-if="history.assignments.length === 0"
                                    class="text-center py-8 text-sm text-muted-foreground"
                                >
                                    No assignment history
                                </div>
                                <div
                                    v-else
                                    class="space-y-4"
                                >
                                    <Card
                                        v-for="assignment in history.assignments"
                                        :key="assignment.id"
                                        class="p-4"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold">{{ assignment.employee_name }}</span>
                                                    <Badge
                                                        v-if="assignment.is_active"
                                                        variant="default"
                                                        class="text-xs"
                                                    >
                                                        Active
                                                    </Badge>
                                                </div>
                                                <div class="text-sm text-muted-foreground space-y-1">
                                                    <p>Assigned: {{ assignment.assign_date }}</p>
                                                    <p v-if="assignment.return_date">Returned: {{ assignment.return_date }}</p>
                                                    <p>Condition on Assign: {{ assignment.condition_on_assign }}</p>
                                                    <p v-if="assignment.condition_on_return">Condition on Return: {{ assignment.condition_on_return }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                </div>
                            </div>

                            <!-- Maintenance History -->
                            <div>
                                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                    <Wrench class="h-5 w-5" />
                                    Maintenance History
                                </h3>
                                <div
                                    v-if="history.maintenance.length === 0"
                                    class="text-center py-8 text-sm text-muted-foreground"
                                >
                                    No maintenance history
                                </div>
                                <div
                                    v-else
                                    class="space-y-4"
                                >
                                    <Card
                                        v-for="maintenance in history.maintenance"
                                        :key="maintenance.id"
                                        class="p-4"
                                    >
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <Badge
                                                    :variant="maintenance.status === 'Completed' ? 'default' : 'outline'"
                                                >
                                                    {{ maintenance.status }}
                                                </Badge>
                                                <span class="text-sm font-semibold">{{ maintenance.maintenance_type }}</span>
                                            </div>
                                            <p class="text-sm text-muted-foreground">{{ maintenance.problem_description }}</p>
                                            <div class="text-sm text-muted-foreground space-y-1">
                                                <p>Start Date: {{ maintenance.start_date }}</p>
                                                <p v-if="maintenance.completion_date">Completion Date: {{ maintenance.completion_date }}</p>
                                                <p>Cost: ${{ maintenance.cost }}</p>
                                                <p v-if="maintenance.vendor">Vendor: {{ maintenance.vendor }}</p>
                                            </div>
                                        </div>
                                    </Card>
                                </div>
                            </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

