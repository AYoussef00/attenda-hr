<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Save, Upload, Trash2, Image as ImageIcon } from 'lucide-vue-next';
import { ref } from 'vue';

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

interface PartnerLogo {
    id: number;
    logo_url: string;
    company_name?: string;
    testimonial?: string;
    display_order: number;
    is_active: boolean;
}

const props = defineProps<{
    text1?: string;
    text2?: string;
    partnerLogos?: PartnerLogo[];
}>();

// Form for editing texts
const form = useForm({
    text1: props.text1 || 'Finally, a performance management platform that works your way.',
    text2: props.text2 || 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.',
});

const handleSubmit = () => {
    form.post('/system/settings/update-texts', {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

// Partner Logo Management
const logoForm = useForm({
    logos: [] as File[],
    company_name: '',
    testimonial: '',
});

const logoFileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);

const handleLogoFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFiles.value = Array.from(target.files);
        logoForm.logos = Array.from(target.files);
    }
};

const handleAddLogo = () => {
    if (selectedFiles.value.length === 0) {
        alert('Please select at least one image file');
        return;
    }

    // Create FormData for file uploads
    const formData = new FormData();
    
    // Append all files with 'logos[]' key for Laravel to recognize as array
    selectedFiles.value.forEach((file) => {
        formData.append('logos[]', file);
    });
    
    // Append optional fields
    if (logoForm.company_name) {
        formData.append('company_name', logoForm.company_name);
    }
    if (logoForm.testimonial) {
        formData.append('testimonial', logoForm.testimonial);
    }

    // Use router.post with FormData
    router.post('/system/settings/partner-logos', formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            logoForm.reset();
            logoForm.logos = [];
            selectedFiles.value = [];
            if (logoFileInput.value) {
                logoFileInput.value.value = '';
            }
        },
    });
};

const handleDeleteLogo = (id: number) => {
    if (confirm('Are you sure you want to delete this logo?')) {
        router.delete(`/system/settings/partner-logos/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Attenda - Admin Settings | System Configuration">
        <meta name="description" content="إعدادات النظام في Attenda. تكوين إعدادات النظام العامة، الإشعارات، الأمان، والتكاملات." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Edit Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Edit Settings Texts</CardTitle>
                    <CardDescription>
                        Update the text content displayed on the settings page
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <!-- Text 1 -->
                        <div class="space-y-2">
                            <Label for="text1">Text 1 (Main Heading)</Label>
                            <Input
                                id="text1"
                                v-model="form.text1"
                                type="text"
                                placeholder="Enter text 1"
                                :class="{ 'border-red-500': form.errors.text1 }"
                            />
                            <p v-if="form.errors.text1" class="text-sm text-red-500">
                                {{ form.errors.text1 }}
                            </p>
                        </div>

                        <!-- Text 2 -->
                        <div class="space-y-2">
                            <Label for="text2">Text 2 (Description)</Label>
                            <textarea
                                id="text2"
                                v-model="form.text2"
                                rows="4"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Enter text 2"
                                :class="{ 'border-red-500': form.errors.text2 }"
                            />
                            <p v-if="form.errors.text2" class="text-sm text-red-500">
                                {{ form.errors.text2 }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-[#1e3b3b] hover:bg-[#234444]"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes</span>
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Preview Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Preview</h3>

                <!-- Section 1 Preview -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ form.text1 || 'Finally, a performance management platform that works your way.' }}
                    </p>
                </div>

                <!-- Section 2 Preview -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <p class="text-lg text-gray-700 leading-relaxed">
                        {{ form.text2 || 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.' }}
                    </p>
                </div>
            </div>

            <!-- Partner Logos Management -->
            <Card>
                <CardHeader>
                    <CardTitle>Partner Logos</CardTitle>
                    <CardDescription>
                        Manage partner logos that appear on the landing page
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Add New Logo Form -->
                    <div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Add New Logo</h4>
                        <form @submit.prevent="handleAddLogo" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="logo">Logo Images <span class="text-red-500">*</span> (You can select multiple images)</Label>
                                <div class="flex items-center gap-4">
                                    <input
                                        ref="logoFileInput"
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        @change="handleLogoFileChange"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-red-500': logoForm.errors.logos }"
                                    />
                                    <Button
                                        type="submit"
                                        :disabled="logoForm.processing || selectedFiles.length === 0"
                                        class="bg-[#1e3b3b] hover:bg-[#234444]"
                                    >
                                        <Upload class="h-4 w-4 mr-2" />
                                        <span v-if="logoForm.processing">Uploading...</span>
                                        <span v-else>Upload {{ selectedFiles.length > 0 ? `(${selectedFiles.length})` : '' }}</span>
                                    </Button>
                                </div>
                                <p v-if="selectedFiles.length > 0" class="text-sm text-gray-600">
                                    {{ selectedFiles.length }} file(s) selected
                                </p>
                                <p v-if="logoForm.errors.logos" class="text-sm text-red-500">
                                    {{ logoForm.errors.logos }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="company_name">Company Name (Optional)</Label>
                                    <Input
                                        id="company_name"
                                        v-model="logoForm.company_name"
                                        type="text"
                                        placeholder="Company name"
                                        :class="{ 'border-red-500': logoForm.errors.company_name }"
                                    />
                                    <p v-if="logoForm.errors.company_name" class="text-sm text-red-500">
                                        {{ logoForm.errors.company_name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="testimonial">Testimonial (Optional)</Label>
                                    <Input
                                        id="testimonial"
                                        v-model="logoForm.testimonial"
                                        type="text"
                                        placeholder="Testimonial"
                                        :class="{ 'border-red-500': logoForm.errors.testimonial }"
                                    />
                                    <p v-if="logoForm.errors.testimonial" class="text-sm text-red-500">
                                        {{ logoForm.errors.testimonial }}
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Existing Logos -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900">Existing Logos ({{ props.partnerLogos?.length || 0 }})</h4>
                        
                        <div v-if="!props.partnerLogos || props.partnerLogos.length === 0" class="text-center py-8 text-gray-500">
                            <ImageIcon class="h-12 w-12 mx-auto mb-2 text-gray-400" />
                            <p>No logos added yet. Add your first logo above.</p>
                        </div>

                        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div
                                v-for="logo in props.partnerLogos"
                                :key="logo.id"
                                class="relative group border border-gray-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow"
                            >
                                <div class="aspect-square flex items-center justify-center bg-gray-50 rounded mb-2 overflow-hidden">
                                    <img
                                        v-if="logo.logo_url"
                                        :src="logo.logo_url"
                                        :alt="logo.company_name || 'Partner Logo'"
                                        class="max-w-full max-h-full object-contain"
                                        @error="(e) => { e.target.style.display = 'none'; }"
                                    />
                                    <div v-else class="flex flex-col items-center justify-center text-gray-400">
                                        <ImageIcon class="h-8 w-8 mb-1" />
                                        <span class="text-xs">No image</span>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p v-if="logo.company_name" class="text-sm font-medium text-gray-900 truncate">
                                        {{ logo.company_name }}
                                    </p>
                                    <p v-else class="text-xs text-gray-400">No company name</p>
                                </div>
                                <Button
                                    @click="handleDeleteLogo(logo.id)"
                                    variant="destructive"
                                    size="sm"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
