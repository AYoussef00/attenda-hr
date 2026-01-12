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
import { Building2, ArrowLeft, Upload, X, CreditCard } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
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
        title: 'Companies',
        href: '/system/companies',
    },
    {
        title: 'Add New Company',
        href: '/system/companies/create',
    },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    logo: null as File | null,
    status: 'active',
    attendance_methods: {
        qr: true,
        ip: false,
    },
    // Subscription fields
    plan_id: '',
    subscription_type: 'monthly', // 'monthly' or 'annual'
    subscription_start_date: new Date().toISOString().split('T')[0],
    subscription_end_date: '',
    // Admin user fields
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
});

const logoPreview = ref<string | null>(null);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
};

// Calculate end date when plan is selected, start date changes, or subscription type changes
const calculateEndDate = () => {
    if (form.subscription_start_date && form.plan_id && form.subscription_type) {
        const startDate = new Date(form.subscription_start_date);
        const endDate = new Date(startDate);
        
        if (form.subscription_type === 'monthly') {
            // Add 1 month
            endDate.setMonth(endDate.getMonth() + 1);
        } else if (form.subscription_type === 'annual') {
            // Add 1 year
            endDate.setFullYear(endDate.getFullYear() + 1);
        }
        
        form.subscription_end_date = endDate.toISOString().split('T')[0];
    }
};

const selectedPlan = computed(() => {
    return props.plans.find(plan => plan.id === Number(form.plan_id));
});

