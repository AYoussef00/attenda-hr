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
import { CreditCard, ArrowLeft, Plus, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    plan: {
        id: number;
        name: string;
        price: number;
        yearly_price: number | null;
        max_employees: number;
        features: string[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Plans',
        href: '/system/plans',
    },
    {
        title: 'Edit Plan',
        href: `/system/plans/${props.plan.id}/edit`,
    },
];

const form = useForm({
    name: props.plan.name,
    price: props.plan.price.toString(),
    yearly_price: props.plan.yearly_price !== null ? props.plan.yearly_price.toString() : '',
    max_employees: props.plan.max_employees.toString(),
    features: [...props.plan.features],
});

const newFeature = ref('');

const addFeature = () => {
    if (newFeature.value.trim() && !form.features.includes(newFeature.value.trim())) {
        form.features.push(newFeature.value.trim());
        newFeature.value = '';
    }
};

const removeFeature = (index: number) => {
    form.features.splice(index, 1);
};

const submit = () => {
    form.put(`/system/plans/${props.plan.id}`, {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Edit Plan" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Edit Plan
                            </CardTitle>
                            <CardDescription>
                                Update subscription plan details
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/system/plans">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Plans
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Plan Name -->
                            <div class="grid gap-2">
                                <Label for="name">
                                    Plan Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    name="name"
                                    required
                                    placeholder="e.g., Basic Plan"
                                    :class="{ 'border-destructive': form.errors.name }"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Monthly Price -->
                            <div class="grid gap-2">
                                <Label for="price">
                                    Monthly Price (USD) <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="price"
                                    v-model="form.price"
                                    type="number"
                                    name="price"
                                    required
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="{ 'border-destructive': form.errors.price }"
                                />
                                <InputError :message="form.errors.price" />
                            </div>

                            <!-- Yearly Price -->
                            <div class="grid gap-2">
                                <Label for="yearly_price">
                                    Yearly Price (USD) <span class="text-xs text-muted-foreground">(optional)</span>
                                </Label>
                                <Input
                                    id="yearly_price"
                                    v-model="form.yearly_price"
                                    type="number"
                                    name="yearly_price"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="{ 'border-destructive': (form.errors as any).yearly_price }"
                                />
                                <InputError :message="(form.errors as any).yearly_price" />
                            </div>

                            <!-- Max Employees -->
                            <div class="grid gap-2">
                                <Label for="max_employees">
                                    Max Employees <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="max_employees"
                                    v-model="form.max_employees"
                                    type="number"
                                    name="max_employees"
                                    required
                                    min="1"
                                    placeholder="e.g., 100"
                                    :class="{ 'border-destructive': form.errors.max_employees }"
                                />
                                <InputError :message="form.errors.max_employees" />
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="grid gap-2">
                            <Label>Features (Optional)</Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="newFeature"
                                    type="text"
                                    placeholder="Enter a feature and press Enter or click Add"
                                    @keyup.enter.prevent="addFeature"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="addFeature"
                                >
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Add features that are included in this plan (e.g., "Unlimited Attendance Records", "Advanced Reports")
                            </p>
                            
                            <!-- Features List -->
                            <div
                                v-if="form.features.length > 0"
                                class="flex flex-wrap gap-2 mt-2"
                            >
                                <div
                                    v-for="(feature, index) in form.features"
                                    :key="index"
                                    class="flex items-center gap-2 bg-muted px-3 py-1 rounded-md"
                                >
                                    <span class="text-sm">{{ feature }}</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-4 w-4"
                                        @click="removeFeature(index)"
                                    >
                                        <X class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                            <InputError :message="form.errors.features" />
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/system/plans">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Plan</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

