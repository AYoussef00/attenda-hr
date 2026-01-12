<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type DocSection = {
    id: string;
    title: string;
    description?: string;
};

const sections: DocSection[] = [
    {
        id: 'introduction',
        title: 'Introduction',
        description: 'High level overview of the system.',
    },
    {
        id: 'employees',
        title: 'Employees',
        description: 'How employees are created and managed.',
    },
    {
        id: 'attendance',
        title: 'Attendance',
        description: 'Attendance, check-in / check-out, and rules.',
    },
    {
        id: 'payroll',
        title: 'Payroll',
        description: 'Salary, deductions and payslips.',
    },
];

const scrollToSection = (id: string) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Documentation" />

    <div class="min-h-screen bg-white text-slate-900 flex">
        <!-- Sidebar -->
        <aside class="w-64 border-r border-slate-200 bg-slate-50/60 px-4 py-6">
            <h2 class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase mb-3">
                Contents
                                </h2>
            <nav class="space-y-1 text-sm">
                                <button
                    v-for="section in sections"
                                    :key="section.id"
                                    type="button"
                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-white hover:text-emerald-700 border border-transparent hover:border-emerald-100 transition"
                                    @click="scrollToSection(section.id)"
                                >
                    <div class="font-medium text-xs sm:text-sm">
                                        {{ section.title }}
                    </div>
                    <p v-if="section.description" class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                        {{ section.description }}
                    </p>
                                </button>
                            </nav>
                        </aside>

        <!-- Content -->
        <main class="flex-1 px-6 sm:px-10 lg:px-16 py-8 space-y-16 overflow-y-auto">
            <section
                v-for="section in sections"
                :id="section.id"
                :key="section.id"
                class="scroll-mt-24"
            >
                <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 mb-2">
                    {{ section.title }}
                                </h1>
                <p v-if="section.description" class="text-sm text-slate-500 mb-4">
                    {{ section.description }}
                </p>

                <!-- Placeholder content: you can replace this with real docs later -->
                <p class="text-sm text-slate-600">
                    This section is intentionally left minimal. You can replace this text with your real
                    documentation content for
                    <span class="font-medium">{{ section.title }}</span>.
                </p>
                </section>
        </main>
    </div>
</template>


