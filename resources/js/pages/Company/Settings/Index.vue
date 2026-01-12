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
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Settings, Upload, X, Save, CheckCircle2, XCircle, Calendar, Plus, Edit, Trash2 } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { computed, ref } from 'vue';

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
        settings: Record<string, any>;
        status: string;
    };
    leave_types: Array<{
        id: number;
        name: string;
        description: string | null;
        yearly_balance: number;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Settings',
        href: '/company/settings',
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

// Leave Types management
const showLeaveTypeModal = ref(false);
const editingLeaveTypeId = ref<number | null>(null);
const leaveTypeSubmitting = ref(false);

// Form data using reactive refs for better v-model performance
const leaveTypeData = ref({
    name: '',
    description: '',
    yearly_balance: 0,
});

const openLeaveTypeModal = (type?: typeof props.leave_types[0]) => {
    if (type) {
        editingLeaveTypeId.value = type.id;
        leaveTypeData.value = {
            name: type.name,
            description: type.description || '',
            yearly_balance: type.yearly_balance,
        };
    } else {
        editingLeaveTypeId.value = null;
        leaveTypeData.value = {
            name: '',
            description: '',
            yearly_balance: 0,
        };
    }
    showLeaveTypeModal.value = true;
};

const closeLeaveTypeModal = () => {
    showLeaveTypeModal.value = false;
    editingLeaveTypeId.value = null;
    leaveTypeData.value = {
        name: '',
        description: '',
        yearly_balance: 0,
    };
    leaveTypeSubmitting.value = false;
};

const submitLeaveType = () => {
    leaveTypeSubmitting.value = true;
    
    if (editingLeaveTypeId.value) {
        // Update existing
        const updateForm = useForm({
            _method: 'put',
            name: leaveTypeData.value.name,
            description: leaveTypeData.value.description,
            yearly_balance: leaveTypeData.value.yearly_balance,
        });
        
        updateForm.put(`/company/leave-types/${editingLeaveTypeId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeLeaveTypeModal();
            },
            onFinish: () => {
                leaveTypeSubmitting.value = false;
            },
        });
    } else {
        // Create new
        const createForm = useForm({
            name: leaveTypeData.value.name,
            description: leaveTypeData.value.description,
            yearly_balance: leaveTypeData.value.yearly_balance,
        });
        
        createForm.post('/company/leave-types', {
            preserveScroll: true,
            onSuccess: () => {
                closeLeaveTypeModal();
            },
            onFinish: () => {
                leaveTypeSubmitting.value = false;
            },
        });
    }
};

const deleteLeaveType = (id: number) => {
    if (confirm('Are you sure you want to delete this leave type?')) {
        router.delete(`/company/leave-types/${id}`, {
            preserveScroll: true,
        });
    }
};

const submit = () => {
    form.put('/company/settings', {
        preserveScroll: true,
        onSuccess: () => {
            // Reset logo preview to new logo if uploaded
            if (!form.logo) {
                logoPreview.value = props.company.logo;
            }
        },
    });
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Company Settings | Configure Your Account">
        <meta name="description" content="إعدادات الشركة في Attenda. تكوين معلومات الشركة، طرق الحضور، أنواع الإجازات، والإعدادات العامة." />
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
                <CheckCircle2 class="h-4 w-4" />
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
                                <Settings class="h-5 w-5" />
                                Company Settings
                            </CardTitle>
                            <CardDescription>
                                Manage your company information and preferences
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
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

                                <!-- Company Email -->
                                <div class="grid gap-2">
                                    <Label for="email">Company Email</Label>
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

                                <!-- Company Phone -->
                                <div class="grid gap-2">
                                    <Label for="phone">Company Phone</Label>
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

                                <!-- Company Status -->
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
                            <div class="grid gap-2 mt-6">
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
                            <div class="grid gap-2 mt-6">
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
                        </div>

                        <!-- Attendance Methods -->
                        <div class="border-b pt-6 pb-6">
                            <h3 class="text-lg font-semibold mb-4">Attendance Methods</h3>
                            <div class="grid gap-2">
                                <Label>Enable Attendance Methods</Label>
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
                        </div>

                        <!-- IP Whitelist -->
                        <div class="border-b pt-6 pb-6" v-if="form.attendance_methods.ip">
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

                        <!-- Leave Types -->
                        <div class="border-b pt-6 pb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold flex items-center gap-2">
                                    <Calendar class="h-5 w-5" />
                                    Leave Types
                                </h3>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="openLeaveTypeModal()"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Leave Type
                                </Button>
                            </div>
                            
                            <div v-if="leave_types.length === 0" class="text-sm text-muted-foreground py-4">
                                No leave types found. Click "Add Leave Type" to create one.
                            </div>
                            
                            <div v-else class="space-y-2">
                                <div
                                    v-for="type in leave_types"
                                    :key="type.id"
                                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="font-semibold">{{ type.name }}</div>
                                            <Badge variant="secondary">
                                                {{ type.yearly_balance }} days/year
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="type.description"
                                            class="text-sm text-muted-foreground mt-1"
                                        >
                                            {{ type.description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            @click="openLeaveTypeModal(type)"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            @click="deleteLeaveType(type.id)"
                                        >
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Type Modal -->
                        <div
                            v-if="showLeaveTypeModal"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                            @click.self="closeLeaveTypeModal"
                        >
                            <Card class="w-full max-w-md">
                                <CardHeader>
                                    <CardTitle>
                                        {{ editingLeaveTypeId ? 'Edit Leave Type' : 'Add New Leave Type' }}
                                    </CardTitle>
                                    <CardDescription>
                                        {{ editingLeaveTypeId ? 'Update leave type information' : 'Create a new leave type for your company' }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form @submit.prevent="submitLeaveType" class="space-y-4">
                                        <div class="grid gap-2">
                                            <Label for="leave_type_name">
                                                Name <span class="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="leave_type_name"
                                                v-model="leaveTypeData.name"
                                                type="text"
                                                required
                                                placeholder="e.g., Annual Leave, Sick Leave"
                                            />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="leave_type_description">Description</Label>
                                            <textarea
                                                id="leave_type_description"
                                                v-model="leaveTypeData.description"
                                                rows="3"
                                                placeholder="Enter description (optional)"
                                                class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                            ></textarea>
                                        </div>

                                <div class="grid gap-2">
                                    <Label for="leave_type_balance">
                                        Yearly Balance (Days) <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="leave_type_balance"
                                        v-model.number="leaveTypeData.yearly_balance"
                                        type="number"
                                        min="0"
                                        required
                                        placeholder="e.g., 21"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Number of days allowed per year for this leave type
                                    </p>
                                </div>

                                <div class="flex items-center justify-end gap-2 pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="closeLeaveTypeModal"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="leaveTypeSubmitting"
                                    >
                                        <span v-if="leaveTypeSubmitting">Saving...</span>
                                        <span v-else>{{ editingLeaveTypeId ? 'Update' : 'Create' }}</span>
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4 pt-6">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <Save class="h-4 w-4 mr-2" />
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Settings</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

