<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ChatThreadFeedback {
    id: number;
    visitor_id: string;
    assigned_admin_id: number | null;
    assigned_admin_name: string | null;
    assigned_admin_email: string | null;
    status: 'open' | 'closed';
    unread_admin_count: number;
    last_message_at: string | null;
    ended_at: string | null;
    duration_seconds: number | null;
    resolved: boolean | null;
    issue_summary: string | null;
    messages_count: number;
}

const props = defineProps<{
    threads: ChatThreadFeedback[];
}>();

const hasAnyFeedback = computed(() =>
    props.threads.some((t) => t.ended_at || t.issue_summary !== null || t.resolved !== null),
);

function formatDuration(seconds: number | null): string {
    if (!seconds || seconds <= 0) return '-';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}
</script>

<template>
    <AdminLayout title="Chat Feedback">
        <div class="space-y-6">
            <div class="flex items-baseline justify-between gap-4">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">
                        Chat Feedback
                    </h1>
                    <p class="mt-1 text-xs text-slate-500">
                        Overview of all chat sessions and their feedback, with quick access to each conversation.
                    </p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Total chats: {{ threads.length }}</span>
                        <span v-if="hasAnyFeedback">With feedback: {{ threads.filter((t) => t.ended_at).length }}</span>
                    </div>
                </div>

                <div class="max-h-[70vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Chat ID</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Visitor</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Handled by</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Resolved</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Duration</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Ended at</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Messages</th>
                                <th class="px-4 py-2 text-left font-medium text-slate-500">Issue summary</th>
                                <th class="px-4 py-2 text-right font-medium text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="threads.length === 0">
                                <td colspan="9" class="px-4 py-6 text-center text-[11px] text-slate-400">
                                    No chats yet.
                                </td>
                            </tr>
                            <tr
                                v-for="thread in threads"
                                :key="thread.id"
                                class="hover:bg-slate-50/60"
                            >
                                <td class="px-4 py-2 whitespace-nowrap text-slate-800">
                                    #{{ thread.id }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-600">
                                    {{ thread.visitor_id }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-700">
                                    <span v-if="thread.assigned_admin_name">
                                        {{ thread.assigned_admin_name }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-[11px] text-slate-400"
                                    >
                                        Unassigned
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="thread.status === 'open'
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                            : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'"
                                    >
                                        {{ thread.status === 'open' ? 'Open' : 'Closed' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <span
                                        v-if="thread.resolved === true"
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-emerald-100"
                                    >
                                        Yes
                                    </span>
                                    <span
                                        v-else-if="thread.resolved === false"
                                        class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-600 ring-1 ring-red-100"
                                    >
                                        No
                                    </span>
                                    <span
                                        v-else
                                        class="text-[11px] text-slate-400"
                                    >
                                        -
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-700">
                                    {{ formatDuration(thread.duration_seconds) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-500">
                                    {{ thread.ended_at || '-' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-slate-600">
                                    {{ thread.messages_count }}
                                </td>
                                <td class="px-4 py-2 max-w-xs text-xs text-slate-700">
                                    <span v-if="thread.issue_summary">
                                        {{ thread.issue_summary }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-[11px] text-slate-400"
                                    >
                                        -
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <Link
                                        :href="`/system/chat?thread_id=${thread.id}`"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50"
                                    >
                                        View chat
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


