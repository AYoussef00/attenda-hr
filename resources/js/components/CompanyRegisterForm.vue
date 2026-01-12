<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';

type Plan = {
    id: number | string;
    name: string;
    price: string;
    price_raw: number | null;
    yearly_price: string | null;
    yearly_price_raw: number | null;
    max_employees: number | null;
    features: string[];
    popular: boolean;
    description: string;
};

const props = defineProps<{
    plans: Array<{
        id: number;
        name: string;
        price: string;
        price_raw: number;
        yearly_price: string | null;
        yearly_price_raw: number | null;
        max_employees: number;
        features: string[];
    }>;
    selectedPlan?: {
        id: number;
        name: string;
        price: string;
        price_raw: number;
        yearly_price: string | null;
        yearly_price_raw: number | null;
        max_employees: number;
        features: string[];
    } | null;
    billingPeriod?: 'monthly' | 'yearly';
}>();

const emit = defineEmits<{
    close: [];
}>();

const step = ref<1 | 2 | 3>(1);
const showSuccessDialog = ref(false);
const registrationData = ref<any>(null);
const page = usePage();
const plansList = computed<Plan[]>(() => (props.plans || []) as unknown as Plan[]);

const form = useForm({
    company: {
        name: '',
        industry: '',
        industry_other: '',
        commercial_registration_no: '',
        country: '',
        city: '',
        address: '',
        phone: '',
        email: '',
        size: '' as string | null,
    },
    admin: {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    },
    plan_id: null as number | null,
    billing_period: (props.billingPeriod || 'monthly') as 'monthly' | 'yearly',
});

// Set selected plan if provided
if (props.selectedPlan) {
    // Handle both numeric and string IDs (for Enterprise plan)
    const planId = typeof props.selectedPlan.id === 'string' 
        ? (props.selectedPlan.id === 'enterprise' ? null : parseInt(props.selectedPlan.id))
        : props.selectedPlan.id;
    
    if (planId) {
        form.plan_id = planId;
    }
}

const canGoNextStep1 = computed(() => {
    return !!form.company.name && !!form.company.commercial_registration_no && !!form.company.email;
});

const canGoNextStep2 = computed(() => {
    return (
        !!form.admin.name &&
        !!form.admin.email &&
        !!form.admin.password &&
        !!form.admin.password_confirmation
    );
});

const canSubmit = computed(() => {
    // All steps valid + plan selected
    return canGoNextStep1.value && canGoNextStep2.value && !!form.plan_id;
});

const goToNext = () => {
    if (step.value === 1 && canGoNextStep1.value) {
        step.value = 2;
    } else if (step.value === 2 && canGoNextStep2.value) {
        step.value = 3;
    }
};

const goToPrev = () => {
    if (step.value > 1) {
        step.value = (step.value - 1) as 1 | 2 | 3;
    }
};

// Billing period toggle (binds to form.billing_period)
const billingPeriod = computed<'monthly' | 'yearly'>({
    get: () => form.billing_period,
    set: (value) => {
        form.billing_period = value;
    },
});

const getDisplayPrice = (plan: Plan): string => {
    if (billingPeriod.value === 'yearly') {
        if (plan.yearly_price === null) return 'Custom';
        return plan.yearly_price;
    }

    return plan.price;
};

const isEnterprisePlan = (plan: Plan): boolean => {
    return (
        plan.price === 'Custom' ||
        plan.price_raw === null ||
        plan.name.toLowerCase() === 'enterprise'
    );
};

const getOriginalYearlyPrice = (monthlyPrice: number): number => {
    return monthlyPrice * 12;
};

