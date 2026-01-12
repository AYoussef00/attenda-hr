<script setup lang="ts">
import EmployeeLayout from '@/layouts/employee/EmployeeLayout.vue';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Calendar,
    ArrowLeft,
    Plus,
    User,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
    employee: {
        id: number;
        employee_code: string;
        name: string;
        position: string | null;
        department: string;
        shift: string;
    };
    leave_types: Array<{
        id: number;
        name: string;
        description: string | null;
        yearly_balance: number;
    }>;
    leave_balances?: Array<{
        key: string;
        name: string;
        total: number;
        used: number;
        remaining: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'My Leaves',
        href: '/company/employee/leaves',
    },
    {
        title: 'Add New Leave Request',
        href: '/company/employee/leaves/create',
    },
];

const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    note: '',
    medical_certificate: null as File | null,
});

// Calculate days when start_date or end_date changes
const calculatedDays = computed(() => {
    if (form.start_date && form.end_date) {
        const start = new Date(form.start_date);
        const end = new Date(form.end_date);
        const diffTime = Math.abs(end.getTime() - start.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Include both start and end dates
        return diffDays;
    }
    return 0;
});

const selectedLeaveType = computed(() => {
    return props.leave_types.find(type => type.id === Number(form.leave_type_id));
});

const requiresMedicalCertificate = computed(() => {
    const name = (selectedLeaveType.value?.name || '').toLowerCase();
    return name === 'sick leave' || name === 'maternity/paternity leave';
});

const getBalancePercent = (balance: { total: number; remaining: number }) => {
    if (!balance.total || balance.total <= 0) return 0;
    const pct = (balance.remaining / balance.total) * 100;
    return Math.max(0, Math.min(100, Math.round(pct)));
};

const submit = () => {
    form.post('/company/employee/leaves', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Add New Leave Request" />

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Employee Info -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                {{ employee.name }}
                            </CardTitle>
                            <CardDescription>
                                {{ employee.employee_code }} | {{ employee.position || 'N/A' }} | {{ employee.department }} | {{ employee.shift }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Leave Balances (Annual & Sick) -->
            <Card v-if="leave_balances && leave_balances.length">
                <CardContent class="py-4">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div
                            v-for="balance in leave_balances"
                            :key="balance.key"
                            class="flex flex-col items-center justify-center text-center"
                        >
                            <!-- Digital circular gauge -->
                            <div
                                class="relative flex items-center justify-center h-28 w-28 rounded-full bg-slate-100 shadow-md"
                            >
                                <!-- Outer progress ring -->
                                <div
                                    class="absolute inset-2 rounded-full bg-transparent"
                                    :style="{
                                        background: `conic-gradient(#22c55e ${getBalancePercent(balance)}%, #e5e7eb 0)`,
                                    }"
                                ></div>
                                <!-- Inner circle: only remaining days -->
                                <div
                                    class="relative flex h-20 w-20 items-center justify-center rounded-full bg-white text-center"
                                >
                                    <span class="text-3xl font-semibold text-slate-900">
                                        {{ balance.remaining }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ balance.name }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Used {{ balance.used }} of {{ balance.total }} days
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Leave Request Form -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Add New Leave Request
                            </CardTitle>
                            <CardDescription>
                                Submit a new leave request
                            </CardDescription>
                        </div>
                        <Button
                            variant="outline"
                            as-child
                        >
                            <Link href="/company/employee/leaves">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Leaves
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Leave Type -->
                        <div class="grid gap-2">
                            <Label for="leave_type_id">
                                Leave Type <span class="text-destructive">*</span>
                            </Label>
                            <select
                                id="leave_type_id"
                                v-model="form.leave_type_id"
                                name="leave_type_id"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :class="{ 'border-destructive': form.errors.leave_type_id }"
                            >
                                <option value="">Select Leave Type</option>
                                <option
                                    v-for="type in leave_types"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                    <span v-if="type.yearly_balance > 0">
                                        (Yearly Balance: {{ type.yearly_balance }} days)
                                    </span>
                                </option>
                            </select>
                            <InputError :message="form.errors.leave_type_id" />
                            <p
                                v-if="selectedLeaveType && selectedLeaveType.description"
                                class="text-xs text-muted-foreground"
                            >
                                {{ selectedLeaveType.description }}
                            </p>
                        </div>

                        <!-- Medical certificate upload (Sick Leave only) -->
                        <div
                            v-if="requiresMedicalCertificate"
                            class="grid gap-2"
                        >
                            <Label for="medical_certificate">
                                Medical Certificate (required for Sick Leave)
                            </Label>
                            <Input
                                id="medical_certificate"
                                type="file"
                                accept=".pdf,image/*"
                                class="flex h-9 w-full cursor-pointer rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                :class="{ 'border-destructive': (form.errors as any).medical_certificate }"
                                @change="(e: Event) => {
                                    const target = e.target as HTMLInputElement;
                                    form.medical_certificate = (target.files?.[0] || null) as any;
                                }"
                            />
                            <InputError :message="(form.errors as any).medical_certificate" />
                            <p class="text-xs text-muted-foreground">
                                Accepted formats: PDF, JPG, PNG. Max size 10MB.
                            </p>
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                                    :min="new Date().toISOString().split('T')[0]"
                                    :class="{ 'border-destructive': form.errors.start_date }"
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
                                    :min="form.start_date || new Date().toISOString().split('T')[0]"
                                    :class="{ 'border-destructive': form.errors.end_date }"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>
                        </div>

                        <!-- Calculated Days -->
                        <div
                            v-if="calculatedDays > 0"
                            class="rounded-lg border bg-muted p-4"
                        >
                            <p class="text-sm font-medium">
                                Total Days: <span class="text-lg font-bold text-primary">{{ calculatedDays }}</span>
                                {{ calculatedDays === 1 ? 'day' : 'days' }}
                            </p>
                        </div>

                        <!-- Note -->
                        <div class="grid gap-2">
                            <Label for="note">Note (Optional)</Label>
                            <textarea
                                id="note"
                                v-model="form.note"
                                name="note"
                                rows="4"
                                placeholder="Enter any additional notes or comments..."
                                class="flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :class="{ 'border-destructive': form.errors.note }"
                            ></textarea>
                            <InputError :message="form.errors.note" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/employee/leaves">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Submit Leave Request</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </EmployeeLayout>
</template>

