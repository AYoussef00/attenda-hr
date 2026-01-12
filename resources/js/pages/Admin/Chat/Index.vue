<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { MessageCircle, Clock } from 'lucide-vue-next';
import { ref, watch, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';

const props = defineProps<{
    threads: Array<{
        id: number;
        visitor_id: string;
        assigned_admin_id: number | null;
        status: string;
        unread_admin_count: number;
        last_message_at: string | null;
        last_message_preview: string | null;
    }>;
    activeThread: {
        id: number;
        visitor_id: string;
        assigned_admin_id: number | null;
        status: string;
    } | null;
    messages: Array<{
        id: number;
        from: 'visitor' | 'admin';
        text: string;
        created_at: string;
        created_at_human: string;
    }>;
}>();

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user as { id: number; name?: string } | undefined);
const currentUserName = computed(() => {
    return currentUser.value?.name || 'Attenda support';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/system/dashboard',
    },
    {
        title: 'Chat',
        href: '/system/chat',
    },
];

const openThread = (id: number) => {
    router.get('/system/chat', { thread_id: id }, { preserveState: true, preserveScroll: true });
};

const replyForm = useForm({
    message: '',
});

const sendReply = () => {
    if (!props.activeThread) return;
    if (!replyForm.message.trim()) return;

    replyForm.post(`/system/chat/${props.activeThread.id}/messages`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            replyForm.reset();
        },
    });
};

// Quick reply templates
type ChatTemplate = {
    id: string;
    title: string;
    description: string;
    text: string;
};

const templates = ref<ChatTemplate[]>([
    {
        id: 'welcome',
        title: 'Welcome template',
        description: 'Warm greeting when conversation starts',
        text: 'Welcome to Attenda 👋 This is Sarah from support. How can I help you today?',
    },
    {
        id: 'closing',
        title: 'Closing template',
        description: 'End the conversation politely',
        text: 'Thank you for chatting with Attenda support. If you need anything else, just reach out any time. Have a great day! ✨',
    },
]);

const useTemplate = (template: ChatTemplate) => {
    if (!props.activeThread) return;
    const name = currentUserName.value;
    // Replace hardcoded agent name (Sarah) with current user name
    const personalized = template.text.replace(/Sarah/g, name);
    replyForm.message = personalized;
    sendReply();
};

const newTemplateTitle = ref('');
const newTemplateDescription = ref('');
const newTemplateText = ref('');
const editingTemplateId = ref<string | null>(null);

const saveTemplate = () => {
    if (!newTemplateTitle.value.trim() || !newTemplateText.value.trim()) {
        return;
    }

    const payload: ChatTemplate = {
        id: editingTemplateId.value ?? `${Date.now()}`,
        title: newTemplateTitle.value.trim(),
        description: newTemplateDescription.value.trim() || 'Custom quick reply',
        text: newTemplateText.value.trim(),
    };

    if (editingTemplateId.value) {
        const index = templates.value.findIndex(t => t.id === editingTemplateId.value);
        if (index !== -1) {
            templates.value[index] = payload;
        }
    } else {
        templates.value.push(payload);
    }

    newTemplateTitle.value = '';
    newTemplateDescription.value = '';
    newTemplateText.value = '';
    editingTemplateId.value = null;
};

const startEditTemplate = (template: ChatTemplate) => {
    editingTemplateId.value = template.id;
    newTemplateTitle.value = template.title;
    newTemplateDescription.value = template.description;
    newTemplateText.value = template.text;
};

const deleteTemplate = (id: string) => {
    templates.value = templates.value.filter(t => t.id !== id);
    if (editingTemplateId.value === id) {
        editingTemplateId.value = null;
        newTemplateTitle.value = '';
        newTemplateDescription.value = '';
        newTemplateText.value = '';
    }
};

// End chat dialog state
const showEndDialog = ref(false);
const capturedDurationSeconds = ref(0);
const endForm = useForm({
    resolved: true as boolean,
    issue: '',
    duration_seconds: 0,
});

// AI summary generation state
const isGeneratingSummary = ref(false);

const formattedCapturedDuration = computed(() => {
    const total = capturedDurationSeconds.value;
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const confirmEndChat = () => {
    if (!props.activeThread) return;
    endForm.duration_seconds = capturedDurationSeconds.value;

    endForm.post(`/system/chat/${props.activeThread.id}/end`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showEndDialog.value = false;
        },
    });
};

const cancelEndChat = () => {
    showEndDialog.value = false;
    if (props.activeThread) {
        // استئناف التايمر لو رجع عن الإنهاء
        startTimerForThread(props.activeThread.id, capturedDurationSeconds.value);
    }
};

const endChat = () => {
    if (!props.activeThread) return;
    capturedDurationSeconds.value = timerSeconds.value;
    stopTimer();
    endForm.resolved = true;
    endForm.issue = '';
    endForm.duration_seconds = capturedDurationSeconds.value;
    showEndDialog.value = true;
};