const getPlanNumericId = (plan: Plan): number | null => {
    if (typeof plan.id === 'number') return plan.id;
    if (typeof plan.id === 'string') {
        const parsed = parseInt(plan.id, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }
    return null;
};

const selectPlan = (plan: Plan) => {
    if (isEnterprisePlan(plan)) {
        // Enterprise is contact-sales only; do not set numeric plan_id
        form.plan_id = null;
        return;
    }
    const id = getPlanNumericId(plan);
    if (id !== null) {
        form.plan_id = id;
    }
};

const submit = async () => {
    if (!canSubmit.value) return;
    
    try {
        // Get CSRF token from Inertia props or meta tag
        const csrfToken = (page.props.csrf_token as string) || 
                         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                         '';
        
        if (!csrfToken) {
            alert('CSRF token not found. Please refresh the page.');
            return;
        }
        
        const response = await fetch('/company/register', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                company: form.company,
                admin: form.admin,
                plan_id: form.plan_id,
                billing_period: form.billing_period,
            }),
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response received. Status:', response.status);
            console.error('Response preview:', text.substring(0, 200));
            
            // If it's a validation error, try to parse it
            if (response.status === 422) {
                alert('Validation failed. Please check the form fields.');
            } else {
                alert('Server error. Please try again or contact support.');
            }
            return;
        }
        
        const data = await response.json();
        
        if (!response.ok) {
            // Handle validation errors
            if (data.errors) {
                // Set Inertia form errors for display
                Object.keys(data.errors).forEach(key => {
                    form.setError(
                        key as any,
                        Array.isArray(data.errors[key]) ? data.errors[key][0] : data.errors[key]
                    );
                });

                // Build a readable message for the user
                const allMessages = Object.values(data.errors)
                    .flat()
                    .join('\n');

                alert(allMessages || 'Please check the required fields and try again.');
            } else {
                alert(data.message || `Error: ${response.status}`);
            }
            return;
        }
        
        if (data.success) {
            registrationData.value = data;
            showSuccessDialog.value = true;
        } else {
            alert(data.message || 'Failed to register company');
        }
    } catch (error: any) {
        console.error('Registration error:', error);
        alert(error.message || 'An error occurred during registration. Please try again.');
    }
};

const countries = [
    { code: 'EG', name: 'Egypt', flag: '🇪🇬' },
    { code: 'SA', name: 'Saudi Arabia', flag: '🇸🇦' },
    { code: 'AE', name: 'United Arab Emirates', flag: '🇦🇪' },
    { code: 'QA', name: 'Qatar', flag: '🇶🇦' },
    { code: 'KW', name: 'Kuwait', flag: '🇰🇼' },
    { code: 'BH', name: 'Bahrain', flag: '🇧🇭' },
    { code: 'OM', name: 'Oman', flag: '🇴🇲' },
    { code: 'JO', name: 'Jordan', flag: '🇯🇴' },
    { code: 'LB', name: 'Lebanon', flag: '🇱🇧' },
    { code: 'MA', name: 'Morocco', flag: '🇲🇦' },
    { code: 'TN', name: 'Tunisia', flag: '🇹🇳' },
    { code: 'DZ', name: 'Algeria', flag: '🇩🇿' },
    { code: 'LY', name: 'Libya', flag: '🇱🇾' },
    { code: 'SD', name: 'Sudan', flag: '🇸🇩' },
    { code: 'TR', name: 'Turkey', flag: '🇹🇷' },
    { code: 'US', name: 'United States', flag: '🇺🇸' },
    { code: 'GB', name: 'United Kingdom', flag: '🇬🇧' },
    { code: 'DE', name: 'Germany', flag: '🇩🇪' },
    { code: 'FR', name: 'France', flag: '🇫🇷' },
    { code: 'IT', name: 'Italy', flag: '🇮🇹' },
    { code: 'ES', name: 'Spain', flag: '🇪🇸' },
    { code: 'CA', name: 'Canada', flag: '🇨🇦' },
    { code: 'AU', name: 'Australia', flag: '🇦🇺' },
    { code: 'IN', name: 'India', flag: '🇮🇳' },
    { code: 'PK', name: 'Pakistan', flag: '🇵🇰' },
    { code: 'BD', name: 'Bangladesh', flag: '🇧🇩' },
    { code: 'PH', name: 'Philippines', flag: '🇵🇭' },
    { code: 'ID', name: 'Indonesia', flag: '🇮🇩' },
    { code: 'NG', name: 'Nigeria', flag: '🇳🇬' },
    { code: 'KE', name: 'Kenya', flag: '🇰🇪' },
    { code: 'ZA', name: 'South Africa', flag: '🇿🇦' },
];

