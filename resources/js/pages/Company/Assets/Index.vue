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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { 
    Package, Plus, Edit, Trash2, Eye, UserCheck, XCircle, Search, Filter, X,
    ArrowRight, ArrowLeft as ArrowLeftIcon, Wrench, FileText, Download, Calendar, TrendingUp, DollarSign
} from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    assets: Array<{
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
        assigned_to: {
            employee_id: number;
            employee_name: string;
        } | null;
    }>;
    filters: {
        type?: string;
        status?: string;
        model?: string;
        search?: string;
        purchase_date_from?: string;
        purchase_date_to?: string;
    };
    types: string[];
    statuses: string[];
    // Assignments
    availableAssets: Array<{
        id: number;
        asset_code: string;
        type: string;
        model: string;
    }>;
    currentAssignments: Array<{
        id: number;
        asset_id: number;
        asset_code: string;
        asset_type: string;
        asset_model: string;
        employee_id: number;
        employee_name: string;
        employee_code: string;
        assign_date: string;
        condition_on_assign: string;
    }>;
    employees: Array<{
        id: number;
        name: string;
        employee_code: string;
    }>;
    // Maintenance
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
    maintenanceFilters: {
        maintenance_status?: string;
        maintenance_type?: string;
    };
    assetsForMaintenance: Array<{
        id: number;
        asset_code: string;
        type: string;
        model: string;
    }>;
    maintenanceStatuses: string[];
    maintenanceTypes: string[];
    // Reports
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
];

const activeTab = ref('assets');
const showFilters = ref(false);
const showAssignDialog = ref(false);
const showReturnDialog = ref(false);
const showMaintenanceDialog = ref(false);
const selectedAssignment = ref<typeof props.currentAssignments[0] | null>(null);

const filterForm = useForm({
    type: props.filters.type || '',
    status: props.filters.status || '',
    model: props.filters.model || '',
    search: props.filters.search || '',
    purchase_date_from: props.filters.purchase_date_from || '',
    purchase_date_to: props.filters.purchase_date_to || '',
});

const assignForm = useForm({
    asset_id: '',
    employee_id: '',
    assign_date: new Date().toISOString().split('T')[0],
    condition_on_assign: '',
});

const returnForm = useForm({
    return_date: new Date().toISOString().split('T')[0],
    condition_on_return: '',
});

const maintenanceForm = useForm({
    asset_id: '',
    maintenance_type: 'Repair',
    problem_description: '',
    cost: '0',
    vendor: '',
    start_date: new Date().toISOString().split('T')[0],
    status: 'Open',
});

const maintenanceFilterForm = useForm({
    status: props.maintenanceFilters.maintenance_status || '',
    maintenance_type: props.maintenanceFilters.maintenance_type || '',
});

