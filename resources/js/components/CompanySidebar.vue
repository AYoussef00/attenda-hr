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
import { ref } from 'vue';
import {
    Building2,
    LayoutGrid,
    Users,
    Calendar,
    Clock,
    Settings,
    FolderTree,
    Timer,
    CreditCard,
    TrendingUp,
    Briefcase,
    Package,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();

// Local language toggle (UI only)
const currentLanguage = ref<'en' | 'ar'>('en');

const setLanguage = (lang: 'en' | 'ar') => {
    currentLanguage.value = lang;
};

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Employees',
        href: '/company/employees',
        icon: Users,
    },
    {
        title: 'Departments',
        href: '/company/departments',
        icon: FolderTree,
    },
    {
        title: 'Shifts',
        href: '/company/shifts',
        icon: Timer,
    },
    {
        title: 'Attendance',
        href: '/company/attendance',
        icon: Clock,
    },
    {
        title: 'Leaves',
        href: '/company/leaves',
        icon: Calendar,
    },
    {
        title: 'Performance',
        href: '/company/performance',
        icon: TrendingUp,
    },
    {
        title: 'Payroll',
        href: '/company/payroll',
        icon: CreditCard,
    },
    {
        title: 'Job Applications',
        href: '/company/jobs',
        icon: Briefcase,
    },
    {
        title: 'Assets',
        href: '/company/assets',
        icon: Package,
    },
    {
        title: 'Subscription',
        href: '/company/subscription',
        icon: Building2,
    },
    {
        title: 'Settings',
        href: '/company/settings',
        icon: Settings,
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
                        <Link href="/company/dashboard">
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

        <SidebarFooter class="border-t border-emerald-900/40 pt-3 space-y-3">
            <!-- Language switcher (above user name) -->
            <div class="flex items-center justify-center gap-2 rounded-full bg-emerald-900/40 px-2 py-1 text-[11px]">
                <button
                    type="button"
                    class="rounded-full px-3 py-0.5 font-medium transition"
                    :class="currentLanguage === 'en' ? 'bg-emerald-50 text-emerald-900 shadow-sm' : 'text-emerald-100/70 hover:text-amber-100'"
                    @click="setLanguage('en')"
                >
                    English
                </button>
                <span class="text-emerald-100/40">|</span>
                <button
                    type="button"
                    class="rounded-full px-3 py-0.5 font-medium transition"
                    :class="currentLanguage === 'ar' ? 'bg-emerald-50 text-emerald-900 shadow-sm' : 'text-emerald-100/70 hover:text-amber-100'"
                    @click="setLanguage('ar')"
                >
                    عربي
                </button>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