const industryOptions = [
    'Restaurant',
    'Café / Food & Beverage',
    'Retail',
    'E-commerce',
    'IT & Software',
    'Marketing & Advertising',
    'Construction',
    'Manufacturing / Factory',
    'Healthcare',
    'Education',
    'Logistics & Transportation',
    'Services',
    'Other',
];
</script>

<template>
    <div class="space-y-6">
        <!-- Header + stepper -->
        <div class="space-y-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
                    attenda.
                </h2>
                <p class="text-sm text-muted-foreground">
                    Complete the steps below to create your company account in just a few minutes.
                </p>
            </div>

            <!-- Stepper (3 steps, match demo style) -->
            <div class="flex items-center gap-4 text-xs font-medium text-slate-700">
                <!-- Step 1 -->
                <div
                    class="flex items-center gap-2"
                    :class="step === 1 ? 'text-slate-800' : 'text-slate-400'"
                >
                    <span
                        :class="[
                            'flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-semibold',
                            step === 1 ? 'bg-purple-600 text-white' : 'border border-slate-200 bg-white',
                        ]"
                    >
                        1
                    </span>
                    <span class="hidden sm:inline">Company information</span>
                    </div>

                <!-- Connector -->
                <div class="h-px flex-1 bg-slate-200">
                    <div
                        class="h-px"
                        :class="step >= 2 ? 'w-full bg-slate-800' : 'w-1/3 bg-slate-400'"
                    />
                </div>

                <!-- Step 2 -->
                <div
                    class="flex items-center gap-2"
                    :class="step === 2 ? 'text-slate-800' : 'text-slate-400'"
                >
                    <span
                        :class="[
                            'flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-semibold',
                            step === 2 ? 'bg-purple-600 text-white' : 'border border-slate-200 bg-white',
                        ]"
                    >
                        2
                    </span>
                    <span class="hidden sm:inline">Account owner</span>
                </div>

                <!-- Connector -->
                <div class="h-px flex-1 bg-slate-200">
                    <div
                        class="h-px"
                        :class="step === 3 ? 'w-full bg-slate-800' : 'w-1/3 bg-slate-400'"
                    />
                </div>

                <!-- Step 3 -->
                <div
                    class="flex items-center gap-2"
                    :class="step === 3 ? 'text-slate-800' : 'text-slate-400'"
                >
                    <span
                        :class="[
                            'flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-semibold',
                            step === 3 ? 'bg-purple-600 text-white' : 'border border-slate-200 bg-white',
                        ]"
                    >
                        3
                    </span>
                    <span class="hidden sm:inline">Choose plan</span>
                </div>
                    </div>
        </div>

        <!-- Form body -->
        <div>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Step 1: Company -->
                <div v-if="step === 1" class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        STEP 1 · COMPANY INFORMATION
                    </h2>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="company_name">Company Name *</Label>
                            <Input
                                id="company_name"
                                v-model="form.company.name"
                                type="text"
                                placeholder="e.g., Attenda Solutions"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                            />
                            <InputError :message="form.errors['company.name']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="industry">Industry / Business Type</Label>
                            <select
                                id="industry"
                                v-model="form.company.industry"
                                name="industry"
                                class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select industry</option>
                                <option
                                    v-for="option in industryOptions"
                                    :key="option"
                                    :value="option"
                                >
                                    {{ option }}
                                </option>
                            </select>
                            <InputError :message="form.errors['company.industry']" />
                        </div>

                        <div v-if="form.company.industry === 'Other'" class="grid gap-2">
                            <Label for="industry_other">Specify industry</Label>
                            <Input
                                id="industry_other"
                                v-model="form.company.industry_other"
                                type="text"
                                placeholder="Type your business type"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                            />
                            <InputError :message="form.errors['company.industry_other']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="cr_number">Commercial Registration No. *</Label>
                            <Input
                                id="cr_number"
                                v-model="form.company.commercial_registration_no"
                                type="text"
                                placeholder="e.g., 1234567890"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                            />
                            <InputError :message="form.errors['company.commercial_registration_no']" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="country">Country</Label>
                                <select
                                    id="country"
                                    v-model="form.company.country"
                                    name="country"
                                class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select country</option>
                                    <option
                                        v-for="country in countries"
                                        :key="country.code"
                                        :value="country.name"
                                    >
                                        {{ country.flag }} {{ country.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors['company.country']" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="city">City</Label>
                                <Input
                                    id="city"
                                    v-model="form.company.city"
                                    type="text"
                                    placeholder="e.g., Cairo"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                />
                                <InputError :message="form.errors['company.city']" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input
                                id="address"
                                v-model="form.company.address"
                                type="text"
                                placeholder="Street, district, building"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                            />
                            <InputError :message="form.errors['company.address']" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="company_phone">Company Phone Number</Label>
                                <Input
                                    id="company_phone"
                                    v-model="form.company.phone"
                                    type="text"
                                    placeholder="+20 10 000 0000"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                />
                                <InputError :message="form.errors['company.phone']" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="company_email">Company Email *</Label>
                                <Input
                                    id="company_email"
                                    v-model="form.company.email"
                                    type="email"
                                    placeholder="hr@company.com"
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                />
                                <InputError :message="form.errors['company.email']" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="size">Company size (number of employees)</Label>
                            <select
                                id="size"
                                v-model="form.company.size"
                                name="size"
                                class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">Select range</option>
                                <option value="1-10">1 - 10</option>
                                <option value="30-50">30 - 50</option>
                                <option value="100-150">100 - 150</option>
                                <option value="200-300">200 - 300</option>
                            </select>
                            <InputError :message="form.errors['company.size']" />
                        </div>
                    </div>
                </div>

                <!-- Step 2: Admin -->
                <div v-else-if="step === 2" class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        STEP 2 · ACCOUNT OWNER
                    </h2>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="admin_name">Full Name</Label>
                            <Input
                                id="admin_name"
                                v-model="form.admin.name"
                                type="text"
                                placeholder="Your full name"
                            />
                            <InputError :message="form.errors['admin.name']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin_email">Admin Email (for login)</Label>
                            <Input
                                id="admin_email"
                                v-model="form.admin.email"
                                type="email"
                                placeholder="you@company.com"
                            />
                            <InputError :message="form.errors['admin.email']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin_phone">Admin Phone Number</Label>
                            <Input
                                id="admin_phone"
                                v-model="form.admin.phone"
                                type="text"
                                placeholder="+20 10 000 0000"
                            />
                            <InputError :message="form.errors['admin.phone']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.admin.password"
                                type="password"
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <InputError :message="form.errors['admin.password']" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.admin.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Plan selection -->
                <div v-else-if="step === 3" class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        STEP 3 · CHOOSE YOUR PLAN
                    </h2>
                    <!-- Billing period toggle (same style as pricing section) -->
                    <div
                        v-if="plansList.length > 0 && plansList.some(p => !isEnterprisePlan(p))"
                        class="flex items-center justify-start gap-4"
                    >
                        <span
                            :class="`text-sm font-medium transition-colors ${
                                billingPeriod === 'monthly' ? 'text-gray-900' : 'text-gray-500'
                            }`"
                        >
                            Monthly
                        </span>
                        <button
                            @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                            :class="`relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 ${
                                billingPeriod === 'yearly' ? 'bg-[#1e3b3b]' : 'bg-gray-300'
                            }`"
                            role="switch"
                            :aria-checked="billingPeriod === 'yearly'"
                        >
                            <span
                                :class="`inline-block h-5 w-5 transform rounded-full bg-white transition-transform ${
                                    billingPeriod === 'yearly' ? 'translate-x-6' : 'translate-x-1'
                                }`"
                            />
                        </button>
                        <div class="flex items-center gap-2">
                            <span
                                :class="`text-sm font-medium transition-colors ${
                                    billingPeriod === 'yearly' ? 'text-gray-900' : 'text-gray-500'
                                }`"
                            >
                                Yearly
                            </span>
                            <span
                                v-if="billingPeriod === 'yearly'"
                                class="px-2 py-0.5 bg-green-100 text-green-700 text-[11px] font-semibold rounded-full"
                            >
                                50% OFF
                            </span>
                        </div>
                    </div>

                    <!-- Plan cards (compact version of pricing cards) -->
                    <div
                        v-if="plansList.length > 0"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <Card
                            v-for="plan in plansList"
                            :key="plan.id"
                            :class="`cursor-pointer rounded-2xl border-2 p-4 transition-all duration-300 ${
                                form.plan_id === getPlanNumericId(plan)
                                    ? 'border-black bg-black text-white shadow-xl scale-[1.02]'
                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md'
                            }`"
                            @click="selectPlan(plan)"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-base font-semibold">
                                        {{ plan.name }}
                                    </h3>
                                    <p class="text-xs opacity-80">
                                        {{ plan.description }}
                                    </p>
                                </div>
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full border text-[10px]"
                                    :class="form.plan_id === getPlanNumericId(plan)
                                        ? 'border-white bg-white text-black'
                                        : 'border-gray-300 bg-white text-gray-400'"
                                >
                                    <span v-if="form.plan_id === getPlanNumericId(plan)">✓</span>
                                </span>
                            </div>

                            <div class="mb-2">
                                <div v-if="!isEnterprisePlan(plan)" class="flex items-baseline gap-1">
                                    <span class="text-sm opacity-80">$</span>
                                    <span class="text-3xl font-semibold">
                                        {{ getDisplayPrice(plan) }}
                                    </span>
                                    <span class="text-xs opacity-80">
                                        /{{ billingPeriod === 'yearly' ? 'year' : 'month' }}
                                    </span>
                                </div>
                                <div v-else class="text-sm font-medium">
                                    Custom pricing
                                </div>
                            </div>

                            <p class="text-[11px] opacity-80">
                                Up to {{ plan.max_employees ?? 'unlimited' }} employees
                            </p>

                            <!-- Optional: You can later show discount info here by comparing yearly_price_raw to 12 * price_raw -->
                        </Card>
                    </div>

                    <InputError :message="(form.errors as any).plan_id" />
                </div>


                <!-- Actions -->
                <div class="flex items-center justify-between pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        class="text-xs"
                        :disabled="step === 1 || form.processing"
                        @click="goToPrev"
                    >
                        Back
                    </Button>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-muted-foreground">
                            Step {{ step }} of 3
                        </span>
                        <Button
                            v-if="step === 1 || step === 2"
                            type="button"
                            class="text-xs"
                            :disabled="(step === 1 && !canGoNextStep1) || (step === 2 && !canGoNextStep2) || form.processing"
                            @click="goToNext"
                        >
                            Next
                        </Button>
                        <Button
                            v-else
                            type="submit"
                            class="text-xs"
                            :disabled="!canSubmit || form.processing"
                        >
                            <Spinner v-if="form.processing" />
                            <span v-else>Complete registration</span>
                        </Button>
                    </div>
                </div>

                <InputError :message="(form.errors as any).general" />
            </form>
        </div>

        <!-- Success Dialog -->
        <Dialog :open="showSuccessDialog" @update:open="showSuccessDialog = $event">
            <DialogContent class="sm:max-w-lg border-none p-0 shadow-2xl">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-50 via-white to-blue-50 p-8">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-green-200/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-blue-200/20 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2" />
                    
                    <div class="relative z-10 text-center">
                        <!-- Success Icon -->
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg">
                            <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        
                        <!-- Title -->
                        <DialogTitle class="mb-4 text-3xl font-bold text-gray-900">
                            Request Received Successfully!
                        </DialogTitle>
                        
                        <!-- Message -->
                        <DialogDescription class="mx-auto max-w-md text-base text-gray-600 leading-relaxed">
                            {{ registrationData?.message || 'Your request has been received. We will contact you as soon as possible.' }}
                        </DialogDescription>
                        
                        <!-- Close Button -->
                        <div class="mt-8">
                            <Button 
                                @click="showSuccessDialog = false; $emit('close')" 
                                class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-8 py-6 text-base font-semibold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
                            >
                                Close
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>


