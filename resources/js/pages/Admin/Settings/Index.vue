<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

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

const props = defineProps<{
    text1?: string;
    text2?: string;
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
        </div>
    </AdminLayout>
</template>
