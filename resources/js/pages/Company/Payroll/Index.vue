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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CreditCard, Plus, Eye, Calendar, CheckCircle2, Clock, FileText, DollarSign, AlertCircle, Users, TrendingUp, TrendingDown, Timer, Settings, RefreshCw, Trash2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';

const props = defineProps<{
    cycles: Array<{
        id: number | null;
        month: string;
        status: string;
        generated_at: string | null;
        paid_at: string | null;
        entries_count: number;
        created_at: string | null;
        has_cycle: boolean;
        employees_with_attendance: number;
    }>;
    statistics: {
        active_employees_count: number;
        total_cycles_count: number;
        current_month_net_salary: number;
        total_allowances: number;
        total_deductions: number;
        total_overtime: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Payroll',
        href: '/company/payroll',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const generateDialogOpen = ref(false);
const selectedMonth = ref('');

// Get current month in YYYY-MM format
const currentMonth = computed(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
});

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'generated':
            return 'default';
        case 'approved':
            return 'default';
        case 'paid':
            return 'default';
        case 'not_generated':
            return 'outline';
        default:
            return 'outline';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'paid':
            return CheckCircle2;
        case 'generated':
        case 'approved':
            return Clock;
        case 'not_generated':
            return AlertCircle;
        default:
            return FileText;
    }
};