const generateIssueSummary = async () => {
    if (!props.activeThread) return;
    if (isGeneratingSummary.value) return;

    isGeneratingSummary.value = true;
    try {
        const response = await fetch(`/system/chat/${props.activeThread.id}/summarize`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) return;

        const data = await response.json();
        if (data.summary) {
            endForm.issue = data.summary;
        }
    } catch (e) {
        // ignore summarisation errors for now
    } finally {
        isGeneratingSummary.value = false;
    }
};

// Local messages state for live updates
const localMessages = ref(props.messages || []);
watch(
    () => props.messages,
    (val) => {
        localMessages.value = val || [];
    },
);

// Local threads state for live updates
const localThreads = ref(props.threads || []);
watch(
    () => props.threads,
    (val) => {
        localThreads.value = val || [];
    },
);

const conversationBodyRef = ref<HTMLElement | null>(null);

const scrollConversationToBottom = () => {
    nextTick(() => {
        if (conversationBodyRef.value) {
            conversationBodyRef.value.scrollTop = conversationBodyRef.value.scrollHeight;
        }
    });
};

watch(
    () => localMessages.value,
    () => {
        scrollConversationToBottom();
    },
    { deep: true },
);

let pollId: number | null = null;
let threadsPollId: number | null = null;

// Timer state for active chat
const timerSeconds = ref(0);
const timerIntervalId = ref<number | null>(null);
const timerRunningForThreadId = ref<number | null>(props.activeThread?.id ?? null);

