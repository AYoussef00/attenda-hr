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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Timer, Plus, Users, UserCheck, XCircle, Edit, Trash2, Clock } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { computed } from 'vue';

const props = defineProps<{
    shifts: Array<{
        id: number;
        name: string;
        start_time: string;
        end_time: string;
        break_minutes: number;
        late_grace_minutes: number;
        overtime_after: number | null;
        total_hours: number;
        employees_count: number;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
    {
        title: 'Shifts',
        href: '/company/shifts',
    },
];

// Get flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

// Delete shift
const deleteShift = (id: number) => {
    if (confirm('Are you sure you want to delete this shift?')) {
        router.delete(`/company/shifts/${id}`, {
            onSuccess: () => {
                // Success message will be shown via flash
            },
        });
    }
};
</script>

<template>
    <Head title="Attenda - Shifts Management | Work Schedule Planning">
        <meta name="description" content="إدارة الورديات في Attenda. إنشاء وتنظيم جداول العمل، الورديات، وأوقات الدوام للموظفين." />
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
                <UserCheck class="h-4 w-4" />
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
                                <Timer class="h-5 w-5" />
                                Shifts
                            </CardTitle>
                            <CardDescription>
                                Manage your company work shifts
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/company/shifts/create">
                                <Plus class="h-4 w-4" />
                                Add New Shift
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Shift Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Start Time
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        End Time
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Break (min)
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Total Hours
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                                        Employees Count
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="shifts.length === 0"
                                    class="border-b"
                                >
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No shifts found
                                    </td>
                                </tr>
                                <tr
                                    v-for="shift in shifts"
                                    :key="shift.id"
                                    class="border-b hover:bg-muted/50"
                                >
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ shift.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4" />
                                            {{ shift.start_time }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4" />
                                            {{ shift.end_time }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ shift.break_minutes }} min
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ shift.total_hours }} hrs
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <Users class="h-4 w-4 text-muted-foreground" />
                                            {{ shift.employees_count }} employee{{ shift.employees_count !== 1 ? 's' : '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                            >
                                                <Link :href="`/company/shifts/${shift.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                @click="deleteShift(shift.id)"
                                            >
                                                <Trash2 class="h-4 w-4 text-destructive" />
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
    </CompanyLayout>
</template>

