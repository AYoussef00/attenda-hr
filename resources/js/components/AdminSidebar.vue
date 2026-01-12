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
import admin from '@/routes/admin';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    Building2,
    FileText,
    LayoutGrid,
    Settings,
    Shield,
    Users,
    CreditCard,
    ClipboardList,
    BarChart3,
    MessageCircle,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const pendingRequestsCount = computed(() => {
    return (page.props.pendingRequestsCount as number) || 0;
});

// Live chat badge count (starts from initial server value then uses polling)
const chatBadgeCount = ref<number>((page.props.newChatsCount as number) || 0);

const currentUserRole = computed(() => {
    // auth.user is shared from HandleInertiaRequests
    return (page.props.auth?.user as any)?.role ?? null;
});

// Full admin navigation (super admin / other elevated roles)
const fullNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Companies',
        href: '/system/companies',
        icon: Building2,
    },
    {
        title: 'Plans',
        href: '/system/plans',
        icon: CreditCard,
    },
    {
        title: 'Subscriptions',
        href: '/system/subscriptions',
        icon: FileText,
    },
    {
        title: 'Requests',
        href: '/system/requests',
        icon: ClipboardList,
    },
    {
        title: 'Analysis',
        href: '/system/analysis',
        icon: BarChart3,
    },
    {
        title: 'Chat',
        href: '/system/chat',
        icon: MessageCircle,
    },
    {
        title: 'Chat Feedback',
        href: '/system/chat-feedback',
        icon: ClipboardList,
    },
    {
        title: 'Admin Users',
        href: '/system/admin-users',
        icon: Shield,
    },
    {
        title: 'Activity Logs',
        href: admin.dashboard(), // TODO: Update with activity logs route
        icon: Users,
    },
    {
        title: 'Settings',
        href: '/system/settings',
        icon: Settings,
    },
];

// Limited navigation for simple "user" role
const userNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Chat',
        href: '/system/chat',
        icon: MessageCircle,
    },
    {
        title: 'Chat Feedback',
        href: '/system/chat-feedback',
        icon: ClipboardList,
    },
    {
        title: 'Chat Analysis',
        href: '/system/chat-analysis',
        icon: BarChart3,
    },
];

const mainNavItems = computed<NavItem[]>(() => {
    return currentUserRole.value === 'user' ? userNavItems : fullNavItems;
});

// Simple local language toggle (UI only for now)
const currentLanguage = ref<'en' | 'ar'>('en');

const setLanguage = (lang: 'en' | 'ar') => {
    currentLanguage.value = lang;
};

let chatPollId: number | null = null;
let chatInitialized = false;

const playChatNotification = () => {
    try {
        const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioContextClass) return;

        const ctx = new AudioContextClass();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.value = 880;

        osc.connect(gain);
        gain.connect(ctx.destination);

        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);

        osc.start();
        osc.stop(ctx.currentTime + 0.25);
    } catch {
        // ignore sound errors (e.g. autoplay restrictions)
    }
};

const startChatPolling = () => {
    if (chatPollId !== null) return;

    chatPollId = window.setInterval(async () => {
        try {
            const response = await fetch('/system/chat-unread-count', {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;
            const data = await response.json();
            if (typeof data.count === 'number') {
                const next = data.count;
                // أول مرة نقرأ القيمة: ما نشغّلش الصوت
                if (!chatInitialized) {
                    chatBadgeCount.value = next;
                    chatInitialized = true;
                    return;
                }

                // لو في زياده في الكونت → حد بعت رسالة جديدة
                if (next > chatBadgeCount.value) {
                    chatBadgeCount.value = next;
                    playChatNotification();
                } else {
                    chatBadgeCount.value = next;
                }
            }
        } catch {
            // ignore polling errors
        }
    }, 3000);
};

const stopChatPolling = () => {
    if (chatPollId !== null) {
        window.clearInterval(chatPollId);
        chatPollId = null;
    }
};

onMounted(() => {
    startChatPolling();
});

onBeforeUnmount(() => {
    stopChatPolling();
});
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
                        <Link :href="admin.dashboard()">
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
                        <Link :href="item.href" class="relative">
                            <component
                                :is="item.icon"
                                class="h-4 w-4 text-emerald-100/70 group-hover:text-amber-100 group-data-[active=true]:text-amber-200"
                            />
                            <span class="tracking-tight">{{ item.title }}</span>
                            <div
                                v-if="item.title === 'Requests' && pendingRequestsCount > 0"
                                class="absolute right-1 top-1/2 -translate-y-1/2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-semibold text-white shadow-sm"
                            >
                                {{ pendingRequestsCount > 99 ? '99+' : pendingRequestsCount }}
                            </div>
                            <div
                                v-if="item.title === 'Chat' && chatBadgeCount > 0"
                                class="absolute right-1 top-1/2 -translate-y-1/2 flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-400 px-1.5 text-xs font-semibold text-slate-900 shadow-sm"
                            >
                                {{ chatBadgeCount > 99 ? '99+' : chatBadgeCount }}
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter class="border-t border-emerald-900/40 pt-3 space-y-3">
            <NavUser />
            <!-- Language switcher -->
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
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