const formatMonth = (month: string) => {
    const [year, monthNum] = month.split('-');
    const date = new Date(parseInt(year), parseInt(monthNum) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const generatePayroll = () => {
    if (!selectedMonth.value) {
        return;
    }

    router.post(`/company/payroll/generate/${selectedMonth.value}`, {}, {
        onSuccess: () => {
            generateDialogOpen.value = false;
            selectedMonth.value = '';
        },
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const viewCycle = (id: number | null) => {
    if (id) {
        router.visit(`/company/payroll/cycle/${id}`);
    }
};

const generateForMonth = (month: string) => {
    selectedMonth.value = month;
    generateDialogOpen.value = true;
};

const regenerateCycle = (id: number | null, month: string) => {
    if (!id) {
        return;
    }

    if (!confirm(`Are you sure you want to delete and regenerate the payroll cycle for ${formatMonth(month)}? This action cannot be undone.`)) {
        return;
    }

    router.post(`/company/payroll/cycle/${id}/regenerate`, {}, {
        onSuccess: () => {
            // Success handled by flash message
        },
        onError: () => {
            // Error handled by flash message
        },
    });
};
</script>

<template>
    <Head title="Payroll" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Flash Messages -->
            <Alert v-if="flash?.success" class="bg-emerald-50 border-emerald-200">
                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                <AlertTitle class="text-emerald-800">Success</AlertTitle>
                <AlertDescription class="text-emerald-700">
                    {{ flash.success }}
                </AlertDescription>
            </Alert>

            <Alert v-if="flash?.error" class="bg-red-50 border-red-200">
                <AlertCircle class="h-4 w-4 text-red-600" />
                <AlertTitle class="text-red-800">Error</AlertTitle>
                <AlertDescription class="text-red-700">
                    {{ flash.error }}
                </AlertDescription>
            </Alert>

            <!-- Header -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Payroll Cycles
                            </CardTitle>
                            <CardDescription>
                                Manage payroll cycles and generate monthly payroll
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                @click="router.visit('/company/payroll/settings')"
                            >
                                <Settings class="h-4 w-4 mr-2" />
                                Payroll Settings
                            </Button>
                            <Dialog v-model:open="generateDialogOpen">
                                <DialogTrigger as-child>
                                    <Button>
                                        <Plus class="h-4 w-4 mr-2" />
                                        Generate Payroll
                                    </Button>
                                </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Generate Payroll</DialogTitle>
                                    <DialogDescription>
                                        Select the month for which you want to generate payroll
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="space-y-4 py-4">
                                    <div class="space-y-2">
                                        <Label for="month">Month</Label>
                                        <Input
                                            id="month"
                                            v-model="selectedMonth"
                                            type="month"
                                            :min="currentMonth"
                                            placeholder="YYYY-MM"
                                        />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        @click="generateDialogOpen = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button @click="generatePayroll">
                                        Generate
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- عدد الموظفين النشطين -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Active Employees
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ statistics.active_employees_count }}</div>
                        <p class="text-xs text-slate-500 mt-1">Currently active</p>
                    </CardContent>
                </Card>

                <!-- عدد الدورات الشهرية -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <Calendar class="h-4 w-4" />
                            Total Payroll Cycles
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ statistics.total_cycles_count }}</div>
                        <p class="text-xs text-slate-500 mt-1">Monthly cycles</p>
                    </CardContent>
                </Card>

                <!-- إجمالي الرواتب المستحقة للشهر الحالي -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <DollarSign class="h-4 w-4" />
                            Current Month Net Salary
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ formatCurrency(statistics.current_month_net_salary) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">For {{ currentMonth }}</p>
                    </CardContent>
                </Card>

                <!-- إجمالي البدلات -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-emerald-600" />
                            Total Allowances
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">
                            {{ formatCurrency(statistics.total_allowances) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Fixed + Variable</p>
                    </CardContent>
                </Card>

                <!-- إجمالي الخصومات -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <TrendingDown class="h-4 w-4 text-red-600" />
                            Total Deductions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatCurrency(statistics.total_deductions) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Fixed + Manual + Late</p>
                    </CardContent>
                </Card>

                <!-- إجمالي الأوفر تايم -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-slate-600 flex items-center gap-2">
                            <Timer class="h-4 w-4 text-blue-600" />
                            Total Overtime
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ formatCurrency(statistics.total_overtime) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Overtime payments</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Payroll Cycles List -->
            <Card>
                <CardHeader>
                    <CardTitle>Payroll Cycles</CardTitle>
                    <CardDescription>
                        List of all payroll cycles
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="cycles.length === 0" class="text-center py-8 text-slate-500">
                        No payroll cycles found. Generate your first payroll cycle.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="cycle in cycles"
                            :key="cycle.id"
                            class="flex items-center justify-between p-4 border rounded-lg hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100">
                                    <component
                                        :is="getStatusIcon(cycle.status)"
                                        class="h-6 w-6 text-emerald-600"
                                    />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-lg">
                                            {{ formatMonth(cycle.month) }}
                                        </h3>
                                        <Badge :variant="getStatusVariant(cycle.status)">
                                            {{ cycle.status }}
                                        </Badge>
                                    </div>
                                    <div class="text-sm text-slate-500 mt-1">
                                        <span v-if="cycle.generated_at">
                                            Generated: {{ new Date(cycle.generated_at).toLocaleDateString() }}
                                        </span>
                                        <span v-if="cycle.paid_at" class="ml-4">
                                            Paid: {{ new Date(cycle.paid_at).toLocaleDateString() }}
                                        </span>
                                        <span class="ml-4">
                                            <span v-if="cycle.has_cycle">
                                                {{ cycle.entries_count }} employees in payroll
                                            </span>
                                            <span v-else>
                                                {{ cycle.employees_with_attendance }} employees with attendance
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="cycle.has_cycle"
                                    variant="outline"
                                    size="sm"
                                    @click="viewCycle(cycle.id)"
                                >
                                    <Eye class="h-4 w-4 mr-2" />
                                    View
                                </Button>
                                <Button
                                    v-if="cycle.has_cycle"
                                    variant="outline"
                                    size="sm"
                                    @click="regenerateCycle(cycle.id, cycle.month)"
                                    class="text-orange-600 hover:text-orange-700"
                                >
                                    <RefreshCw class="h-4 w-4 mr-2" />
                                    Regenerate
                                </Button>
                                <Button
                                    v-else
                                    variant="default"
                                    size="sm"
                                    @click="generateForMonth(cycle.month)"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Generate
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

