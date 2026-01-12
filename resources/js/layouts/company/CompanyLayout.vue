<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import CompanySidebar from '@/components/CompanySidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { AlertCircle } from 'lucide-vue-next';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const subscriptionExpired = computed(() => page.props.subscriptionExpired as boolean);
const showDialog = ref(false);
const isChatPage = computed(() => page.url === '/company/chat');

onMounted(() => {
    if (subscriptionExpired.value && !isChatPage.value) {
        showDialog.value = true;
    }
});

const goToChat = () => {
    window.location.href = '/company/chat';
};
</script>

<template>
    <AppShell variant="sidebar">
        <CompanySidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden relative">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div :class="{ 'blur-sm pointer-events-none': subscriptionExpired && !isChatPage }">
                <slot />
            </div>
            
            <!-- Overlay when subscription is expired -->
            <div
                v-if="subscriptionExpired && !isChatPage"
                class="absolute inset-0 z-40 bg-white/30 backdrop-blur-sm"
                @click.prevent
            />

            <!-- Dialog for subscription expiration -->
            <Dialog v-if="!isChatPage" :open="showDialog" @update:open="(value) => { if (!subscriptionExpired) showDialog = value; }">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                <AlertCircle class="h-5 w-5 text-red-600" />
                            </div>
                            <DialogTitle class="text-xl">Subscription Expired</DialogTitle>
                        </div>
                        <DialogDescription class="pt-4 text-base">
                            Your subscription has expired. Please renew your subscription to continue using our services.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="sm:justify-end">
                        <Button @click="goToChat" class="w-full sm:w-auto bg-green-600 hover:bg-green-700">
                            Chat with Us
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppContent>
    </AppShell>
</template>

