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
import { Head, Link } from '@inertiajs/vue3';
import { FileText, ArrowLeft, Download, Package, DollarSign, Calendar, TrendingUp } from 'lucide-vue-next';

const props = defineProps<{
    assetsByStatus: Record<string, number>;
    highMaintenanceAssets: Array<{
        asset_code: string;
        type: string;
        model: string;
        total_maintenance_cost: string;
    }>;
    nearingWarrantyExpiration: Array<{
        asset_code: string;
        type: string;
        model: string;
        warranty_end: string;
        days_remaining: number;
    }>;
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
        title: 'Reports',
        href: '/company/assets/reports',
    },
];

const downloadReport = (type: string) => {
    window.location.href = `/company/assets/reports/export/${type}`;
};
</script>

<template>
    <Head title="Asset Reports" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold flex items-center gap-2">
                        <FileText class="h-6 w-6" />
                        Asset Reports
                    </h2>
                    <p class="text-muted-foreground">View and download asset reports</p>
                </div>
                <Button variant="outline" as-child>
                    <Link href="/company/assets">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Assets
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6">
                <!-- Assets by Status -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Package class="h-5 w-5" />
                                    Assets by Status
                                </CardTitle>
                                <CardDescription>
                                    Distribution of assets by their current status
                                </CardDescription>
                            </div>
                            <Button
                                variant="outline"
                                @click="downloadReport('status')"
                            >
                                <Download class="h-4 w-4 mr-2" />
                                Download PDF
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="Object.keys(assetsByStatus).length === 0"
                            class="text-center py-8 text-sm text-muted-foreground"
                        >
                            No assets found
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-2 gap-4 md:grid-cols-5"
                        >
                            <div
                                v-for="(count, status) in assetsByStatus"
                                :key="status"
                                class="text-center p-4 border rounded-lg"
                            >
                                <p class="text-2xl font-bold">{{ count }}</p>
                                <p class="text-sm text-muted-foreground mt-1">{{ status.replace('_', ' ') }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- High Maintenance Cost Assets -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5" />
                                    High Maintenance Cost Assets
                                </CardTitle>
                                <CardDescription>
                                    Assets with the highest maintenance costs
                                </CardDescription>
                            </div>
                            <Button
                                variant="outline"
                                @click="downloadReport('maintenance')"
                            >
                                <Download class="h-4 w-4 mr-2" />
                                Download PDF
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="highMaintenanceAssets.length === 0"
                            class="text-center py-8 text-sm text-muted-foreground"
                        >
                            No maintenance records found
                        </div>
                        <div
                            v-else
                            class="overflow-x-auto"
                        >
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Asset Code
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Type
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Model
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Total Maintenance Cost
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="asset in highMaintenanceAssets"
                                        :key="asset.asset_code"
                                        class="border-b hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-3 text-sm font-medium">
                                            {{ asset.asset_code }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ asset.type }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ asset.model }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-red-600">
                                            ${{ asset.total_maintenance_cost }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Assets Nearing Warranty Expiration -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Calendar class="h-5 w-5" />
                                    Assets Nearing Warranty Expiration
                                </CardTitle>
                                <CardDescription>
                                    Assets with warranty expiring within 90 days
                                </CardDescription>
                            </div>
                            <Button
                                variant="outline"
                                @click="downloadReport('warranty')"
                            >
                                <Download class="h-4 w-4 mr-2" />
                                Download PDF
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="nearingWarrantyExpiration.length === 0"
                            class="text-center py-8 text-sm text-muted-foreground"
                        >
                            No assets nearing warranty expiration
                        </div>
                        <div
                            v-else
                            class="overflow-x-auto"
                        >
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Asset Code
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Type
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Model
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Warranty End
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Days Remaining
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="asset in nearingWarrantyExpiration"
                                        :key="asset.asset_code"
                                        class="border-b hover:bg-muted/50"
                                    >
                                        <td class="px-4 py-3 text-sm font-medium">
                                            {{ asset.asset_code }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ asset.type }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ asset.model }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ asset.warranty_end }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge
                                                :variant="asset.days_remaining <= 30 ? 'destructive' : asset.days_remaining <= 60 ? 'outline' : 'default'"
                                            >
                                                {{ asset.days_remaining }} days
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </CompanyLayout>
</template>

