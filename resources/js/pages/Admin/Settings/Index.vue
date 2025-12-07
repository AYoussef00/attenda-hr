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
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { Settings, Upload, Trash2, Building2, CheckCircle2, XCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';

const props = defineProps<{
    partnerLogos: Array<{
        id: number;
        logo_path: string;
        logo_url: string;
        company_name: string | null;
        display_order: number;
        is_active: boolean;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Settings',
        href: '/system/settings',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const logoForm = useForm({
    logo: null as File | null,
    company_name: '',
});

const logoPreview = ref<string | null>(null);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        logoForm.logo = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const submitLogo = () => {
    if (!logoForm.logo) {
        return;
    }

    logoForm.post('/system/settings/partner-logos', {
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            logoPreview.value = null;
        },
    });
};

const deleteLogo = (id: number) => {
    if (confirm('Are you sure you want to delete this logo?')) {
        router.delete(`/system/settings/partner-logos/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
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

            <!-- Partner Logos Section -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Building2 class="h-5 w-5" />
                        <CardTitle>Partner Company Logos</CardTitle>
                    </div>
                    <CardDescription>
                        Add and manage logos of partner companies
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Add New Logo Form -->
                    <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
                        <h3 class="text-lg font-semibold mb-4">Add New Partner Logo</h3>
                        <form @submit.prevent="submitLogo" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="company_name">Company Name (Optional)</Label>
                                <Input
                                    id="company_name"
                                    v-model="logoForm.company_name"
                                    type="text"
                                    placeholder="Enter company name"
                                    class="max-w-md"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="logo">Logo Image <span class="text-red-500">*</span></Label>
                                
                                <!-- Logo Preview -->
                                <div v-if="logoPreview" class="mb-4">
                                    <div class="relative inline-block">
                                        <img
                                            :src="logoPreview"
                                            alt="Logo preview"
                                            class="h-32 w-auto object-contain border border-gray-300 rounded-lg p-2 bg-white"
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-500 text-white hover:bg-red-600"
                                            @click="logoPreview = null; logoForm.logo = null"
                                        >
                                            <XCircle class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Upload Area -->
                                <div v-if="!logoPreview">
                                    <label
                                        for="logo"
                                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                                    >
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <Upload class="w-8 h-8 mb-2 text-gray-400" />
                                            <p class="mb-2 text-sm text-gray-500">
                                                <span class="font-semibold">Click to upload</span> or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                PNG, JPG, GIF, SVG, WEBP (MAX. 2MB)
                                            </p>
                                        </div>
                                        <input
                                            id="logo"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleLogoChange"
                                        />
                                    </label>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                :disabled="!logoForm.logo || logoForm.processing"
                                class="bg-[#1e3b3b] hover:bg-[#234444]"
                            >
                                <span v-if="logoForm.processing">Uploading...</span>
                                <span v-else>Add Logo</span>
                            </Button>
                        </form>
                    </div>

                    <!-- Existing Logos Grid -->
                    <div v-if="partnerLogos.length > 0">
                        <h3 class="text-lg font-semibold mb-4">Partner Logos ({{ partnerLogos.length }})</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div
                                v-for="logo in partnerLogos"
                                :key="logo.id"
                                class="relative group border border-gray-200 rounded-lg p-4 bg-white hover:shadow-lg transition-all"
                            >
                                <!-- Logo Image -->
                                <div class="flex items-center justify-center h-32 mb-2">
                                    <img
                                        :src="logo.logo_url"
                                        :alt="logo.company_name || 'Partner Logo'"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>

                                <!-- Company Name -->
                                <p
                                    v-if="logo.company_name"
                                    class="text-sm font-medium text-gray-700 text-center mb-2 truncate"
                                    :title="logo.company_name"
                                >
                                    {{ logo.company_name }}
                                </p>
                                <p
                                    v-else
                                    class="text-xs text-gray-400 text-center mb-2"
                                >
                                    No name
                                </p>

                                <!-- Delete Button -->
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="w-full mt-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    @click="deleteLogo(logo.id)"
                                >
                                    <Trash2 class="h-4 w-4 mr-2" />
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else
                        class="text-center py-12 border border-dashed border-gray-300 rounded-lg"
                    >
                        <Building2 class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p class="text-gray-600">
                            No partner logos added yet. Add your first logo above.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
