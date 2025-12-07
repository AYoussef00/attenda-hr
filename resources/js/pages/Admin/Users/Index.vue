<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Shield, Mail, Calendar, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    last_login: string | null;
    created_at: string | null;
}

const props = defineProps<{
    users: AdminUser[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/system/dashboard',
    },
    {
        title: 'Admin Users',
        href: '/system/admin-users',
    },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'super_admin',
    status: 'active',
});

const submit = () => {
    createForm.post('/system/admin-users', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'email', 'password', 'password_confirmation');
            createForm.role = 'super_admin';
            createForm.status = 'active';
        },
    });
};

const roleLabel = (role: string) => {
    switch (role) {
        case 'super_admin':
            return 'Super admin';
        case 'company_admin':
            return 'Company admin';
        case 'hr':
            return 'HR';
        case 'manager':
            return 'Manager';
        case 'user':
            return 'User';
        default:
            return role;
    }
};
</script>

<template>
    <Head title="Admin Users" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div
                v-if="flash?.success"
                class="rounded-md border border-emerald-500 bg-emerald-50 px-3 py-2 text-xs text-emerald-900"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="rounded-md border border-red-500 bg-red-50 px-3 py-2 text-xs text-red-900"
            >
                {{ flash.error }}
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Shield class="h-5 w-5" />
                                Admin Users
                            </CardTitle>
                            <CardDescription>
                                Manage system admin users and invite new admins.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Create admin user form -->
                    <form class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    v-model="createForm.name"
                                    type="text"
                                    placeholder="Full name"
                                />
                                <p
                                    v-if="createForm.errors.name"
                                    class="text-[11px] text-red-500"
                                >
                                    {{ createForm.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="email@example.com"
                                />
                                <p
                                    v-if="createForm.errors.email"
                                    class="text-[11px] text-red-500"
                                >
                                    {{ createForm.errors.email }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                />
                                <p
                                    v-if="createForm.errors.password"
                                    class="text-[11px] text-red-500"
                                >
                                    {{ createForm.errors.password }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="role">Role</Label>
                                <select
                                    id="role"
                                    v-model="createForm.role"
                                    class="flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-emerald-500/50"
                                >
                                    <option value="super_admin">Super admin</option>
                                    <option value="company_admin">Company admin</option>
                                    <option value="hr">HR</option>
                                    <option value="manager">Manager</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="createForm.status"
                                    class="flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-emerald-500/50"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex h-9 items-center justify-center rounded-full bg-emerald-700 px-4 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:opacity-50"
                                :disabled="createForm.processing"
                            >
                                Add admin user
                            </button>
                        </div>
                    </form>

                    <!-- Admin users table -->
                    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Last login
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Created at
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-if="users.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No admin users found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="user in users"
                                    :key="user.id"
                                    class="hover:bg-slate-50 transition-colors"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <UserIcon class="h-4 w-4 text-slate-400" />
                                            <span class="font-medium text-slate-900">{{ user.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">
                                        <div class="flex items-center gap-2">
                                            <Mail class="h-3 w-3 text-slate-400" />
                                            <span>{{ user.email }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-800"
                                        >
                                            {{ roleLabel(user.role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                            :class="user.status === 'active'
                                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                                : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'"
                                        >
                                            {{ user.status === 'active' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                        <div class="flex items-center gap-1">
                                            <Clock class="h-3 w-3 text-slate-400" />
                                            <span>{{ user.last_login ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                        <div class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3 text-slate-400" />
                                            <span>{{ user.created_at }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>


