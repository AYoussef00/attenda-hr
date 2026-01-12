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
import { Timer, ArrowLeft } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Shifts',
        href: '/company/shifts',
    },
    {
        title: 'Add New Shift',
        href: '/company/shifts/create',
    },
];

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
    break_minutes: 0,
    late_grace_minutes: 0,
    overtime_after: null as number | null,
});

const submit = () => {
    form.post('/company/shifts', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <Head title="Add New Shift" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Timer class="h-5 w-5" />
                                Add New Shift
                            </CardTitle>
                            <CardDescription>
                                Create a new work shift for your company
                            </CardDescription>
                        </div>
                        <Button variant="outline" as-child>
                            <Link href="/company/shifts">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Shifts
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Basic Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Shift Name -->
                                <div class="grid gap-2">
                                    <Label for="name">
                                        Shift Name <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        name="name"
                                        required
                                        placeholder="e.g. Morning Shift, Night Shift"
                                        :class="{ 'border-destructive': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>
                            </div>
                        </div>

                        <!-- Time Information -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Time Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Start Time -->
                                <div class="grid gap-2">
                                    <Label for="start_time">
                                        Start Time <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="start_time"
                                        v-model="form.start_time"
                                        type="time"
                                        name="start_time"
                                        required
                                        :class="{ 'border-destructive': form.errors.start_time }"
                                    />
                                    <InputError :message="form.errors.start_time" />
                                </div>

                                <!-- End Time -->
                                <div class="grid gap-2">
                                    <Label for="end_time">
                                        End Time <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="end_time"
                                        v-model="form.end_time"
                                        type="time"
                                        name="end_time"
                                        required
                                        :class="{ 'border-destructive': form.errors.end_time }"
                                    />
                                    <InputError :message="form.errors.end_time" />
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-semibold mb-4">
                                Additional Settings
                            </h3>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Break Minutes -->
                                <div class="grid gap-2">
                                    <Label for="break_minutes">Break Minutes</Label>
                                    <Input
                                        id="break_minutes"
                                        v-model.number="form.break_minutes"
                                        type="number"
                                        name="break_minutes"
                                        min="0"
                                        placeholder="0"
                                        :class="{ 'border-destructive': form.errors.break_minutes }"
                                    />
                                    <InputError :message="form.errors.break_minutes" />
                                    <p class="text-sm text-muted-foreground">
                                        Total break time in minutes
                                    </p>
                                </div>

                                <!-- Late Grace Minutes -->
                                <div class="grid gap-2">
                                    <Label for="late_grace_minutes">Late Grace (min)</Label>
                                    <Input
                                        id="late_grace_minutes"
                                        v-model.number="form.late_grace_minutes"
                                        type="number"
                                        name="late_grace_minutes"
                                        min="0"
                                        placeholder="0"
                                        :class="{ 'border-destructive': form.errors.late_grace_minutes }"
                                    />
                                    <InputError :message="form.errors.late_grace_minutes" />
                                    <p class="text-sm text-muted-foreground">
                                        Minutes allowed before marked late
                                    </p>
                                </div>

                                <!-- Overtime After -->
                                <div class="grid gap-2">
                                    <Label for="overtime_after">Overtime After (min)</Label>
                                    <Input
                                        id="overtime_after"
                                        v-model.number="form.overtime_after"
                                        type="number"
                                        name="overtime_after"
                                        min="0"
                                        placeholder="0"
                                        :class="{ 'border-destructive': form.errors.overtime_after }"
                                    />
                                    <InputError :message="form.errors.overtime_after" />
                                    <p class="text-sm text-muted-foreground">
                                        Minutes after which overtime starts
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-4">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                            >
                                <Link href="/company/shifts">Cancel</Link>
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Shift</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </CompanyLayout>
</template>

