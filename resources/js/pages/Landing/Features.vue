<script setup lang="ts">
import {
    Users,
    Calendar,
    TrendingUp,
    FileText,
    Shield,
    Zap,
    Clock,
    DollarSign,
} from 'lucide-vue-next';
import { ref } from 'vue';

const features = [
    {
        icon: Users,
        title: 'Employee Management',
        description: 'Centralize employee data, documents, and profiles in one secure location.',
        gradient: 'from-blue-500/10 to-blue-600/10',
        iconColor: 'text-blue-600',
    },
    {
        icon: Calendar,
        title: 'Attendance Tracking',
        description: 'Monitor attendance, leaves, and work hours with real-time tracking and reports.',
        gradient: 'from-purple-500/10 to-purple-600/10',
        iconColor: 'text-purple-600',
    },
    {
        icon: DollarSign,
        title: 'Payroll Management',
        description: 'Automate payroll processing with tax calculations and direct deposit integration.',
        gradient: 'from-green-500/10 to-green-600/10',
        iconColor: 'text-green-600',
    },
    {
        icon: TrendingUp,
        title: 'Performance Reviews',
        description: 'Set goals, track progress, and conduct performance evaluations efficiently.',
        gradient: 'from-orange-500/10 to-orange-600/10',
        iconColor: 'text-orange-600',
    },
    {
        icon: FileText,
        title: 'Document Management',
        description: 'Store and manage important documents with version control and easy access.',
        gradient: 'from-pink-500/10 to-pink-600/10',
        iconColor: 'text-pink-600',
    },
    {
        icon: Shield,
        title: 'Security & Compliance',
        description: 'Enterprise-grade security with GDPR and SOC 2 compliance built-in.',
        gradient: 'from-red-500/10 to-red-600/10',
        iconColor: 'text-red-600',
    },
    {
        icon: Clock,
        title: 'Time & Scheduling',
        description: 'Create schedules, manage shifts, and track project time effortlessly.',
        gradient: 'from-indigo-500/10 to-indigo-600/10',
        iconColor: 'text-indigo-600',
    },
    {
        icon: Zap,
        title: 'Automated Workflows',
        description: 'Streamline HR processes with customizable automation and notifications.',
        gradient: 'from-yellow-500/10 to-yellow-600/10',
        iconColor: 'text-yellow-600',
    },
];

const hoveredIndex = ref<number | null>(null);
</script>

<template>
    <section id="features" class="py-32 lg:py-40 bg-white relative overflow-hidden" aria-labelledby="features-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl" />
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-20">
                <div class="inline-block px-4 py-2 rounded-full bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 mb-6">
                    <span class="text-sm font-medium text-gray-700">Features</span>
                </div>
                <h2 id="features-heading" class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-light tracking-tight text-gray-900 mb-6 leading-tight">
                    Everything you need
                    <br />
                    <span class="font-medium">in one place.</span>
                </h2>
                <p class="text-xl lg:text-2xl font-light text-gray-600 leading-relaxed">
                    Powerful features designed to simplify HR operations and enhance employee experience.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    @mouseenter="hoveredIndex = index"
                    @mouseleave="hoveredIndex = null"
                    :class="`group relative p-8 rounded-3xl border transition-all duration-500 ${
                        hoveredIndex === index
                            ? 'border-gray-300 bg-white shadow-2xl scale-105'
                            : 'border-gray-200 bg-white/50 backdrop-blur-sm hover:border-gray-300'
                    }`"
                >
                    <!-- Background Gradient on Hover -->
                    <div
                        :class="`absolute inset-0 rounded-3xl bg-gradient-to-br ${feature.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none`"
                    />

                    <!-- Content -->
                    <div class="relative z-10 space-y-4">
                        <!-- Icon -->
                        <div
                            :class="`inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 group-hover:bg-white transition-all duration-500 ${
                                hoveredIndex === index ? 'scale-110 rotate-6' : ''
                            }`"
                        >
                            <component
                                :is="feature.icon"
                                :class="`h-7 w-7 ${feature.iconColor} transition-transform duration-500 ${
                                    hoveredIndex === index ? 'scale-110' : ''
                                }`"
                            />
                        </div>

                        <!-- Title -->
                        <h3 class="text-xl font-medium text-gray-900 leading-tight">
                            {{ feature.title }}
                        </h3>

                        <!-- Description -->
                        <p class="text-gray-600 leading-relaxed text-sm lg:text-base">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
