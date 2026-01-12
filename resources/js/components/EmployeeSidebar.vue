<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Clock, Calendar, User, FileCheck, Receipt, TrendingUp } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/company/employee/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My Attendance',
        href: '/company/employee/attendance',
        icon: Clock,
    },
    {
        title: 'Performance',
        href: '/company/employee/performance',
        icon: TrendingUp,
    },
    {
        title: 'My Leaves',
        href: '/company/employee/leaves',
        icon: Calendar,
    },
    {
        title: 'Payslips',
        href: '/company/employee/payslips',
        icon: Receipt,
    },
    {
        title: 'Documents',
        href: '/company/employee/documents',
        icon: FileCheck,
    },
    {
        title: 'Profile',
        href: '/company/employee/profile',
        icon: User,
    },
];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="border-r border-emerald-900/40 bg-[#1e3b3b] text-emerald-50"
    >
        <SidebarHeader class="pb-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-16 px-3 rounded-2xl hover:bg-emerald-900/10 transition-colors"
                    >
                        <Link href="/company/employee/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="mt-6">
            <SidebarMenu class="px-3 space-y-1">
                <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                        class="group h-11 rounded-xl px-3 text-sm font-medium text-emerald-50/80 hover:bg-emerald-900/15 data-[active=true]:bg-amber-200/10 data-[active=true]:text-amber-100 transition-all"
                    >
                        <Link :href="item.href">
                            <component
                                :is="item.icon"
                                class="h-4 w-4 text-emerald-100/70 group-hover:text-amber-100 group-data-[active=true]:text-amber-200"
                            />
                            <span class="tracking-tight">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter class="border-t border-emerald-900/40 pt-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

