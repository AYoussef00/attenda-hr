<script setup lang="ts">
import EmployeeLayout from '@/layouts/employee/EmployeeLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import {
    FileCheck,
    Download,
    File,
    Image,
    FileText,
    AlertCircle,
    CheckCircle2,
    Upload,
    Plus,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface DocumentType {
    id: number;
    name_ar: string;
    name_en: string;
    slug: string;
    is_default: boolean;
}

interface Document {
    id: number;
    title: string;
    document_type_id: number | null;
    type: DocumentType | null;
    file_path: string;
    file_type: string | null;
    issued_date: string | null;
    expiry_date: string | null;
    uploaded_by: number | null;
    uploaded_by_name: string;
    note: string | null;
    status: string;
    created_at: string;
}

const props = defineProps<{
    employee: {
        id: number;
        name: string;
        employee_code: string;
    };
    documents: Document[];
    missingDocumentTypes: DocumentType[];
    availableDocumentTypes: DocumentType[];
}>();

const showUploadDialog = ref(false);
const selectedDocumentType = ref<DocumentType | null>(null);
const fileInputRefs = ref<Record<number, HTMLInputElement | null>>({});

const form = useForm({
    title: '',
    document_type_id: null as number | null,
    file: null as File | null,
    issued_date: '',
    expiry_date: '',
    note: '',
});

const openUploadDialog = (documentType: DocumentType | null = null) => {
    selectedDocumentType.value = documentType;
    if (documentType) {
        form.document_type_id = documentType.id;
        form.title = documentType.name_en;
    } else {
        form.document_type_id = null;
        form.title = '';
    }
    showUploadDialog.value = true;
};

const handleQuickUpload = (documentType: DocumentType) => {
    // Create a temporary file input
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
    input.onchange = (e: Event) => {
        const target = e.target as HTMLInputElement;
        if (target.files && target.files[0]) {
            // Use the existing form but update it with the document type data
            form.title = documentType.name_en;
            form.document_type_id = documentType.id;
            form.file = target.files[0];
            form.issued_date = '';
            form.expiry_date = '';
            form.note = '';

            form.post('/company/employee/documents', {
                forceFormData: true,
                onSuccess: () => {
                    form.reset();
                },
            });
        }
    };
    input.click();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.file = target.files[0];
    }
};

const submitUpload = () => {
    form.post('/company/employee/documents', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            selectedDocumentType.value = null;
            showUploadDialog.value = false;
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
    },
    {
        title: 'Documents',
        href: '/company/employee/documents',
    },
];

const getFileIcon = (fileType: string | null) => {
    if (!fileType) return File;
    if (fileType === 'image') return Image;
    if (fileType === 'pdf') return FileText;
    return File;
};

const downloadDocument = (document: Document) => {
    window.open(`/storage/${document.file_path}`, '_blank');
};

const isExpired = (expiryDate: string | null) => {
    if (!expiryDate) return false;
    return new Date(expiryDate) < new Date();
};
</script>

