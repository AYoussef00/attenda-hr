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
import { Package, ArrowLeft, UserCheck, XCircle, ArrowRight, ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next';
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
        title: 'Assign / Return',
        href: '/company/assets/assignments',
    },
];

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

const showAssignDialog = ref(false);
const showReturnDialog = ref(false);
const selectedAssignment = ref<typeof props.currentAssignments[0] | null>(null);

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

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Asset Assignments | Assign & Return Assets">
        <meta name="description" content="توزيع وإرجاع الأصول في Attenda. تعيين الأصول للموظفين، تتبعها، وإرجاعها عند الحاجة." />
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

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold flex items-center gap-2">
                        <Package class="h-6 w-6" />
                        Asset Assignments
                    </h2>
                    <p class="text-muted-foreground">Assign and return assets to employees</p>
                </div>
                <Button variant="outline" as-child>
                    <Link href="/company/assets">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Assets
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Available Assets Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Available Assets
                        </CardTitle>
                        <CardDescription>
                            Assets ready for assignment
                        </CardDescription>
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

                <!-- Current Assignments Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Current Assignments
                        </CardTitle>
                        <CardDescription>
                            Assets currently assigned to employees
                        </CardDescription>
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