const formattedTimer = computed(() => {
    const total = timerSeconds.value;
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const startPolling = (threadId: number) => {
    if (pollId !== null) return;

    pollId = window.setInterval(async () => {
        try {
            const response = await fetch(`/system/chat/${threadId}/messages`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            if (Array.isArray(data.messages)) {
                localMessages.value = data.messages;
            }
        } catch (e) {
            // ignore polling errors for now
        }
    }, 3000);
};

const stopPolling = () => {
    if (pollId !== null) {
        window.clearInterval(pollId);
        pollId = null;
    }
};

const startTimerForThread = (threadId: number, initialSeconds = 0) => {
    timerRunningForThreadId.value = threadId;
    timerSeconds.value = initialSeconds;
    if (timerIntervalId.value !== null) {
        window.clearInterval(timerIntervalId.value);
    }
    timerIntervalId.value = window.setInterval(() => {
        timerSeconds.value += 1;
    }, 1000);
};

const stopTimer = () => {
    if (timerIntervalId.value !== null) {
        window.clearInterval(timerIntervalId.value);
        timerIntervalId.value = null;
    }
    timerRunningForThreadId.value = null;
    timerSeconds.value = 0;
};

const startThreadsPolling = () => {
    if (threadsPollId !== null) return;

    threadsPollId = window.setInterval(async () => {
        try {
            const response = await fetch('/system/chat/threads', {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            if (Array.isArray(data.threads)) {
                // Update threads list reactively without full page refresh
                // We mutate props.threads indirectly by replacing its reference in a local ref
                localThreads.value = data.threads as any;
            }
        } catch (e) {
            // ignore polling errors
        }
    }, 3000);
};

const stopThreadsPolling = () => {
    if (threadsPollId !== null) {
        window.clearInterval(threadsPollId);
        threadsPollId = null;
    }
};

onMounted(() => {
    if (props.activeThread) {
        startPolling(props.activeThread.id);
        scrollConversationToBottom();
        startTimerForThread(props.activeThread.id);
    }

    startThreadsPolling();
});

watch(
    () => props.activeThread?.id,
    (id) => {
        stopPolling();
        stopTimer();
        if (id) {
            startPolling(id);
            startTimerForThread(id);
        }
    },
);

onBeforeUnmount(() => {
    stopPolling();
    stopThreadsPolling();
    stopTimer();
});

const acceptThread = (id: number) => {
    router.post(`/system/chat/${id}/accept`, undefined, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Attenda - Visitor Chat | Customer Support Chat">
        <meta name="description" content="دردشة الزوار في Attenda. إدارة محادثات الدعم الفني مع الزوار، الرد على الاستفسارات، وتقديم المساعدة." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col min-h-0 rounded-3xl bg-slate-50 p-4 shadow-inner">
            <div class="flex h-[calc(100vh-7.5rem)] rounded-3xl bg-white shadow-sm border border-slate-200 overflow-hidden">
                <!-- Left sidebar: chats list -->
                <aside class="flex w-72 flex-col bg-slate-50 px-4 py-4 border-r border-slate-200">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Chats
                    </h2>

                    <div v-if="threads.length === 0" class="text-[11px] text-slate-500">
                        No conversations yet.
                    </div>
                    <div v-else class="space-y-3">
                        <button
                            v-for="thread in localThreads"
                            :key="thread.id"
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl bg-white px-3 py-3 text-xs font-medium text-slate-900 shadow-sm transition hover:shadow-md"
                            :class="activeThread && activeThread.id === thread.id ? 'shadow-md' : ''"
                            @click="openThread(thread.id)"
                        >
                            <div class="flex items-center gap-2">
                                <span>Chat #{{ thread.id }}</span>
                                <span
                                    v-if="timerRunningForThreadId === thread.id"
                                    class="text-[10px] font-medium text-slate-400"
                                >
                                    {{ formattedTimer }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="!thread.assigned_admin_id"
                                    type="button"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-3 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100"
                                    @click.stop="acceptThread(thread.id)"
                                >
                                    Accept
                                </button>
                            <span
                                v-if="thread.unread_admin_count > 0"
                                class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-400 px-1.5 text-[11px] font-semibold text-slate-900 shadow-sm"
                            >
                                {{ thread.unread_admin_count > 99 ? '99+' : thread.unread_admin_count }}
                            </span>
                            </div>
                        </button>
                    </div>
                </aside>

                <!-- Middle vertical divider -->
                <div class="w-[3px] bg-slate-200"></div>

                <!-- Right side: conversation area + templates -->
                <section class="flex flex-1 min-h-0 flex-row">
                    <!-- Conversation body -->
                    <div class="flex flex-1 min-h-0 flex-col justify-center px-8 py-4 text-sm">
                        <div
                            v-if="!activeThread"
                            class="text-center text-xs text-slate-400"
                        >
                            Select a chat from the left to start a conversation with the customer.
                        </div>

                        <div v-else class="flex h-full flex-col justify-between">
                            <!-- Require accept before seeing messages -->
                            <div
                                v-if="!activeThread.assigned_admin_id"
                                class="flex flex-1 flex-col items-center justify-center text-center text-xs text-slate-400"
                            >
                                <p class="mb-2 font-medium text-slate-600">
                                    Accept this chat to start the conversation.
                                </p>
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center justify-center rounded-full bg-slate-900 px-4 text-[11px] font-semibold text-white shadow-sm hover:bg-slate-800"
                                    @click="acceptThread(activeThread.id)"
                                >
                                    Accept chat
                                </button>
                            </div>

                            <template v-else>
                            <!-- Messages (scrollable area) -->
                            <div
                                ref="conversationBodyRef"
                                class="flex flex-1 flex-col gap-3 overflow-y-auto py-3"
                            >
                                <div
                                    v-if="localMessages.length === 0"
                                    class="flex flex-1 items-center justify-center text-xs text-slate-400"
                                >
                                    No messages in this conversation yet.
                                </div>
                                <div
                                    v-for="message in localMessages"
                                    :key="message.id"
                                >
                                    <!-- Visitor message (left) -->
                                    <div
                                        v-if="message.from === 'visitor'"
                                        class="flex items-end gap-2"
                                    >
                                        <div
                                            class="h-7 w-7 flex-shrink-0 rounded-full bg-slate-900 text-[11px] font-semibold text-white flex items-center justify-center"
                                        >
                                            v
                                        </div>
                                        <div class="flex flex-col">
                                            <div
                                                class="inline-block max-w-xs break-words rounded-2xl bg-white px-4 py-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200 leading-snug"
                                            >
                                                {{ message.text }}
                                            </div>
                                            <span class="mt-1 text-[10px] text-slate-400">
                                                {{ message.created_at_human }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Admin message (right) -->
                                    <div
                                        v-else
                                        class="mt-1 flex items-end justify-end gap-2"
                                    >
                                        <div class="flex flex-col items-end">
                                            <div
                                                class="inline-block max-w-xs break-words rounded-2xl bg-slate-900 px-4 py-2 text-sm text-white shadow-sm leading-snug"
                                            >
                                                {{ message.text }}
                                            </div>
                                            <span class="mt-1 text-[10px] text-slate-400">
                                                {{ message.created_at_human }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply box -->
                            <div class="mt-4 border-t border-slate-200 pt-3">
                                <form
                                    class="flex items-center gap-2 rounded-full border border-slate-300 bg-slate-50 px-4 py-2"
                                    @submit.prevent="sendReply"
                                >
                                    <input
                                        v-model="replyForm.message"
                                        type="text"
                                        placeholder="Type a reply to this customer..."
                                        class="h-9 flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
                                    />
                                    <button
                                        type="submit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-white text-xs"
                                        :disabled="replyForm.processing || !replyForm.message.trim()"
                                    >
                                        Send
                                    </button>
                                </form>
                                <p
                                    v-if="replyForm.errors.message"
                                    class="mt-1 text-[11px] text-red-500"
                                >
                                    {{ replyForm.errors.message }}
                                </p>
                            </div>
                            </template>
                        </div>
                    </div>

                    <!-- Vertical divider -->
                    <div class="hidden lg:block w-px bg-slate-200"></div>

                    <!-- Templates sidebar -->
                    <aside class="hidden lg:flex w-72 flex-col bg-slate-50 px-4 py-4 border-l border-slate-200">
                        <!-- Scroll area: header + templates list -->
                        <div class="flex-1 min-h-0 flex flex-col">
                            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Quick templates
                            </h2>
                            <p class="mb-3 text-[11px] text-slate-400">
                                Click a template to send it instantly in the current chat.
                            </p>

                            <!-- Existing templates list (scrollable) -->
                            <div class="space-y-3 pr-1 overflow-y-auto">
                                <button
                                    v-for="template in templates"
                                    :key="template.id"
                                    type="button"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-xs shadow-sm transition hover:border-slate-300 hover:shadow"
                                    :disabled="!activeThread"
                                    @click="useTemplate(template)"
                                >
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-semibold text-slate-900">
                                            {{ template.title }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded-full px-2 py-0.5 text-[10px] text-slate-400 hover:text-slate-700 hover:bg-slate-100"
                                                @click.stop="startEditTemplate(template)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-full px-2 py-0.5 text-[10px] text-red-400 hover:text-red-600 hover:bg-red-50"
                                                @click.stop="deleteTemplate(template.id)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        {{ template.description }}
                                    </p>
                                </button>
                            </div>
                        </div>

                        <!-- Add new template (fixed at bottom) -->
                        <div class="pt-4 space-y-3">
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 px-3 py-3 text-[11px] shadow-sm">
                                <p class="mb-2 font-semibold text-slate-900">
                                    Add new template
                                </p>
                                <div class="space-y-2">
                                    <input
                                        v-model="newTemplateTitle"
                                        type="text"
                                        placeholder="Template title"
                                        class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] focus:outline-none focus:ring-[1.5px] focus:ring-slate-400"
                                    />
                                    <input
                                        v-model="newTemplateDescription"
                                        type="text"
                                        placeholder="Short description (optional)"
                                        class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] focus:outline-none focus:ring-[1.5px] focus:ring-slate-400"
                                    />
                                    <textarea
                                        v-model="newTemplateText"
                                        rows="2"
                                        placeholder="Message text..."
                                        class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] focus:outline-none focus:ring-[1.5px] focus:ring-slate-400"
                                    />
                                    <button
                                        type="button"
                                        class="mt-1 inline-flex h-7 w-full items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white disabled:opacity-40"
                                        :disabled="!newTemplateTitle.trim() || !newTemplateText.trim()"
                                        @click="saveTemplate"
                                    >
                                        {{ editingTemplateId ? 'Update template' : 'Save template' }}
                                    </button>
                                </div>
                            </div>

                            <!-- End chat button -->
                            <button
                                type="button"
                                class="inline-flex h-8 w-full items-center justify-center rounded-full bg-red-500 text-[11px] font-semibold text-white shadow-sm hover:bg-red-600 disabled:opacity-40"
                                :disabled="!activeThread"
                                @click="endChat"
                            >
                                End Chat
                            </button>
                        </div>
                    </aside>
                </section>
            </div>
        </div>
    </AdminLayout>
    <div
        v-if="showEndDialog"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h2 class="text-sm font-semibold text-slate-900">
                End Chat
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Chat #{{ activeThread?.id }} · Duration: {{ formattedCapturedDuration }}
            </p>

            <form class="mt-4 space-y-4" @submit.prevent="confirmEndChat">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-slate-800">
                        Was the issue resolved?
                    </p>
                    <div class="flex items-center gap-4 text-xs">
                        <label class="flex items-center gap-1">
                            <input
                                v-model="endForm.resolved"
                                type="radio"
                                :value="true"
                            />
                            <span>Yes</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input
                                v-model="endForm.resolved"
                                type="radio"
                                :value="false"
                            />
                            <span>No</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-medium text-slate-800">
                            What was the issue about?
                        </label>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-0.5 text-[11px] font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-50"
                            :disabled="isGeneratingSummary"
                            @click="generateIssueSummary"
                        >
                            {{ isGeneratingSummary ? 'Generating…' : 'Generate' }}
                        </button>
                    </div>
                    <textarea
                        v-model="endForm.issue"
                        rows="3"
                        class="w-full rounded-md border border-slate-200 bg-white px-2 py-1 text-xs focus:outline-none focus:ring-[1.5px] focus:ring-slate-400"
                        placeholder="Short summary of the customer issue..."
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-full px-3 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100"
                        @click="cancelEndChat"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-full bg-red-500 px-4 py-1 text-xs font-semibold text-white hover:bg-red-600"
                        :disabled="endForm.processing"
                    >
                        Confirm End Chat
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>


