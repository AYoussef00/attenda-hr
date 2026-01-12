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
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    User,
    Building2,
    Mail,
    Phone,
    Calendar,
    Clock,
    Briefcase,
    CreditCard,
    MapPin,
    Key,
    AlertCircle,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    employee: {
        id: number;
        employee_code: string;
        position: string | null;
        hire_date: string | null;
        hire_date_formatted: string | null;
        contract_type: string | null;
        status: string;
        department: {
            id: number;
            name: string;
        } | null;
        shift: {
            id: number;
            name: string;
            start_time: string;
            end_time: string;
        } | null;
    };
    user: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        last_login: string | null;
        last_login_formatted: string | null;
    };
    company: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
        logo: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'Profile',
        href: '/company/employee/profile',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const profileForm = useForm({
    _method: 'put',
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    profileForm.put('/company/employee/profile', {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.password = '';
            profileForm.password_confirmation = '';
        },
    });
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return { variant: 'default', class: 'bg-green-600 hover:bg-green-700 text-white border-0' };
        case 'inactive':
            return { variant: 'secondary', class: 'bg-gray-600 hover:bg-gray-700 text-white border-0' };
        case 'terminated':
            return { variant: 'destructive', class: 'bg-red-600 hover:bg-red-700 text-white border-0' };
        default:
            return { variant: 'outline', class: '' };
    }
};

const getInitials = (name: string) => {
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

<template>
    <Head title="Attenda - My Profile | Employee Profile & Information">
        <meta name="description" content="ملفي الشخصي في Attenda. عرض وتحديث المعلومات الشخصية، بيانات الاتصال، الوظيفة، والقسم." />
    </Head>

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Success/Error Messages -->
            <Alert
                v-if="flash?.success"
                variant="default"
                class="border-green-500 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100"
            >
                <CheckCircle2 class="h-4 w-4" />
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>

            <Alert
                v-if="flash?.error"
                variant="destructive"
            >
                <XCircle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Profile Header -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-6">
                        <Avatar class="h-24 w-24">
                            <AvatarImage :src="company.logo" :alt="user.name" />
                            <AvatarFallback class="text-2xl">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <CardTitle class="text-2xl flex items-center gap-2">
                                <User class="h-6 w-6" />
                                {{ user.name }}
                            </CardTitle>
                            <CardDescription class="mt-2">
                                <div class="flex items-center gap-4 text-sm">
                                    <span class="flex items-center gap-1">
                                        <CreditCard class="h-4 w-4" />
                                        {{ employee.employee_code }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Briefcase class="h-4 w-4" />
                                        {{ employee.position || 'N/A' }}
                                    </span>
                                    <Badge :class="getStatusVariant(employee.status).class">
                                        {{ employee.status }}
                                    </Badge>
                                </div>
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Employee Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Employee Information
                        </CardTitle>
                        <CardDescription>
                            Your employee details
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Employee Code</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <CreditCard class="h-4 w-4 text-muted-foreground" />
                                {{ employee.employee_code }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Position</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Briefcase class="h-4 w-4 text-muted-foreground" />
                                {{ employee.position || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Department</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Building2 class="h-4 w-4 text-muted-foreground" />
                                {{ employee.department?.name || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Shift</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                {{ employee.shift?.name || 'N/A' }}
                                <span v-if="employee.shift" class="text-muted-foreground">
                                    ({{ employee.shift.start_time }} - {{ employee.shift.end_time }})
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Hire Date</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                {{ employee.hire_date_formatted || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Contract Type</Label>
                            <div class="text-sm font-medium">
                                {{ employee.contract_type || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Status</Label>
                            <div>
                                <Badge :class="getStatusVariant(employee.status).class">
                                    {{ employee.status }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Company Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Company Information
                        </CardTitle>
                        <CardDescription>
                            Your company details
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-4">
                            <Avatar class="h-16 w-16">
                                <AvatarImage :src="company.logo" :alt="company.name" />
                                <AvatarFallback>
                                    {{ getInitials(company.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div class="text-lg font-semibold">{{ company.name }}</div>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Company Email</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                {{ company.email || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-muted-foreground">Company Phone</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <Phone class="h-4 w-4 text-muted-foreground" />
                                {{ company.phone || 'N/A' }}
                            </div>
                        </div>

                        <div class="grid gap-2" v-if="company.address">
                            <Label class="text-muted-foreground">Company Address</Label>
                            <div class="flex items-center gap-2 text-sm font-medium">
                                <MapPin class="h-4 w-4 text-muted-foreground" />
                                {{ company.address }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Account Information -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Key class="h-5 w-5" />
                            Account Information
                        </CardTitle>
                        <CardDescription>
                            Update your account information
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Name -->
                                <div class="grid gap-2">
                                    <Label for="name">
                                        Name <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="profileForm.name"
                                        type="text"
                                        name="name"
                                        required
                                        :class="{ 'border-destructive': profileForm.errors.name }"
                                    />
                                    <InputError :message="profileForm.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="grid gap-2">
                                    <Label for="email">
                                        Email <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        v-model="profileForm.email"
                                        type="email"
                                        name="email"
                                        required
                                        :class="{ 'border-destructive': profileForm.errors.email }"
                                    />
                                    <InputError :message="profileForm.errors.email" />
                                </div>

                                <!-- Phone -->
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        v-model="profileForm.phone"
                                        type="tel"
                                        name="phone"
                                        :class="{ 'border-destructive': profileForm.errors.phone }"
                                    />
                                    <InputError :message="profileForm.errors.phone" />
                                </div>

                                <!-- Last Login -->
                                <div class="grid gap-2">
                                    <Label class="text-muted-foreground">Last Login</Label>
                                    <div class="text-sm font-medium text-muted-foreground">
                                        {{ user.last_login_formatted || 'Never' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-semibold mb-4">Change Password (Optional)</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- New Password -->
                                    <div class="grid gap-2">
                                        <Label for="password">New Password</Label>
                                        <Input
                                            id="password"
                                            v-model="profileForm.password"
                                            type="password"
                                            name="password"
                                            :class="{ 'border-destructive': profileForm.errors.password }"
                                        />
                                        <InputError :message="profileForm.errors.password" />
                                        <p class="text-xs text-muted-foreground">
                                            Leave blank to keep current password
                                        </p>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="grid gap-2">
                                        <Label for="password_confirmation">Confirm New Password</Label>
                                        <Input
                                            id="password_confirmation"
                                            v-model="profileForm.password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            :class="{ 'border-destructive': profileForm.errors.password_confirmation }"
                                        />
                                        <InputError :message="profileForm.errors.password_confirmation" />
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-4">
                                <Button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                >
                                    <span v-if="profileForm.processing">Updating...</span>
                                    <span v-else>Update Profile</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </EmployeeLayout>
</template>


