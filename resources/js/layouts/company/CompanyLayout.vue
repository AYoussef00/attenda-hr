<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import CompanySidebar from '@/components/CompanySidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <CompanySidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>

