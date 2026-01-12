<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import EmployeeSidebar from '@/components/EmployeeSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    notifications?: Array<{
        id: number;
        title: string;
        message: string;
        type: string;
        read: boolean;
        created_at: string;
    }>;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    notifications: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <EmployeeSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" :notifications="notifications" />
            <slot />
        </AppContent>
    </AppShell>
</template>

