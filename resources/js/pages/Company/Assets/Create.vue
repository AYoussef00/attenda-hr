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
import { Package, ArrowLeft } from 'lucide-vue-next';

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
        title: 'Add New Asset',
        href: '/company/assets/create',
    },
];

const form = useForm({
    asset_code: '',
    type: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    cost: '',
    status: 'Available',
    warranty_end: '',
    notes: '',
});

const submit = () => {
    form.post('/company/assets', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Attenda - Add New Asset | Register Company Asset">
        <meta name="description" content="إضافة أصل جديد في Attenda. تسجيل أصل جديد للشركة مع تفاصيله، القيمة، والحالة." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Package class="h-5 w-5" />
                                Add New Asset
                            </CardTitle>
                            <CardDescription>
                                Create a new asset for your company
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/assets">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Assets
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Asset Code -->
                            <div class="grid gap-2">
                                <Label for="asset_code">
                                    Asset Code
                                </Label>
                                <Input
                                    id="asset_code"
                                    v-model="form.asset_code"
                                    type="text"
                                    placeholder="Leave empty to auto-generate (ASSET-001)"
                                    :class="{ 'border-destructive': form.errors.asset_code }"
                                />
                                <InputError :message="form.errors.asset_code" />
                                <p class="text-sm text-muted-foreground">
                                    Leave empty to auto-generate asset code
                                </p>
                            </div>

                            <!-- Type -->
                            <div class="grid gap-2">
                                <Label for="type">
                                    Type <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="type"
                                    v-model="form.type"
                                    type="text"
                                    required
                                    placeholder="e.g., Laptop, Mobile, Monitor, Car"
                                    :class="{ 'border-destructive': form.errors.type }"
                                />
                                <InputError :message="form.errors.type" />
                            </div>

                            <!-- Model -->
                            <div class="grid gap-2">
                                <Label for="model">
                                    Model <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="model"
                                    v-model="form.model"
                                    type="text"
                                    required
                                    placeholder="e.g., MacBook Pro, iPhone 14"
                                    :class="{ 'border-destructive': form.errors.model }"
                                />
                                <InputError :message="form.errors.model" />
                            </div>

                            <!-- Serial Number -->
                            <div class="grid gap-2">
                                <Label for="serial_number">
                                    Serial Number
                                </Label>
                                <Input
                                    id="serial_number"
                                    v-model="form.serial_number"
                                    type="text"
                                    placeholder="Enter serial number"
                                    :class="{ 'border-destructive': form.errors.serial_number }"
                                />
                                <InputError :message="form.errors.serial_number" />
                            </div>

                            <!-- Purchase Date -->
                            <div class="grid gap-2">
                                <Label for="purchase_date">
                                    Purchase Date <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="purchase_date"
                                    v-model="form.purchase_date"
                                    type="date"
                                    required
                                    :class="{ 'border-destructive': form.errors.purchase_date }"
                                />
                                <InputError :message="form.errors.purchase_date" />
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
                                    <option value="Available">Available</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Under_Maintenance">Under Maintenance</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Retired">Retired</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <!-- Warranty End -->
                            <div class="grid gap-2">
                                <Label for="warranty_end">
                                    Warranty End Date
                                </Label>
                                <Input
                                    id="warranty_end"
                                    v-model="form.warranty_end"
                                    type="date"
                                    :class="{ 'border-destructive': form.errors.warranty_end }"
                                />
                                <InputError :message="form.errors.warranty_end" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="grid gap-2">
                            <Label for="notes">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Additional notes about the asset"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/assets">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Asset</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

