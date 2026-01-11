<script setup lang="ts">
import CompanyLayout from '@/layouts/company/CompanyLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { MessageCircle, Send, X } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/company/dashboard',
    },
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
        text: "Got any questions? I'm happy to help.",
    },
    {
        id: 2,
        from: 'bot',
        text: 'What would you like to do?',
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
        // ignore errors for now
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

    // Send to backend so it appears in admin chat
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
                // Silently ignore; UI will at least have local message
                messages.value.push({
                    id: Date.now(),
                    from: 'user',
                    text,
                });
                scrollToBottom();
            });
    } catch (e) {
        // If something fails before fetch, still show message locally
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
    // Get or create a persistent visitor ID for grouping messages
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
    <Head title="Chat with Us" />

    <CompanyLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col">
            <!-- Chat Container - Full Page -->
            <div class="flex h-[calc(100vh-8rem)] flex-col overflow-hidden rounded-xl bg-white shadow-lg">
                <!-- Header -->
                <div class="flex items-center justify-between bg-[#1e3b3b] px-6 py-4 text-emerald-50">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center rounded-full bg-white px-3 py-1 text-sm font-semibold text-[#1e3b3b]">
                            attenda.
                        </div>
                        <div>
                            <p class="text-base font-semibold leading-tight">attenda Support</p>
                            <p class="text-sm text-emerald-100/70">Typically replies in a few minutes</p>
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
                            class="mt-1 h-8 w-8 flex-shrink-0 rounded-full bg-slate-900 text-sm font-semibold text-white flex items-center justify-center"
                        >
                            a
                        </div>
                        <!-- Bot bubble -->
                        <div
                            v-if="message.from === 'bot'"
                            class="rounded-2xl rounded-tl-none bg-white px-5 py-3 text-slate-900 shadow-sm max-w-[70%]"
                        >
                            {{ message.text }}
                        </div>
                        <!-- User bubble -->
                        <div
                            v-else
                            class="rounded-2xl rounded-tr-none bg-slate-900 px-5 py-3 text-white shadow-sm max-w-[70%]"
                        >
                            {{ message.text }}
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="border-t border-slate-200 bg-white px-6 py-4">
                    <div class="flex items-center gap-3 rounded-full border border-slate-300 bg-slate-50 px-5 py-3">
                        <input
                            type="text"
                            placeholder="Ask me anything..."
                            v-model="inputValue"
                            @keyup.enter.prevent="sendMessage"
                            class="h-10 flex-1 bg-transparent text-base text-slate-900 placeholder:text-slate-400 focus:outline-none"
                        />
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white transition-opacity hover:bg-slate-800"
                            :class="inputValue.trim() ? 'opacity-100' : 'opacity-40 cursor-default'"
                            :disabled="!inputValue.trim()"
                            @click="sendMessage"
                        >
                            <Send class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>

