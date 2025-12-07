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
import { Building2, ArrowLeft, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
        logo: string | null;
        attendance_methods: {
            qr: boolean;
            ip: boolean;
        };
        ip_whitelist: string[];
        status: string;
    };
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
        title: 'Edit Company',
        href: `/system/companies/${props.company.id}/edit`,
    },
];

const form = useForm({
    name: props.company.name,
    email: props.company.email || '',
    phone: props.company.phone || '',
    address: props.company.address || '',
    logo: null as File | null,
    status: props.company.status,
    attendance_methods: {
        qr: props.company.attendance_methods?.qr ?? true,
        ip: props.company.attendance_methods?.ip ?? false,
    },
    ip_whitelist: props.company.ip_whitelist || [],
});

const logoPreview = ref<string | null>(props.company.logo);

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
    logoPreview.value = props.company.logo; // Reset to original logo
};

// IP Whitelist management
const newIpAddress = ref('');

const addIpAddress = () => {
    if (newIpAddress.value.trim() && !form.ip_whitelist.includes(newIpAddress.value.trim())) {
        form.ip_whitelist.push(newIpAddress.value.trim());
        newIpAddress.value = '';
    }
};

const removeIpAddress = (index: number) => {
    form.ip_whitelist.splice(index, 1);
};

const submit = () => {
    form.put(`/system/companies/${props.company.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Reset logo preview to new logo if uploaded
            if (!form.logo) {
                logoPreview.value = props.company.logo;
            }
        },
    });
};
</script>

<template>
    <Head title="Edit Company" />

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
                                Edit Company
                            </CardTitle>
                            <CardDescription>
                                Update company information
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
                                    type="text"
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

                        <!-- IP Whitelist -->
                        <div class="border-t pt-6" v-if="form.attendance_methods.ip">
                            <h3 class="text-lg font-semibold mb-4">IP Whitelist</h3>
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label for="new_ip">Add IP Address</Label>
                                    <div class="flex gap-2">
                                        <Input
                                            id="new_ip"
                                            v-model="newIpAddress"
                                            type="text"
                                            placeholder="192.168.1.1"
                                            @keyup.enter="addIpAddress"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="addIpAddress"
                                        >
                                            Add IP
                                        </Button>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Add IP addresses that are allowed to record attendance
                                    </p>
                                </div>

                                <!-- IP Address List -->
                                <div v-if="form.ip_whitelist.length > 0" class="space-y-2">
                                    <Label>Allowed IP Addresses</Label>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(ip, index) in form.ip_whitelist"
                                            :key="index"
                                            class="flex items-center justify-between p-3 border rounded-lg"
                                        >
                                            <span class="text-sm font-mono">{{ ip }}</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                @click="removeIpAddress(index)"
                                            >
                                                <X class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-muted-foreground">
                                    No IP addresses added. All IPs will be allowed.
                                </div>
                                <InputError :message="form.errors['ip_whitelist']" />
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
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Company</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

