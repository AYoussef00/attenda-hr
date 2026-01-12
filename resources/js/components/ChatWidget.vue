<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { MessageCircle, X, Send } from 'lucide-vue-next';

const isOpen = ref(false);
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

const toggleChat = () => {
    isOpen.value = !isOpen.value;
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

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
    window.addEventListener('keydown', handleKeydown);
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
});

watch(isOpen, (open) => {
        if (open) {
        loadHistory();
        if (pollId === null) {
            pollId = window.setInterval(() => {
                loadHistory();
            }, 2000);
        }
    } else if (pollId !== null) {
        window.clearInterval(pollId);
        pollId = null;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (pollId !== null) {
        window.clearInterval(pollId);
        pollId = null;
    }
});
</script>

<template>
    <!-- Floating Chat Button -->
    <button
        type="button"
        class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-black text-white shadow-2xl hover:bg-black/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-black"
        @click="toggleChat"
        aria-label="Open chat"
    >
        <MessageCircle class="h-7 w-7" />
    </button>

    <!-- Chat Panel -->
    <transition name="fade">
        <div
            v-if="isOpen"
            class="fixed bottom-4 right-4 z-40 flex h-[80vh] max-h-[720px] w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-white shadow-[0_18px_45px_rgba(15,23,42,0.6)]"
        >
            <!-- Header -->
            <div class="flex items-center justify-between bg-slate-900 px-4 py-4 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-900">
                        attenda.
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight">attenda Support</p>
                        <p class="text-xs text-slate-300">Typically replies in a few minutes</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-300 hover:bg-slate-800 hover:text-white"
                    @click="isOpen = false"
                    aria-label="Close chat"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Messages -->
            <div
                ref="chatBodyRef"
                class="flex flex-1 flex-col gap-3 overflow-y-auto bg-slate-50 px-4 py-5 text-sm"
            >
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex items-start gap-2"
                    :class="message.from === 'user' ? 'justify-end' : ''"
                >
                    <!-- Bot avatar -->
                    <div
                        v-if="message.from === 'bot'"
                        class="mt-1 h-6 w-6 flex-shrink-0 rounded-full bg-slate-900 text-[11px] font-semibold text-white flex items-center justify-center"
                    >
                        a
                    </div>
                    <!-- Bot bubble -->
                    <div
                        v-if="message.from === 'bot'"
                        class="rounded-2xl rounded-tl-none bg-white px-4 py-3 text-slate-900 shadow-sm max-w-[85%]"
                    >
                        {{ message.text }}
                    </div>
                    <!-- User bubble -->
                    <div
                        v-else
                        class="rounded-2xl rounded-tr-none bg-slate-900 px-4 py-3 text-white shadow-sm max-w-[85%]"
                    >
                        {{ message.text }}
                    </div>
                </div>

            </div>

            <!-- Input -->
            <div class="border-t border-slate-200 bg-white px-4 py-3">
                <div class="flex items-center gap-2 rounded-full border border-slate-300 bg-slate-50 px-4 py-2">
                    <input
                        type="text"
                        placeholder="Ask me anything..."
                        v-model="inputValue"
                        @keyup.enter.prevent="sendMessage"
                        class="h-9 flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
                    />
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-white transition-opacity"
                        :class="inputValue.trim() ? 'opacity-100 hover:bg-slate-800' : 'opacity-40 cursor-default'"
                        :disabled="!inputValue.trim()"
                        @click="sendMessage"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>

