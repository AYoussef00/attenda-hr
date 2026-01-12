<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart3, CheckCircle2, XCircle, MessageCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        today_closed: number;
        today_resolved: number;
        today_unresolved: number;
        avg_duration_seconds: number | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/system/dashboard',
    },
    {
        title: 'Chat Analysis',
        href: '/system/chat-analysis',
    },
];

const formattedAvgDuration = computed(() => {
    const secs = props.stats.avg_duration_seconds;
    if (!secs || secs <= 0) return '-';
    const minutes = Math.floor(secs / 60);
    const seconds = secs % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});
</script>

<template>
    <Head title="Chat Analysis" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">
                        Chat Analysis
                    </h1>
                    <p class="mt-1 text-xs text-slate-500">
                        High-level metrics about how your support team is handling chats today.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1 text-[11px] font-medium text-slate-50">
                    <BarChart3 class="h-3 w-3" />
                    <span>Today overview</span>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Chats closed today -->
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                Chats closed today
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">
                                {{ stats.today_closed }}
                            </p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <MessageCircle class="h-4 w-4" />
                        </div>
                    </div>
                </div>

                <!-- Issues resolved -->
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                Problems solved
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-700">
                                {{ stats.today_resolved }}
                            </p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                </div>

                <!-- Issues not resolved -->
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                Problems not solved
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-red-600">
                                {{ stats.today_unresolved }}
                            </p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <XCircle class="h-4 w-4" />
                        </div>
                    </div>
                </div>

                <!-- Average time to close -->
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                Average time to close
                            </p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">
                                {{ formattedAvgDuration }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Minutes:Seconds for chats closed today
                            </p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-slate-50">
                            <BarChart3 class="h-4 w-4" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


