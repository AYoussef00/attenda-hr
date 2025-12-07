<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-svh bg-[#1e3b3b] px-4 py-8 flex items-center justify-center">
        <!-- Top-left brand logo -->
                <div class="pointer-events-none absolute left-6 top-4 z-40 sm:left-10 sm:top-6">
            <Link
                :href="home()"
                class="pointer-events-auto inline-flex items-center gap-3 px-0 py-0 transition hover:opacity-90"
            >
                <img
                    src="/asset/white-logo.png"
                            alt="attenda."
                    class="h-20 w-auto sm:h-28"
                />
            </Link>
        </div>

        <div
            class="w-full max-w-5xl grid gap-10 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] rounded-3xl bg-white/5 shadow-2xl border border-white/10 backdrop-blur-xl overflow-hidden"
        >
            <!-- Left side: Brand / Marketing -->
            <div class="relative hidden md:flex flex-col justify-between px-10 py-10 text-white">
                <div class="space-y-6">
                    <div class="flex items-center">
                        <img
                            src="/asset/white-logo.png"
                            alt="attenda. - Smart Workforce Management"
                            class="w-64 max-w-full drop-shadow-[0_12px_35px_rgba(0,0,0,0.35)]"
                        />
                    </div>

                    <div class="space-y-4">
                        <h1 class="text-3xl lg:text-4xl font-semibold leading-tight">
                            A modern, elegant way to manage your team.
                        </h1>
                        <p class="text-sm leading-relaxed text-blue-100/80 max-w-md">
                            Track attendance, manage leaves, and keep your HR operations running
                            smoothly with a single, powerful dashboard.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2 text-sm">
                        <div class="rounded-2xl bg-white/10 border border-white/20 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-blue-100/80 mb-1">
                                Attendance & Leaves
                            </p>
                            <p class="font-medium">Built for growing teams</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 border border-white/10 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-wide text-blue-100/80 mb-1">
                                Secure Access
                            </p>
                            <p class="font-medium">Role-based login for admins & employees</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-blue-100/70 pt-4">
                    <p>Trusted by modern HR teams</p>
                    <p class="hidden lg:block">Optimized for desktop & mobile</p>
                </div>

                <div
                    class="pointer-events-none absolute inset-0 -z-10 opacity-70"
                    aria-hidden="true"
                >
                    <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-blue-400/40 blur-3xl" />
                    <div class="absolute -left-10 bottom-0 h-56 w-56 rounded-full bg-indigo-500/40 blur-3xl" />
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.08),_transparent_55%)]" />
                </div>
            </div>

            <!-- Right side: Auth card -->
            <div class="relative bg-white/95 dark:bg-slate-950/95 px-6 py-8 sm:px-8 sm:py-10 flex items-center">
                <div class="w-full max-w-sm mx-auto">
                    <div class="mb-8 flex flex-col gap-6">
                        <div class="flex items-center justify-between">
                            <Link
                                :href="home()"
                                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:bg-slate-900"
                            >
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-[11px] font-semibold text-white shadow-sm">
                                    AH
                                </span>
                                <span>Back to attenda.</span>
                            </Link>
                        </div>

                        <div class="space-y-2">
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
                                {{ title }}
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
