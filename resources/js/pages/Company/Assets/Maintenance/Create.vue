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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Wrench, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    assets: Array<{
        id: number;
        asset_code: string;
        type: string;
        model: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Assets',
        href: '/company/assets',
    },
    {
        title: 'Maintenance',
        href: '/company/assets/maintenance',
    },
    {
        title: 'Create Ticket',
        href: '/company/assets/maintenance/create',
    },
];

const form = useForm({
    asset_id: '',
    maintenance_type: 'Repair',
    problem_description: '',
    cost: '0',
    vendor: '',
    start_date: new Date().toISOString().split('T')[0],
    status: 'Open',
});

const submit = () => {
    form.post('/company/assets/maintenance', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Create Maintenance Ticket" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Wrench class="h-5 w-5" />
                                Create Maintenance Ticket
                            </CardTitle>
                            <CardDescription>
                                Create a new maintenance record for an asset
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/assets/maintenance">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Maintenance
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Asset -->
                            <div class="grid gap-2">
                                <Label for="asset_id">
                                    Asset <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="asset_id"
                                    v-model="form.asset_id"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.asset_id }"
                                >
                                    <option value="">Select asset</option>
                                    <option
                                        v-for="asset in assets"
                                        :key="asset.id"
                                        :value="asset.id.toString()"
                                    >
                                        {{ asset.asset_code }} - {{ asset.type }} - {{ asset.model }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.asset_id" />
                            </div>

                            <!-- Maintenance Type -->
                            <div class="grid gap-2">
                                <Label for="maintenance_type">
                                    Maintenance Type <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="maintenance_type"
                                    v-model="form.maintenance_type"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.maintenance_type }"
                                >
                                    <option value="Repair">Repair</option>
                                    <option value="Scheduled">Scheduled</option>
                                </select>
                                <InputError :message="form.errors.maintenance_type" />
                            </div>

                            <!-- Start Date -->
                            <div class="grid gap-2">
                                <Label for="start_date">
                                    Start Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    required
                                    :class="{ 'border-destructive': form.errors.start_date }"
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>

                            <!-- Status -->
                            <div class="grid gap-2">
                                <Label for="status">
                                    Status <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                    :class="{ 'border-destructive': form.errors.status }"
                                >
                                    <option value="Open">Open</option>
                                    <option value="In_Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <!-- Cost -->
                            <div class="grid gap-2">
                                <Label for="cost">
                                    Cost <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="cost"
                                    v-model="form.cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    placeholder="0.00"
                                    :class="{ 'border-destructive': form.errors.cost }"
                                />
                                <InputError :message="form.errors.cost" />
                            </div>

                            <!-- Vendor -->
                            <div class="grid gap-2">
                                <Label for="vendor">
                                    Vendor
                                </Label>
                                <Input
                                    id="vendor"
                                    v-model="form.vendor"
                                    type="text"
                                    placeholder="Vendor name"
                                    :class="{ 'border-destructive': form.errors.vendor }"
                                />
                                <InputError :message="form.errors.vendor" />
                            </div>
                        </div>

                        <!-- Problem Description -->
                        <div class="grid gap-2">
                            <Label for="problem_description">
                                Problem Description <span class="text-destructive">*</span>
                            </Label>
                            <textarea
                                id="problem_description"
                                v-model="form.problem_description"
                                rows="4"
                                required
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Describe the problem or maintenance required"
                                :class="{ 'border-destructive': form.errors.problem_description }"
                            />
                            <InputError :message="form.errors.problem_description" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/assets/maintenance">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Ticket</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