const submit = () => {
    form.post('/system/companies', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Attenda - Add New Company | Register New Company">
        <meta name="description" content="إضافة شركة جديدة في Attenda. تسجيل شركة جديدة في النظام مع تفاصيلها الأساسية وخطة الاشتراك." />
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
                                <Building2 class="h-5 w-5" />
                                Add New Company
                            </CardTitle>
                            <CardDescription>
                                Create a new company account
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/system/companies">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Companies
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Company Name -->
                            <div class="grid gap-2">
                                <Label for="name">
                                    Company Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    name="name"
                                    required
                                    placeholder="Enter company name"
                                    :class="{ 'border-destructive': form.errors.name }"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Email -->
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    name="email"
                                    placeholder="company@example.com"
                                    :class="{ 'border-destructive': form.errors.email }"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Phone -->
                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    name="phone"
                                    placeholder="+1234567890"
                                    :class="{ 'border-destructive': form.errors.phone }"
                                />
                                <InputError :message="form.errors.phone" />
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
                                    <option value="inactive">Inactive</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                name="address"
                                rows="3"
                                placeholder="Enter company address"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                :class="{ 'border-destructive': form.errors.address }"
                            />
                            <InputError :message="form.errors.address" />
                        </div>

                        <!-- Logo Upload -->
                        <div class="grid gap-2">
                            <Label for="logo">Company Logo</Label>
                            
                            <!-- Preview -->
                            <div v-if="logoPreview" class="relative w-32 h-32 border rounded-lg overflow-hidden">
                                <img
                                    :src="logoPreview"
                                    alt="Logo preview"
                                    class="w-full h-full object-cover"
                                />
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    class="absolute top-1 right-1"
                                    @click="removeLogo"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>

                            <!-- Upload Button -->
                            <div v-if="!logoPreview">
                                <label
                                    for="logo"
                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                                    :class="{ 'border-destructive': form.errors.logo }"
                                >
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <Upload class="w-8 h-8 mb-2 text-gray-400" />
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            PNG, JPG, GIF (MAX. 2MB)
                                        </p>
                                    </div>
                                    <input
                                        id="logo"
                                        type="file"
                                        name="logo"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleLogoChange"
                                    />
                                </label>
                            </div>

                            <!-- Change Logo Button -->
                            <div v-else class="flex gap-2">
                                <label
                                    for="logo_change"
                                    class="cursor-pointer"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        as="span"
                                    >
                                        Change Logo
                                    </Button>
                                    <input
                                        id="logo_change"
                                        type="file"
                                        name="logo"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleLogoChange"
                                    />
                                </label>
                            </div>

                            <InputError :message="form.errors.logo" />
                        </div>

                        <!-- Attendance Methods -->
                        <div class="grid gap-2">
                            <Label>Attendance Methods</Label>
                            <div class="flex gap-6">
                                <div class="flex items-center space-x-2">
                                    <input
                                        id="qr_method"
                                        v-model="form.attendance_methods.qr"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <Label
                                        for="qr_method"
                                        class="cursor-pointer font-normal"
                                    >
                                        QR Code
                                    </Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input
                                        id="ip_method"
                                        v-model="form.attendance_methods.ip"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <Label
                                        for="ip_method"
                                        class="cursor-pointer font-normal"
                                    >
                                        IP Address
                                    </Label>
                                </div>
                            </div>
                            <InputError :message="form.errors['attendance_methods']" />
                        </div>

                        <!-- Subscription Section -->
                        <div class="border-t pt-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold flex items-center gap-2">
                                    <CreditCard class="h-5 w-5" />
                                    Subscription Plan (Optional)
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    Select a subscription plan for this company
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <!-- Plan -->
                                <div class="grid gap-2">
                                    <Label for="plan_id">Plan</Label>
                                    <select
                                        id="plan_id"
                                        v-model="form.plan_id"
                                        name="plan_id"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        :class="{ 'border-destructive': form.errors.plan_id }"
                                        @change="calculateEndDate"
                                    >
                                        <option value="">No Plan</option>
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

                                <!-- Subscription Type -->
                                <div class="grid gap-2" v-if="form.plan_id">
                                    <Label>
                                        Subscription Type <span class="text-destructive">*</span>
                                    </Label>
                                    <div class="flex gap-6">
                                        <div class="flex items-center space-x-2">
                                            <input
                                                id="subscription_monthly"
                                                v-model="form.subscription_type"
                                                type="radio"
                                                name="subscription_type"
                                                value="monthly"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                @change="calculateEndDate"
                                            />
                                            <Label
                                                for="subscription_monthly"
                                                class="cursor-pointer font-normal"
                                            >
                                                Monthly
                                            </Label>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <input
                                                id="subscription_annual"
                                                v-model="form.subscription_type"
                                                type="radio"
                                                name="subscription_type"
                                                value="annual"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                                @change="calculateEndDate"
                                            />
                                            <Label
                                                for="subscription_annual"
                                                class="cursor-pointer font-normal"
                                            >
                                                Annual
                                            </Label>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.subscription_type" />
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2" v-if="form.plan_id">
                                    <!-- Subscription Start Date -->
                                    <div class="grid gap-2">
                                        <Label for="subscription_start_date">
                                            Start Date <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="subscription_start_date"
                                            v-model="form.subscription_start_date"
                                            type="date"
                                            name="subscription_start_date"
                                            required
                                            :class="{ 'border-destructive': form.errors.subscription_start_date }"
                                            @change="calculateEndDate"
                                        />
                                        <InputError :message="form.errors.subscription_start_date" />
                                    </div>

                                    <!-- Subscription End Date -->
                                    <div class="grid gap-2">
                                        <Label for="subscription_end_date">
                                            End Date <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="subscription_end_date"
                                            v-model="form.subscription_end_date"
                                            type="date"
                                            name="subscription_end_date"
                                            required
                                            :class="{ 'border-destructive': form.errors.subscription_end_date }"
                                            readonly
                                        />
                                        <InputError :message="form.errors.subscription_end_date" />
                                        <p class="text-xs text-muted-foreground">
                                            Calculated automatically based on subscription type
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin User Section -->
                        <div class="border-t pt-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold">Company Admin User</h3>
                                <p class="text-sm text-muted-foreground">
                                    Create the admin user account for this company
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Admin Name -->
                                <div class="grid gap-2">
                                    <Label for="admin_name">
                                        Admin Name <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="admin_name"
                                        v-model="form.admin_name"
                                        type="text"
                                        name="admin_name"
                                        required
                                        placeholder="Enter admin name"
                                        :class="{ 'border-destructive': form.errors.admin_name }"
                                    />
                                    <InputError :message="form.errors.admin_name" />
                                </div>

                                <!-- Admin Email -->
                                <div class="grid gap-2">
                                    <Label for="admin_email">
                                        Admin Email <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="admin_email"
                                        v-model="form.admin_email"
                                        type="email"
                                        name="admin_email"
                                        required
                                        placeholder="admin@example.com"
                                        :class="{ 'border-destructive': form.errors.admin_email }"
                                    />
                                    <InputError :message="form.errors.admin_email" />
                                </div>

                                <!-- Admin Password -->
                                <div class="grid gap-2">
                                    <Label for="admin_password">
                                        Admin Password <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="admin_password"
                                        v-model="form.admin_password"
                                        type="password"
                                        name="admin_password"
                                        required
                                        placeholder="Enter password"
                                        :class="{ 'border-destructive': form.errors.admin_password }"
                                    />
                                    <InputError :message="form.errors.admin_password" />
                                </div>

                                <!-- Admin Password Confirmation -->
                                <div class="grid gap-2">
                                    <Label for="admin_password_confirmation">
                                        Confirm Password <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="admin_password_confirmation"
                                        v-model="form.admin_password_confirmation"
                                        type="password"
                                        name="admin_password_confirmation"
                                        required
                                        placeholder="Confirm password"
                                        :class="{ 'border-destructive': form.errors.admin_password_confirmation }"
                                    />
                                    <InputError :message="form.errors.admin_password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/system/companies">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Company</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