<template>
    <Head title="Attenda - My Documents | Personal Documents & Files">
        <meta name="description" content="مستنداتي في Attenda. عرض وإدارة المستندات الشخصية، الوثائق، الشهادات، والملفات المرفقة." />
    </Head>

    <EmployeeLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-2xl bg-slate-50 p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1.5">
                    <p class="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">
                        Documents
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        My Documents
                    </h1>
                    <p class="text-sm text-slate-500">
                        View your uploaded documents and required documents
                    </p>
                </div>
                <Dialog v-model:open="showUploadDialog">
                    <DialogTrigger as-child>
                        <Button class="bg-[#1e3b3b] hover:bg-[#234444] text-emerald-50">
                            <Plus class="h-4 w-4 mr-2" />
                            Upload Document
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ selectedDocumentType ? `Upload ${selectedDocumentType.name_en}` : 'Upload New Document' }}
                            </DialogTitle>
                            <DialogDescription>
                                Upload a document to your profile. All fields marked with * are required.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitUpload" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="title">
                                    Document Title <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter document title"
                                    required
                                />
                                <InputError :message="form.errors.title" />
                            </div>

                            <div class="space-y-2">
                                <Label for="document_type_id">
                                    Document Type
                                </Label>
                                <select
                                    id="document_type_id"
                                    v-model="form.document_type_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!!selectedDocumentType"
                                >
                                    <option :value="null">Select document type</option>
                                    <option
                                        v-for="type in availableDocumentTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.name_en }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.document_type_id" />
                            </div>

                            <div class="space-y-2">
                                <Label for="file">
                                    File <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    @change="handleFileChange"
                                    required
                                />
                                <p class="text-xs text-slate-500">
                                    Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max: 10MB)
                                </p>
                                <InputError :message="form.errors.file" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="issued_date">
                                        Issued Date
                                    </Label>
                                    <Input
                                        id="issued_date"
                                        v-model="form.issued_date"
                                        type="date"
                                    />
                                    <InputError :message="form.errors.issued_date" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="expiry_date">
                                        Expiry Date
                                    </Label>
                                    <Input
                                        id="expiry_date"
                                        v-model="form.expiry_date"
                                        type="date"
                                        :min="form.issued_date"
                                    />
                                    <InputError :message="form.errors.expiry_date" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="note">
                                    Note
                                </Label>
                                <textarea
                                    id="note"
                                    v-model="form.note"
                                    name="note"
                                    rows="3"
                                    placeholder="Add any additional notes..."
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.note }"
                                />
                                <InputError :message="form.errors.note" />
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="() => { showUploadDialog = false; selectedDocumentType = null; form.reset(); }"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-[#1e3b3b] hover:bg-[#234444]"
                                >
                                    <Upload class="h-4 w-4 mr-2" />
                                    {{ form.processing ? 'Uploading...' : 'Upload Document' }}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Uploaded Documents Card -->
                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                            Uploaded Documents
                        </CardTitle>
                        <CardDescription>
                            Documents that have been uploaded to your profile
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="documents.length === 0"
                            class="py-8 text-center"
                        >
                            <FileText class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm text-slate-400">
                                No documents uploaded yet.
                            </p>
                        </div>
                        <div
                            v-else
                            class="space-y-3"
                        >
                            <div
                                v-for="document in documents"
                                :key="document.id"
                                class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 hover:bg-slate-50 transition-colors"
                            >
                                <div class="flex items-center gap-4 flex-1">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50"
                                    >
                                        <component
                                            :is="getFileIcon(document.file_type)"
                                            class="h-5 w-5 text-emerald-600"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-slate-900 truncate">
                                                {{ document.title }}
                                            </p>
                                            <Badge
                                                v-if="document.type"
                                                variant="outline"
                                                class="text-[10px] shrink-0"
                                            >
                                                {{ document.type.name_en }}
                                            </Badge>
                                            <Badge
                                                v-if="document.status === 'expired'"
                                                variant="destructive"
                                                class="text-[10px] shrink-0"
                                            >
                                                Expired
                                            </Badge>
                                            <Badge
                                                v-else-if="document.status === 'active'"
                                                variant="default"
                                                class="text-[10px] shrink-0"
                                            >
                                                Active
                                            </Badge>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
                                            <span v-if="document.file_type" class="capitalize">
                                                {{ document.file_type }}
                                            </span>
                                            <span v-if="document.issued_date">
                                                • Issued: {{ new Date(document.issued_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                            </span>
                                            <span v-if="document.expiry_date">
                                                • Expires: {{ new Date(document.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="document.note"
                                            class="text-xs text-slate-400 mt-1 line-clamp-1"
                                        >
                                            {{ document.note }}
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="shrink-0"
                                    @click="downloadDocument(document)"
                                >
                                    <Download class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Required Documents Card -->
                <Card class="border-0 bg-white shadow-sm rounded-2xl">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <AlertCircle class="h-4 w-4 text-amber-600" />
                            Required Documents
                        </CardTitle>
                        <CardDescription>
                            Document types that need to be uploaded
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="missingDocumentTypes.length === 0"
                            class="py-8 text-center"
                        >
                            <CheckCircle2 class="mx-auto h-12 w-12 text-emerald-300 mb-3" />
                            <p class="text-sm text-slate-400">
                                All required documents have been uploaded.
                            </p>
                        </div>
                        <div
                            v-else
                            class="space-y-2"
                        >
                            <div
                                v-for="type in missingDocumentTypes"
                                :key="type.id"
                                class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50/50 p-3"
                            >
                                <div class="flex items-center gap-3 flex-1">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100"
                                    >
                                        <FileText class="h-4 w-4 text-amber-600" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900">
                                            {{ type.name_en }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Not uploaded yet
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        class="text-[10px] border-amber-300 text-amber-700"
                                    >
                                        Required
                                    </Badge>
                                    <Button
                                        size="sm"
                                        class="bg-[#1e3b3b] hover:bg-[#234444] text-emerald-50 h-8 px-3 text-xs"
                                        @click="handleQuickUpload(type)"
                                    >
                                        <Upload class="h-3 w-3 mr-1" />
                                        Upload
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </EmployeeLayout>
</template>

