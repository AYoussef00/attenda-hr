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
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Settings, ArrowLeft, Save, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';

const props = defineProps<{
    company: {
        id: number;
        name: string;
    };
    settings: {
        id: number;
        cycle_type: string;
        salary_calculation_method: string;
        overtime_multiplier: number;
        currency: string;
        default_salary_release_day: number;
        late_deduction_enabled: boolean;
        late_grace_minutes: number;
        late_calculation_unit: string;
        absence_deduction_type: string;
        absence_deduction_percentage: number | null;
        absence_termination_days: number | null;
        early_leave_deduction_enabled: boolean;
        missing_punch_handling: string;
        attendance_bonus_enabled: boolean;
        attendance_bonus_type: string | null;
        attendance_bonus_amount: number | null;
        attendance_bonus_condition: string | null;
        attendance_bonus_min_days: number | null;
        overtime_enabled: boolean;
        overtime_requires_approval: boolean;
        overtime_normal_rate: number;
        overtime_weekend_rate: number;
        overtime_holiday_rate: number;
        overtime_max_per_day: number | null;
        overtime_max_per_month: number | null;
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
    {
        title: 'Settings',
        href: '/company/payroll/settings',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const activeSection = ref<'general' | 'attendance' | 'overtime'>('general');

const form = useForm({
    // General Settings
    cycle_type: props.settings.cycle_type,
    salary_calculation_method: props.settings.salary_calculation_method,
    overtime_multiplier: props.settings.overtime_multiplier,
    currency: props.settings.currency,
    default_salary_release_day: props.settings.default_salary_release_day,
    // Attendance & Deduction Settings
    late_deduction_enabled: props.settings.late_deduction_enabled,
    late_grace_minutes: props.settings.late_grace_minutes,
    late_calculation_unit: props.settings.late_calculation_unit,
    absence_deduction_type: props.settings.absence_deduction_type,
    absence_deduction_percentage: props.settings.absence_deduction_percentage,
    absence_termination_days: props.settings.absence_termination_days,
    early_leave_deduction_enabled: props.settings.early_leave_deduction_enabled,
    missing_punch_handling: props.settings.missing_punch_handling,
    // Attendance Bonus Settings
    attendance_bonus_enabled: props.settings.attendance_bonus_enabled,
    attendance_bonus_type: props.settings.attendance_bonus_type,
    attendance_bonus_amount: props.settings.attendance_bonus_amount,
    attendance_bonus_condition: props.settings.attendance_bonus_condition,
    attendance_bonus_min_days: props.settings.attendance_bonus_min_days,
    // Overtime Settings
    overtime_enabled: props.settings.overtime_enabled,
    overtime_requires_approval: props.settings.overtime_requires_approval,
    overtime_normal_rate: props.settings.overtime_normal_rate,
    overtime_weekend_rate: props.settings.overtime_weekend_rate,
    overtime_holiday_rate: props.settings.overtime_holiday_rate,
    overtime_max_per_day: props.settings.overtime_max_per_day,
    overtime_max_per_month: props.settings.overtime_max_per_month,
});

const submit = () => {
    form.post('/company/payroll/settings', {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

const goBack = () => {
    router.visit('/company/payroll');
};
</script>

<template>
    <Head title="Payroll Settings" />

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
                                <Settings class="h-5 w-5" />
                                Payroll Settings
                            </CardTitle>
                            <CardDescription>
                                Configure payroll settings and preferences
                            </CardDescription>
                        </div>
                        <Button
                            variant="ghost"
                            @click="goBack"
                        >
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Button>
                    </div>
                </CardHeader>
            </Card>

            <!-- Navigation Tabs -->
            <Card>
                <CardContent class="p-0">
                    <div class="flex border-b">
                        <button
                            @click="activeSection = 'general'"
                            :class="[
                                'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeSection === 'general'
                                    ? 'border-emerald-600 text-emerald-600'
                                    : 'border-transparent text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            1. General Payroll Settings
                        </button>
                        <button
                            @click="activeSection = 'attendance'"
                            :class="[
                                'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeSection === 'attendance'
                                    ? 'border-emerald-600 text-emerald-600'
                                    : 'border-transparent text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            2. Attendance & Deduction Settings
                        </button>
                        <button
                            @click="activeSection = 'overtime'"
                            :class="[
                                'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeSection === 'overtime'
                                    ? 'border-emerald-600 text-emerald-600'
                                    : 'border-transparent text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            3. Overtime Settings
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- Settings Forms -->
            <form @submit.prevent="submit">
                <!-- Section 1: General Payroll Settings -->
                <Card v-show="activeSection === 'general'">
                    <CardHeader>
                        <CardTitle>General Payroll Settings</CardTitle>
                        <CardDescription>
                            Basic system settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="cycle_type">Cycle Type</Label>
                                <select
                                    id="cycle_type"
                                    v-model="form.cycle_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="bi-weekly">Bi-weekly</option>
                                    <option value="weekly">Weekly</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="salary_calculation_method">Salary Calculation Method</Label>
                                <select
                                    id="salary_calculation_method"
                                    v-model="form.salary_calculation_method"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="fixed_salary">Fixed Salary</option>
                                    <option value="daily_rate">Daily Rate</option>
                                    <option value="hourly_rate">Hourly Rate</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_multiplier">Overtime Calculation Rule</Label>
                                <select
                                    id="overtime_multiplier"
                                    v-model.number="form.overtime_multiplier"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option :value="1.25">1.25x</option>
                                    <option :value="1.5">1.5x</option>
                                    <option :value="2.0">2x (Weekend/Holiday)</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="currency">Currency</Label>
                                <Input
                                    id="currency"
                                    v-model="form.currency"
                                    type="text"
                                    placeholder="USD"
                                    maxlength="10"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="default_salary_release_day">Default Salary Release Day</Label>
                                <Input
                                    id="default_salary_release_day"
                                    v-model.number="form.default_salary_release_day"
                                    type="number"
                                    min="1"
                                    max="31"
                                    placeholder="27"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 2: Attendance & Deduction Settings -->
                <Card v-show="activeSection === 'attendance'">
                    <CardHeader>
                        <CardTitle>Attendance & Deduction Settings</CardTitle>
                        <CardDescription>
                            Configure attendance and deduction rules
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Late Rules -->
                        <div class="space-y-4 border-b pb-6">
                            <h3 class="text-lg font-semibold">Late Rules</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label>Is late deduction enabled?</Label>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                :value="true"
                                                v-model="form.late_deduction_enabled"
                                                class="h-4 w-4"
                                            />
                                            <span>Yes</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                :value="false"
                                                v-model="form.late_deduction_enabled"
                                                class="h-4 w-4"
                                            />
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="late_grace_minutes">After how many minutes is it considered late?</Label>
                                    <Input
                                        id="late_grace_minutes"
                                        v-model.number="form.late_grace_minutes"
                                        type="number"
                                        min="0"
                                        placeholder="15"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="late_calculation_unit">Is lateness counted by hour or minute?</Label>
                                    <select
                                        id="late_calculation_unit"
                                        v-model="form.late_calculation_unit"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    >
                                        <option value="minute">Minute</option>
                                        <option value="hour">Hour</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Absence Rules -->
                        <div class="space-y-4 border-b pb-6">
                            <h3 class="text-lg font-semibold">Absence Rules</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label for="absence_deduction_type">Deduct full day or percentage?</Label>
                                    <select
                                        id="absence_deduction_type"
                                        v-model="form.absence_deduction_type"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    >
                                        <option value="full_day">Full Day</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>

                                <div v-if="form.absence_deduction_type === 'percentage'" class="space-y-2">
                                    <Label for="absence_deduction_percentage">Deduction Percentage (%)</Label>
                                    <Input
                                        id="absence_deduction_percentage"
                                        v-model.number="form.absence_deduction_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        placeholder="50.00"
                                    />
                                    <p class="text-xs text-slate-500">Enter percentage from 0 to 100 (e.g., 50 means 50%)</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="absence_termination_days">After how many days of absence is it considered Terminated?</Label>
                                    <Input
                                        id="absence_termination_days"
                                        v-model.number="form.absence_termination_days"
                                        type="number"
                                        min="1"
                                        placeholder="Leave empty for no limit"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Early Leave Rules -->
                        <div class="space-y-4 border-b pb-6">
                            <h3 class="text-lg font-semibold">Early Leave Rules</h3>
                            <div class="space-y-2">
                                <Label>Is early leave deduction enabled?</Label>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="true"
                                            v-model="form.early_leave_deduction_enabled"
                                            class="h-4 w-4"
                                        />
                                        <span>Yes</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="false"
                                            v-model="form.early_leave_deduction_enabled"
                                            class="h-4 w-4"
                                        />
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Missing Punch Rules -->
                        <div class="space-y-4 border-b pb-6">
                            <h3 class="text-lg font-semibold">Missing Punch Rules</h3>
                            <div class="space-y-2">
                                <Label for="missing_punch_handling">Forgot to record attendance or departure - deduct or ignore?</Label>
                                <select
                                    id="missing_punch_handling"
                                    v-model="form.missing_punch_handling"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="deduct">Deduct</option>
                                    <option value="ignore">Ignore</option>
                                </select>
                            </div>
                        </div>

                        <!-- Attendance Bonus Rules -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold">Attendance Bonus Rules</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label>Is attendance bonus enabled?</Label>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                :value="true"
                                                v-model="form.attendance_bonus_enabled"
                                                class="h-4 w-4"
                                            />
                                            <span>Yes</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="radio"
                                                :value="false"
                                                v-model="form.attendance_bonus_enabled"
                                                class="h-4 w-4"
                                            />
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>

                                <div v-if="form.attendance_bonus_enabled" class="space-y-2">
                                    <Label for="attendance_bonus_type">Bonus Type</Label>
                                    <select
                                        id="attendance_bonus_type"
                                        v-model="form.attendance_bonus_type"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    >
                                        <option value="fixed_amount">Fixed Amount</option>
                                        <option value="percentage">Percentage of Salary</option>
                                        <option value="per_day">Per Day</option>
                                    </select>
                                </div>

                                <div v-if="form.attendance_bonus_enabled && form.attendance_bonus_type" class="space-y-2">
                                    <Label for="attendance_bonus_amount">
                                        Bonus Amount
                                        <span v-if="form.attendance_bonus_type === 'percentage'">(%)</span>
                                        <span v-else-if="form.attendance_bonus_type === 'per_day'">(per day)</span>
                                    </Label>
                                    <Input
                                        id="attendance_bonus_amount"
                                        v-model.number="form.attendance_bonus_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :placeholder="form.attendance_bonus_type === 'percentage' ? '5.00' : '100.00'"
                                    />
                                    <p class="text-xs text-slate-500">
                                        <span v-if="form.attendance_bonus_type === 'percentage'">Enter percentage (e.g., 5 means 5% of salary)</span>
                                        <span v-else-if="form.attendance_bonus_type === 'per_day'">Enter amount per day of perfect attendance</span>
                                        <span v-else>Enter fixed bonus amount</span>
                                    </p>
                                </div>

                                <div v-if="form.attendance_bonus_enabled" class="space-y-2">
                                    <Label for="attendance_bonus_condition">Bonus Condition</Label>
                                    <select
                                        id="attendance_bonus_condition"
                                        v-model="form.attendance_bonus_condition"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    >
                                        <option value="perfect_attendance">Perfect Attendance (No late, no absence, no early leave)</option>
                                        <option value="no_late">No Late Arrivals</option>
                                        <option value="no_absence">No Absence Days</option>
                                        <option value="custom_days">Minimum Days of Attendance</option>
                                    </select>
                                </div>

                                <div v-if="form.attendance_bonus_enabled && form.attendance_bonus_condition === 'custom_days'" class="space-y-2">
                                    <Label for="attendance_bonus_min_days">Minimum Days Required</Label>
                                    <Input
                                        id="attendance_bonus_min_days"
                                        v-model.number="form.attendance_bonus_min_days"
                                        type="number"
                                        min="1"
                                        placeholder="20"
                                    />
                                    <p class="text-xs text-slate-500">Minimum number of attendance days required to earn bonus</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 3: Overtime Settings -->
                <Card v-show="activeSection === 'overtime'">
                    <CardHeader>
                        <CardTitle>Overtime Settings</CardTitle>
                        <CardDescription>
                            Configure overtime rules and rates
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label>Is overtime allowed?</Label>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="true"
                                            v-model="form.overtime_enabled"
                                            class="h-4 w-4"
                                        />
                                        <span>Yes</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="false"
                                            v-model="form.overtime_enabled"
                                            class="h-4 w-4"
                                        />
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Does overtime require approval?</Label>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="true"
                                            v-model="form.overtime_requires_approval"
                                            class="h-4 w-4"
                                        />
                                        <span>Yes</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="false"
                                            v-model="form.overtime_requires_approval"
                                            class="h-4 w-4"
                                        />
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_normal_rate">Normal OT Rate</Label>
                                <Input
                                    id="overtime_normal_rate"
                                    v-model.number="form.overtime_normal_rate"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="3"
                                    placeholder="1.25"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_weekend_rate">Weekend OT Rate</Label>
                                <Input
                                    id="overtime_weekend_rate"
                                    v-model.number="form.overtime_weekend_rate"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="3"
                                    placeholder="1.5"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_holiday_rate">Holiday OT Rate</Label>
                                <Input
                                    id="overtime_holiday_rate"
                                    v-model.number="form.overtime_holiday_rate"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="3"
                                    placeholder="2.0"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_max_per_day">Max OT per Day (hours)</Label>
                                <Input
                                    id="overtime_max_per_day"
                                    v-model.number="form.overtime_max_per_day"
                                    type="number"
                                    min="1"
                                    placeholder="Leave empty for no limit"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="overtime_max_per_month">Max OT per Month (hours)</Label>
                                <Input
                                    id="overtime_max_per_month"
                                    v-model.number="form.overtime_max_per_month"
                                    type="number"
                                    min="1"
                                    placeholder="Leave empty for no limit"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Save Button -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                Save All Settings
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </CompanyLayout>
</template>
