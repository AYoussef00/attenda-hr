<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
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
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { ClipboardList, Trash2, CheckCircle2, XCircle, AlertCircle, Mail, Phone, Building2, Users, Globe, Clock } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed, ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';

const props = defineProps<{
    requests: Array<{
        id: number;
        first_name: string;
        business_email: string;
        company_name: string;
        phone_number: string;
        number_of_employees: string;
        company_headquarters: string;
        choose_time_slot: string;
        status: string;
        notes: string | null;
        handled_by_name: string | null;
        contacted_at: string | null;
        created_at: string;
    }>;
    filters: {
        status: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Requests',
        href: '/system/requests',
    },
];

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'pending':
            return 'secondary';
        case 'contacted':
            return 'default';
        case 'completed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'pending':
            return 'Pending';
        case 'contacted':
            return 'Contacted';
        case 'completed':
            return 'Completed';
        case 'cancelled':
            return 'Cancelled';
        default:
            return status;
    }
};

// Delete request
const deleteRequest = (id: number) => {
    if (confirm('Are you sure you want to delete this request?')) {
        router.delete(`/system/requests/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};

// Update request status
const updateRequestForm = useForm({
    status: '',
    notes: '',
});

const selectedRequest = ref<typeof props.requests[0] | null>(null);
const showUpdateDialog = ref(false);

const openUpdateDialog = (request: typeof props.requests[0]) => {
    selectedRequest.value = request;
    updateRequestForm.status = request.status;
    updateRequestForm.notes = request.notes || '';
    showUpdateDialog.value = true;
};

const closeUpdateDialog = () => {
    showUpdateDialog.value = false;
    selectedRequest.value = null;
    updateRequestForm.reset();
};

const submitUpdate = () => {
    if (selectedRequest.value) {
        updateRequestForm.put(`/system/requests/${selectedRequest.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeUpdateDialog();
            },
        });
    }
};

// Filter by status
const filterStatus = ref(props.filters.status);

const applyFilter = () => {
    router.get('/system/requests', { status: filterStatus.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Attenda - Demo Requests | Contact & Demo Requests">
        <meta name="description" content="طلبات العرض التوضيحي في Attenda. عرض طلبات الشركات للتعرف على النظام، متابعة الطلبات، والرد عليها." />
    </Head>

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

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <ClipboardList class="h-5 w-5" />
                                Demo Requests
                            </CardTitle>
                            <CardDescription>
                                Manage and review all demo requests from potential customers
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filter -->
                    <div class="mb-6 flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <Label for="status-filter" class="text-sm font-medium">Filter by Status:</Label>
                            <select
                                id="status-filter"
                                v-model="filterStatus"
                                @change="applyFilter"
                                class="flex h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="all">All Requests</option>
                                <option value="pending">Pending</option>
                                <option value="contacted">Contacted</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <!-- Requests Table -->
                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Company
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Details
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-if="requests.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No requests found
                                    </td>
                                </tr>
                                <tr
                                    v-for="request in requests"
                                    :key="request.id"
                                    class="hover:bg-slate-50 transition-colors"
                                >
                                    <!-- Name -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-slate-900">{{ request.first_name }}</div>
                                    </td>

                                    <!-- Company -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 text-slate-400" />
                                            <span class="text-slate-900">{{ request.company_name }}</span>
                                        </div>
                                    </td>

                                    <!-- Contact -->
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 text-slate-600">
                                                <Mail class="h-3 w-3" />
                                                <span class="text-xs">{{ request.business_email }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-slate-600">
                                                <Phone class="h-3 w-3" />
                                                <span class="text-xs">+966 {{ request.phone_number }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Details -->
                                    <td class="px-4 py-3">
                                        <div class="space-y-1 text-xs text-slate-600">
                                            <div class="flex items-center gap-2">
                                                <Users class="h-3 w-3" />
                                                <span>{{ request.number_of_employees }} employees</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Globe class="h-3 w-3" />
                                                <span>{{ request.company_headquarters }}</span>
                                            </div>
                                            <div v-if="request.choose_time_slot === 'Yes'" class="flex items-center gap-2 text-amber-600">
                                                <Clock class="h-3 w-3" />
                                                <span>Wants time slot</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <Badge :variant="getStatusVariant(request.status)">
                                            {{ getStatusLabel(request.status) }}
                                        </Badge>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
                                        {{ new Date(request.created_at).toLocaleDateString() }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="openUpdateDialog(request)"
                                                class="h-8"
                                            >
                                                Update
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="deleteRequest(request.id)"
                                                class="h-8"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Update Dialog -->
        <Dialog :open="showUpdateDialog" @update:open="showUpdateDialog = $event">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Update Request Status</DialogTitle>
                    <DialogDescription>
                        Update the status and add notes for this demo request
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitUpdate" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="updateRequestForm.status"
                            required
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="pending">Pending</option>
                            <option value="contacted">Contacted</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="updateRequestForm.notes"
                            rows="4"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Add any notes about this request..."
                        />
                    </div>

                    <div v-if="selectedRequest" class="text-sm text-slate-600 space-y-1">
                        <p><strong>Request Details:</strong></p>
                        <p>Name: {{ selectedRequest.first_name }}</p>
                        <p>Company: {{ selectedRequest.company_name }}</p>
                        <p>Email: {{ selectedRequest.business_email }}</p>
                        <p>Phone: +966 {{ selectedRequest.phone_number }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeUpdateDialog"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="updateRequestForm.processing"
                        >
                            <span v-if="updateRequestForm.processing">Updating...</span>
                            <span v-else>Update Request</span>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
