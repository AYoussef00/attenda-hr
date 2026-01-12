<script setup lang="ts">
import { Building2, Users, Award, Globe } from 'lucide-vue-next';
import { ref } from 'vue';

const stats = [
    {
        icon: Users,
        value: '1000+',
        label: 'Companies',
        gradient: 'from-blue-500/10 to-blue-600/10',
        iconColor: 'text-blue-600',
    },
    {
        icon: Building2,
        value: '50K+',
        label: 'Employees',
        gradient: 'from-green-500/10 to-green-600/10',
        iconColor: 'text-green-600',
    },
    {
        icon: Award,
        value: '99.9%',
        label: 'Uptime',
        gradient: 'from-purple-500/10 to-purple-600/10',
        iconColor: 'text-purple-600',
    },
    {
        icon: Globe,
        value: '50+',
        label: 'Countries',
        gradient: 'from-orange-500/10 to-orange-600/10',
        iconColor: 'text-orange-600',
    },
];

const hoveredStat = ref<number | null>(null);
</script>

<template>
    <section id="about" class="py-32 lg:py-40 bg-white relative overflow-hidden" aria-labelledby="about-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 right-0 w-96 h-96 bg-gray-500/5 rounded-full blur-3xl" />
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-gray-500/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-20">
                <div class="inline-block px-4 py-2 rounded-full bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 mb-6">
                    <span class="text-sm font-medium text-gray-700">About</span>
                </div>
                <h2 id="about-heading" class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-light tracking-tight text-gray-900 mb-6 leading-tight">
                    About attenda
                </h2>
                <p class="text-xl lg:text-2xl font-light text-gray-600 leading-relaxed">
                    We're on a mission to simplify HR management for companies of all sizes.
                </p>
            </div>

            <!-- Content Grid -->
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-20 items-start mb-20">
                <!-- Story Content -->
                <div class="space-y-8">
                    <h3 class="text-3xl lg:text-4xl font-light tracking-tight text-gray-900 leading-tight">
                        Our Story
                    </h3>
                    <div class="space-y-6 text-lg lg:text-xl font-light text-gray-600 leading-relaxed">
                        <p>
                            attenda was born out of a simple idea: HR management shouldn't be complicated.
                            We've built a platform that helps companies manage their most valuable asset —
                            their people — with ease and efficiency.
                        </p>
                        <p>
                            Whether you're a small startup or a large enterprise, attenda adapts to your
                            needs and grows with your company.
                        </p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-6 lg:gap-8">
                    <div
                        v-for="(stat, index) in stats"
                        :key="index"
                        @mouseenter="hoveredStat = index"
                        @mouseleave="hoveredStat = null"
                        :class="`relative p-8 rounded-3xl border transition-all duration-500 overflow-hidden ${
                            hoveredStat === index
                                ? 'border-gray-300 bg-white shadow-2xl scale-105'
                                : 'border-gray-200 bg-white/50 backdrop-blur-sm hover:border-gray-300'
                        }`"
                    >
                        <!-- Background Gradient on Hover -->
                        <div
                            :class="`absolute inset-0 bg-gradient-to-br ${stat.gradient} opacity-0 transition-opacity duration-500 ${
                                hoveredStat === index ? 'opacity-100' : ''
                            } pointer-events-none`"
                        />

                        <div class="relative z-10 text-center space-y-4">
                            <!-- Icon -->
                            <div
                                :class="`inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 transition-all duration-500 ${
                                    hoveredStat === index ? 'scale-110 rotate-6' : ''
                                }`"
                            >
                                <component
                                    :is="stat.icon"
                                    :class="`h-8 w-8 ${stat.iconColor} transition-transform duration-500 ${
                                        hoveredStat === index ? 'scale-110' : ''
                                    }`"
                                />
                            </div>

                            <!-- Value -->
                            <div class="text-4xl lg:text-5xl font-light text-gray-900">
                                {{ stat.value }}
                            </div>

                            <!-- Label -->
                            <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">
                                {{ stat.label }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mission Section -->
            <div class="text-center max-w-4xl mx-auto space-y-8">
                <h3 class="text-3xl lg:text-4xl font-light tracking-tight text-gray-900 leading-tight">
                    Our Mission
                </h3>
                <p class="text-lg lg:text-xl font-light text-gray-600 leading-relaxed">
                    To empower businesses worldwide with tools that make HR management simple,
                    efficient, and enjoyable. We believe that when HR processes are streamlined,
                    companies can focus on what matters most: building great products and serving
                    their customers.
                </p>
            </div>
        </div>
    </section>
</template>