const applyFilters = () => {
    filterForm.get('/company/assets', {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.reset();
    filterForm.get('/company/assets', {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyMaintenanceFilters = () => {
    maintenanceFilterForm.get('/company/assets', {
        preserveState: true,
        preserveScroll: true,
    });
};

const openReturnDialog = (assignment: typeof props.currentAssignments[0]) => {
    selectedAssignment.value = assignment;
    returnForm.reset();
    returnForm.return_date = new Date().toISOString().split('T')[0];
    showReturnDialog.value = true;
};

const submitAssign = () => {
    assignForm.post('/company/assets/assignments/assign', {
        onSuccess: () => {
            assignForm.reset();
            assignForm.assign_date = new Date().toISOString().split('T')[0];
            showAssignDialog.value = false;
        },
    });
};

const submitReturn = () => {
    if (!selectedAssignment.value) return;
    
    returnForm.post(`/company/assets/assignments/${selectedAssignment.value.id}/return`, {
        onSuccess: () => {
            returnForm.reset();
            returnForm.return_date = new Date().toISOString().split('T')[0];
            selectedAssignment.value = null;
            showReturnDialog.value = false;
        },
    });
};

const submitMaintenance = () => {
    maintenanceForm.post('/company/assets/maintenance', {
        onSuccess: () => {
            maintenanceForm.reset();
            maintenanceForm.start_date = new Date().toISOString().split('T')[0];
            showMaintenanceDialog.value = false;
        },
    });
};

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

const getStatusLabel = (status: string) => {
    return status.replace('_', ' ');
};

const getMaintenanceStatusVariant = (status: string) => {
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

const deleteAsset = (id: number) => {
    if (confirm('Are you sure you want to delete this asset?')) {
        router.delete(`/company/assets/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

const downloadReport = (type: string) => {
    window.location.href = `/company/assets/reports/export/${type}`;
};

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Assets Management | Company Assets Tracking">
        <meta name="description" content="إدارة أصول الشركة في Attenda. تتبع الأصول، المعدات، توزيعها على الموظفين، وصيانتها." />
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
                                <Package class="h-5 w-5" />
                                Assets Management
                            </CardTitle>
                            <CardDescription>
                                Manage your company assets, assignments, maintenance, and reports
                            </CardDescription>
                        </div>
                        <Button
                            v-if="activeTab === 'assets'"
                            as-child
                        >
                            <Link href="/company/assets/create">
                                <Plus class="h-4 w-4" />
                                Add New Asset
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Tab Buttons -->
                    <div class="flex gap-2 mb-6 border-b">
                        <Button
                            variant="ghost"
                            :class="activeTab === 'assets' ? 'border-b-2 border-primary rounded-none' : ''"
                            @click="activeTab = 'assets'"
                        >
                            <Package class="h-4 w-4 mr-2" />
                            All Assets
                        </Button>
                        <Button
                            variant="ghost"
                            :class="activeTab === 'assignments' ? 'border-b-2 border-primary rounded-none' : ''"
                            @click="activeTab = 'assignments'"
                        >
                            <ArrowRight class="h-4 w-4 mr-2" />
                            Assign / Return
                        </Button>
                        <Button
                            variant="ghost"
                            :class="activeTab === 'maintenance' ? 'border-b-2 border-primary rounded-none' : ''"
                            @click="activeTab = 'maintenance'"
                        >
                            <Wrench class="h-4 w-4 mr-2" />
                            Maintenance
                        </Button>
                        <Button
                            variant="ghost"
                            :class="activeTab === 'reports' ? 'border-b-2 border-primary rounded-none' : ''"
                            @click="activeTab = 'reports'"
                        >
                            <FileText class="h-4 w-4 mr-2" />
                            Reports
                        </Button>
                    </div>

                    <!-- Assets Tab -->
                    <div v-if="activeTab === 'assets'" class="space-y-6">
                        <!-- Filters Section -->
                        <div
                            v-if="showFilters"
                            class="mb-6 rounded-lg border bg-gray-50 p-4"
                        >
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="search">Search</Label>
                                    <Input
                                        id="search"
                                        v-model="filterForm.search"
                                        type="text"
                                        placeholder="Search by code, type, model..."
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="type">Type</Label>
                                    <select
                                        id="type"
                                        v-model="filterForm.type"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    >
                                        <option value="">All Types</option>
                                        <option
                                            v-for="type in types"
                                            :key="type"
                                            :value="type"
                                        >
                                            {{ type }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="status">Status</Label>
                                    <select
                                        id="status"
                                        v-model="filterForm.status"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        <option
                                            v-for="status in statuses"
                                            :key="status"
                                            :value="status"
                                        >
                                            {{ getStatusLabel(status) }}
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="model">Model</Label>
                                    <Input
                                        id="model"
                                        v-model="filterForm.model"
                                        type="text"
                                        placeholder="Filter by model"
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="purchase_date_from">Purchase Date From</Label>
                                    <Input
                                        id="purchase_date_from"
                                        v-model="filterForm.purchase_date_from"
                                        type="date"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="purchase_date_to">Purchase Date To</Label>
                                    <Input
                                        id="purchase_date_to"
                                        v-model="filterForm.purchase_date_to"
                                        type="date"
                                    />
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-2">
                                <Button
                                    size="sm"
                                    @click="applyFilters"
                                    :disabled="filterForm.processing"
                                >
                                    <Search class="h-4 w-4 mr-2" />
                                    Apply Filters
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="clearFilters"
                                >
                                    <X class="h-4 w-4 mr-2" />
                                    Clear
                                </Button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="showFilters = !showFilters"
                            >
                                <Filter class="h-4 w-4 mr-2" />
                                {{ showFilters ? 'Hide' : 'Show' }} Filters
                            </Button>
                        </div>

                        <!-- Assets Table -->
                        <div class="overflow-x-auto">
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
                                            Serial Number
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Cost
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Assigned To
                                        </th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-if="assets.length === 0"
                                        class="border-b"
                                    >
                                        <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                            No assets found
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="asset in assets"
                                        :key="asset.id"
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
                                            {{ asset.serial_number || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            ${{ asset.cost }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge
                                                :variant="getStatusVariant(asset.status)"
                                                class="rounded-full px-2 py-0.5 text-xs"
                                            >
                                                {{ getStatusLabel(asset.status) }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            <span v-if="asset.assigned_to">
                                                {{ asset.assigned_to.employee_name }}
                                            </span>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    as-child
                                                >
                                                    <Link :href="`/company/assets/${asset.id}`">
                                                        <Eye class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    as-child
                                                >
                                                    <Link :href="`/company/assets/${asset.id}/edit`">
                                                        <Edit class="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    @click="deleteAsset(asset.id)"
                                                >
                                                    <Trash2 class="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Assignments Tab -->
                    <div v-if="activeTab === 'assignments'" class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Available Assets -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Package class="h-5 w-5" />
                                        Available Assets
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        v-if="availableAssets.length === 0"
                                        class="text-center py-8 text-sm text-muted-foreground"
                                    >
                                        No available assets
                                    </div>
                                    <div
                                        v-else
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="asset in availableAssets"
                                            :key="asset.id"
                                            class="flex items-center justify-between p-3 border rounded-lg"
                                        >
                                            <div>
                                                <p class="font-semibold">{{ asset.asset_code }}</p>
                                                <p class="text-sm text-muted-foreground">{{ asset.type }} - {{ asset.model }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <Dialog v-model:open="showAssignDialog">
                                        <DialogTrigger as-child>
                                            <Button class="w-full mt-4">
                                                <ArrowRight class="h-4 w-4 mr-2" />
                                                Assign Asset
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Assign Asset</DialogTitle>
                                                <DialogDescription>
                                                    Assign an asset to an employee
                                                </DialogDescription>
                                            </DialogHeader>
                                            <form @submit.prevent="submitAssign" class="space-y-4">
                                                <div class="space-y-2">
                                                    <Label for="assign_asset_id">
                                                        Asset <span class="text-destructive">*</span>
                                                    </Label>
                                                    <select
                                                        id="assign_asset_id"
                                                        v-model="assignForm.asset_id"
                                                        required
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                                        :class="{ 'border-destructive': assignForm.errors.asset_id }"
                                                    >
                                                        <option value="">Select asset</option>
                                                        <option
                                                            v-for="asset in availableAssets"
                                                            :key="asset.id"
                                                            :value="asset.id.toString()"
                                                        >
                                                            {{ asset.asset_code }} - {{ asset.type }} - {{ asset.model }}
                                                        </option>
                                                    </select>
                                                    <InputError :message="assignForm.errors.asset_id" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="assign_employee_id">
                                                        Employee <span class="text-destructive">*</span>
                                                    </Label>
                                                    <select
                                                        id="assign_employee_id"
                                                        v-model="assignForm.employee_id"
                                                        required
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                                        :class="{ 'border-destructive': assignForm.errors.employee_id }"
                                                    >
                                                        <option value="">Select employee</option>
                                                        <option
                                                            v-for="employee in employees"
                                                            :key="employee.id"
                                                            :value="employee.id.toString()"
                                                        >
                                                            {{ employee.name }} ({{ employee.employee_code }})
                                                        </option>
                                                    </select>
                                                    <InputError :message="assignForm.errors.employee_id" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="assign_date">
                                                        Assign Date <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="assign_date"
                                                        v-model="assignForm.assign_date"
                                                        type="date"
                                                        required
                                                        :class="{ 'border-destructive': assignForm.errors.assign_date }"
                                                    />
                                                    <InputError :message="assignForm.errors.assign_date" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="condition_on_assign">
                                                        Condition on Assign <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="condition_on_assign"
                                                        v-model="assignForm.condition_on_assign"
                                                        type="text"
                                                        required
                                                        placeholder="e.g., Good, Excellent"
                                                        :class="{ 'border-destructive': assignForm.errors.condition_on_assign }"
                                                    />
                                                    <InputError :message="assignForm.errors.condition_on_assign" />
                                                </div>
                                                <DialogFooter>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        @click="showAssignDialog = false"
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button
                                                        type="submit"
                                                        :disabled="assignForm.processing"
                                                    >
                                                        <span v-if="assignForm.processing">Assigning...</span>
                                                        <span v-else>Assign Asset</span>
                                                    </Button>
                                                </DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                </CardContent>
                            </Card>

                            <!-- Current Assignments -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Package class="h-5 w-5" />
                                        Current Assignments
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div
                                        v-if="currentAssignments.length === 0"
                                        class="text-center py-8 text-sm text-muted-foreground"
                                    >
                                        No active assignments
                                    </div>
                                    <div
                                        v-else
                                        class="space-y-3"
                                    >
                                        <Card
                                            v-for="assignment in currentAssignments"
                                            :key="assignment.id"
                                            class="p-4"
                                        >
                                            <div class="space-y-3">
                                                <div class="flex items-start justify-between">
                                                    <div class="space-y-1">
                                                        <p class="font-semibold">{{ assignment.asset_code }}</p>
                                                        <p class="text-sm text-muted-foreground">{{ assignment.asset_type }} - {{ assignment.asset_model }}</p>
                                                    </div>
                                                    <Badge variant="secondary">Assigned</Badge>
                                                </div>
                                                <div class="space-y-1 text-sm">
                                                    <p class="flex items-center gap-2">
                                                        <span class="font-medium">Employee:</span>
                                                        {{ assignment.employee_name }} ({{ assignment.employee_code }})
                                                    </p>
                                                    <p class="text-muted-foreground">Assigned: {{ assignment.assign_date }}</p>
                                                    <p class="text-muted-foreground">Condition: {{ assignment.condition_on_assign }}</p>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="w-full"
                                                    @click="openReturnDialog(assignment)"
                                                >
                                                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                                                    Return Asset
                                                </Button>
                                            </div>
                                        </Card>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Maintenance Tab -->
                    <div v-if="activeTab === 'maintenance'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold">Maintenance Tickets</h3>
                            </div>
                            <div class="flex gap-2">
                                <Dialog v-model:open="showMaintenanceDialog">
                                    <DialogTrigger as-child>
                                        <Button>
                                            <Plus class="h-4 w-4 mr-2" />
                                            Create Ticket
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="max-w-2xl">
                                        <DialogHeader>
                                            <DialogTitle>Create Maintenance Ticket</DialogTitle>
                                            <DialogDescription>
                                                Create a new maintenance record for an asset
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form @submit.prevent="submitMaintenance" class="space-y-4">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <Label for="maintenance_asset_id">
                                                        Asset <span class="text-destructive">*</span>
                                                    </Label>
                                                    <select
                                                        id="maintenance_asset_id"
                                                        v-model="maintenanceForm.asset_id"
                                                        required
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                                        :class="{ 'border-destructive': maintenanceForm.errors.asset_id }"
                                                    >
                                                        <option value="">Select asset</option>
                                                        <option
                                                            v-for="asset in assetsForMaintenance"
                                                            :key="asset.id"
                                                            :value="asset.id.toString()"
                                                        >
                                                            {{ asset.asset_code }} - {{ asset.type }} - {{ asset.model }}
                                                        </option>
                                                    </select>
                                                    <InputError :message="maintenanceForm.errors.asset_id" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="maintenance_type">
                                                        Maintenance Type <span class="text-destructive">*</span>
                                                    </Label>
                                                    <select
                                                        id="maintenance_type"
                                                        v-model="maintenanceForm.maintenance_type"
                                                        required
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                                        :class="{ 'border-destructive': maintenanceForm.errors.maintenance_type }"
                                                    >
                                                        <option value="Repair">Repair</option>
                                                        <option value="Scheduled">Scheduled</option>
                                                    </select>
                                                    <InputError :message="maintenanceForm.errors.maintenance_type" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="maintenance_start_date">
                                                        Start Date <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="maintenance_start_date"
                                                        v-model="maintenanceForm.start_date"
                                                        type="date"
                                                        required
                                                        :class="{ 'border-destructive': maintenanceForm.errors.start_date }"
                                                    />
                                                    <InputError :message="maintenanceForm.errors.start_date" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="maintenance_status">
                                                        Status <span class="text-destructive">*</span>
                                                    </Label>
                                                    <select
                                                        id="maintenance_status"
                                                        v-model="maintenanceForm.status"
                                                        required
                                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                                        :class="{ 'border-destructive': maintenanceForm.errors.status }"
                                                    >
                                                        <option value="Open">Open</option>
                                                        <option value="In_Progress">In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                    <InputError :message="maintenanceForm.errors.status" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="maintenance_cost">
                                                        Cost <span class="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="maintenance_cost"
                                                        v-model="maintenanceForm.cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        required
                                                        placeholder="0.00"
                                                        :class="{ 'border-destructive': maintenanceForm.errors.cost }"
                                                    />
                                                    <InputError :message="maintenanceForm.errors.cost" />
                                                </div>
                                                <div class="space-y-2">
                                                    <Label for="maintenance_vendor">Vendor</Label>
                                                    <Input
                                                        id="maintenance_vendor"
                                                        v-model="maintenanceForm.vendor"
                                                        type="text"
                                                        placeholder="Vendor name"
                                                        :class="{ 'border-destructive': maintenanceForm.errors.vendor }"
                                                    />
                                                    <InputError :message="maintenanceForm.errors.vendor" />
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="problem_description">
                                                    Problem Description <span class="text-destructive">*</span>
                                                </Label>
                                                <textarea
                                                    id="problem_description"
                                                    v-model="maintenanceForm.problem_description"
                                                    rows="4"
                                                    required
                                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                    placeholder="Describe the problem or maintenance required"
                                                    :class="{ 'border-destructive': maintenanceForm.errors.problem_description }"
                                                />
                                                <InputError :message="maintenanceForm.errors.problem_description" />
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    @click="showMaintenanceDialog = false"
                                                >
                                                    Cancel
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    :disabled="maintenanceForm.processing"
                                                >
                                                    <span v-if="maintenanceForm.processing">Creating...</span>
                                                    <span v-else>Create Ticket</span>
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>

                        <!-- Maintenance Filters -->
                        <div class="rounded-lg border bg-gray-50 p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label>Status</Label>
                                    <select
                                        v-model="maintenanceFilterForm.status"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        <option
                                            v-for="status in maintenanceStatuses"
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
                                        v-model="maintenanceFilterForm.maintenance_type"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    >
                                        <option value="">All Types</option>
                                        <option
                                            v-for="type in maintenanceTypes"
                                            :key="type"
                                            :value="type"
                                        >
                                            {{ type }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <Button
                                size="sm"
                                class="mt-4"
                                @click="applyMaintenanceFilters"
                                :disabled="maintenanceFilterForm.processing"
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
                                                :variant="getMaintenanceStatusVariant(ticket.status)"
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
                    </div>

                    <!-- Reports Tab -->
                    <div v-if="activeTab === 'reports'" class="space-y-6">
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
                </CardContent>
            </Card>

            <!-- Return Dialog -->
            <Dialog v-model:open="showReturnDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Return Asset</DialogTitle>
                        <DialogDescription>
                            Return asset: {{ selectedAssignment?.asset_code }}
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitReturn" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="return_date">
                                Return Date <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="return_date"
                                v-model="returnForm.return_date"
                                type="date"
                                required
                                :class="{ 'border-destructive': returnForm.errors.return_date }"
                            />
                            <InputError :message="returnForm.errors.return_date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="condition_on_return">
                                Condition on Return <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="condition_on_return"
                                v-model="returnForm.condition_on_return"
                                type="text"
                                required
                                placeholder="e.g., Good, Damaged"
                                :class="{ 'border-destructive': returnForm.errors.condition_on_return }"
                            />
                            <InputError :message="returnForm.errors.condition_on_return" />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="showReturnDialog = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="returnForm.processing"
                            >
                                <span v-if="returnForm.processing">Returning...</span>
                                <span v-else>Return Asset</span>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </CompanyLayout>
</template>
