<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItemType } from '@/types';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
        notifications?: Array<{
            id: number;
            title: string;
            message: string;
            type: string;
            read: boolean;
            created_at: string;
        }>;
    }>(),
    {
        breadcrumbs: () => [],
        notifications: () => [],
    },
);

const unreadCount = computed(
    () => props.notifications?.filter((n) => !n.read).length || 0,
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2">
            <!-- Notifications -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative h-9 w-9"
                    >
                        <Bell class="h-5 w-5" />
                        <Badge
                            v-if="unreadCount > 0"
                            class="absolute -right-1 -top-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                            variant="destructive"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </Badge>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="w-80 max-h-[400px] overflow-y-auto"
                >
                    <div class="p-2">
                        <div class="flex items-center justify-between px-2 py-1.5">
                            <h3 class="text-sm font-semibold">Notifications</h3>
                            <Badge
                                v-if="unreadCount > 0"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ unreadCount }} new
                            </Badge>
                        </div>
                    </div>
                    <DropdownMenuSeparator />
                    <div v-if="notifications && notifications.length === 0" class="p-4 text-center text-sm text-slate-500">
                        No notifications
                    </div>
                    <template v-else>
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="px-2 py-1"
                        >
                            <DropdownMenuItem
                                :class="[
                                    'flex flex-col items-start gap-1 p-3 rounded-lg cursor-pointer',
                                    !notification.read ? 'bg-blue-50 dark:bg-blue-950/20' : '',
                                ]"
                            >
                                <div class="flex items-start justify-between w-full">
                                    <div class="flex-1">
                                        <p
                                            :class="[
                                                'text-sm font-medium',
                                                !notification.read
                                                    ? 'text-slate-900'
                                                    : 'text-slate-600',
                                            ]"
                                        >
                                            {{ notification.title }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{ notification.message }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            {{
                                                new Date(
                                                    notification.created_at,
                                                ).toLocaleString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                })
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="!notification.read"
                                        class="h-2 w-2 rounded-full bg-blue-600 shrink-0 mt-1"
                                    />
                                </div>
                            </DropdownMenuItem>
                        </div>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
