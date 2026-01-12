<script setup lang="ts">
import CompanyLayout from '@/layouts/company/CompanyLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { MessageCircle, Send } from 'lucide-vue-next';
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat',
        href: '/company/chat',
    },
];

const inputValue = ref('');
const visitorId = ref<string>('');
let pollId: number | null = null;

type ChatMessage = {
    id: number;
    from: 'bot' | 'user';
    text: string;
};

const introMessages: ChatMessage[] = [
    {
        id: 1,
        from: 'bot',
        text: "Hello! I'm here to help you with any questions about Attenda.",
    },
    {
        id: 2,
        from: 'bot',
        text: 'How can I assist you today?',
    },
];

const messages = ref<ChatMessage[]>([...introMessages]);

const chatBodyRef = ref<HTMLElement | null>(null);

const loadHistory = async () => {
    try {
        const response = await fetch(
            `/chat/messages?visitor_id=${encodeURIComponent(visitorId.value)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (!response.ok) return;

        const data = await response.json();
        const history = Array.isArray(data.messages) ? data.messages : [];

        const mapped: ChatMessage[] = history.map((m: any) => ({
            id: m.id ?? Date.now(),
            from: m.from === 'admin' ? 'bot' : 'user',
            text: String(m.text ?? ''),
        }));

        messages.value = [...introMessages, ...mapped];
        scrollToBottom();
    } catch (e) {
        // ignore errors
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatBodyRef.value) {
            chatBodyRef.value.scrollTop = chatBodyRef.value.scrollHeight;
        }
    });
};

const sendMessage = () => {
    const text = inputValue.value.trim();
    if (!text) return;

    try {
        const csrfToken =
            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)
                ?.content || '';

        fetch('/chat/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                visitor_id: visitorId.value,
                message: text,
            }),
        })
            .then(() => loadHistory())
            .catch(() => {
                messages.value.push({
                    id: Date.now(),
                    from: 'user',
                    text,
                });
                scrollToBottom();
            });
    } catch (e) {
        messages.value.push({
            id: Date.now(),
            from: 'user',
            text,
        });
        scrollToBottom();
    }

    inputValue.value = '';
};

onMounted(() => {
    const stored = window.localStorage.getItem('attenda_chat_visitor_id');
    if (stored) {
        visitorId.value = stored;
    } else {
        const id = crypto.randomUUID ? crypto.randomUUID() : `visitor_${Date.now()}`;
        visitorId.value = id;
        window.localStorage.setItem('attenda_chat_visitor_id', id);
    }

    loadHistory();

    if (pollId === null) {
        pollId = window.setInterval(() => {
            loadHistory();
        }, 2000);
    }
});

onBeforeUnmount(() => {
    if (pollId !== null) {
        window.clearInterval(pollId);
        pollId = null;
    }
});
</script>

<template>
    <Head title="Attenda - Chat Support | Get Help & Support">
        <meta name="description" content="دردشة الدعم في Attenda. تواصل مع فريق الدعم للحصول على المساعدة في نظام إدارة الموارد البشرية." />
    </Head>

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-hidden rounded-2xl bg-gradient-to-b from-slate-50 to-white">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white">
                        <MessageCircle class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">Chat Support</h1>
                        <p class="text-sm text-slate-500">Get help from our support team</p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div
                ref="chatBodyRef"
                class="flex flex-1 flex-col gap-4 overflow-y-auto bg-slate-50 px-6 py-6"
            >
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex items-start gap-3"
                    :class="message.from === 'user' ? 'justify-end' : ''"
                >
                    <!-- Bot avatar -->
                    <div
                        v-if="message.from === 'bot'"
                        class="mt-1 h-8 w-8 flex-shrink-0 rounded-full bg-slate-900 text-xs font-semibold text-white flex items-center justify-center"
                    >
                        A
                    </div>
                    <!-- Bot bubble -->
                    <div
                        v-if="message.from === 'bot'"
                        class="rounded-2xl rounded-tl-none bg-white px-4 py-3 text-slate-900 shadow-sm max-w-[75%]"
                    >
                        {{ message.text }}
                    </div>
                    <!-- User bubble -->
                    <div
                        v-else
                        class="rounded-2xl rounded-tr-none bg-slate-900 px-4 py-3 text-white shadow-sm max-w-[75%]"
                    >
                        {{ message.text }}
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-slate-200 bg-white px-6 py-4">
                <div class="flex items-center gap-3 rounded-full border border-slate-300 bg-slate-50 px-4 py-3">
                    <input
                        type="text"
                        placeholder="Type your message..."
                        v-model="inputValue"
                        @keyup.enter.prevent="sendMessage"
                        class="h-10 flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
                    />
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-white transition-opacity hover:bg-slate-800"
                        :class="inputValue.trim() ? 'opacity-100' : 'opacity-40 cursor-default'"
                        :disabled="!inputValue.trim()"
                        @click="sendMessage"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>
