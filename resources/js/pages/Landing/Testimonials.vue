<script setup lang="ts">
import { Card } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Star, Quote } from 'lucide-vue-next';
import { ref } from 'vue';

const testimonials = [
    {
        name: 'Sarah Johnson',
        role: 'HR Director',
        company: 'TechCorp Inc.',
        content: 'Attenda HR has transformed how we manage our 500+ employee base. The automation features alone have saved us countless hours every week.',
        rating: 5,
        initials: 'SJ',
        gradient: 'from-blue-500/10 to-purple-500/10',
    },
    {
        name: 'Michael Chen',
        role: 'Operations Manager',
        company: 'StartupXYZ',
        content: 'The best investment we\'ve made for our HR operations. The interface is intuitive, and the support team is incredibly responsive.',
        rating: 5,
        initials: 'MC',
        gradient: 'from-green-500/10 to-blue-500/10',
    },
    {
        name: 'Emily Rodriguez',
        role: 'CEO',
        company: 'GrowthLabs',
        content: 'We switched from our legacy system to Attenda HR and never looked back. Our team loves the mobile app and self-service features.',
        rating: 5,
        initials: 'ER',
        gradient: 'from-orange-500/10 to-pink-500/10',
    },
];

const hoveredTestimonial = ref<number | null>(null);
</script>

<template>
    <section id="testimonials" class="py-32 lg:py-40 bg-white relative overflow-hidden" aria-labelledby="testimonials-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-gray-500/5 rounded-full blur-3xl" />
            <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-gray-500/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <!-- Section Header -->
            <div
                class="mb-20 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-10"
            >
                <!-- Left: Heading -->
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center rounded-full bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 px-4 py-1.5 mb-5"
                    >
                        <span class="text-xs font-medium tracking-[0.18em] uppercase text-gray-600">
                            Let our customers do the talking
                        </span>
                    </div>
                    <h2
                        id="testimonials-heading"
                        class="text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-gray-900 leading-tight"
                    >
                        <span class="block">Finally, an HR platform</span>
                        <span class="block">
                            that
                            <span
                                class="bg-gradient-to-r from-purple-500 via-pink-500 to-orange-400 bg-clip-text text-transparent"
                            >
                                works your way.
                            </span>
                        </span>
                    </h2>
                    <p class="mt-5 text-base sm:text-lg lg:text-xl text-gray-600 leading-relaxed">
                        Bring attendance, payroll, performance, and employee self‑service together in a single,
                        easy‑to‑use HR platform your team will actually love.
                    </p>
                </div>

                <!-- Right: Ratings -->
                <div class="flex flex-wrap gap-4 lg:justify-end">
                    <div
                        class="flex items-center gap-3 rounded-full border border-gray-200 bg-white px-4 py-2 shadow-sm"
                    >
                        <span class="text-xl font-semibold text-orange-500">4.7</span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-xs text-gray-500">Rating</span>
                            <span class="text-xs font-medium text-gray-800">on G2</span>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-3 rounded-full border border-gray-200 bg-white px-4 py-2 shadow-sm"
                    >
                        <span class="text-xl font-semibold text-indigo-500">4.6</span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-xs text-gray-500">Rating</span>
                            <span class="text-xs font-medium text-gray-800">on Capterra</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonials Grid -->
            <div class="grid md:grid-cols-3 gap-8 lg:gap-10 max-w-6xl mx-auto">
                <Card
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    @mouseenter="hoveredTestimonial = index"
                    @mouseleave="hoveredTestimonial = null"
                    :class="`relative p-8 lg:p-10 rounded-3xl border transition-all duration-500 overflow-hidden ${
                        hoveredTestimonial === index
                            ? 'border-gray-300 bg-white shadow-2xl scale-105'
                            : 'border-gray-200 bg-white/50 backdrop-blur-sm hover:border-gray-300'
                    }`"
                >
                    <!-- Background Gradient on Hover -->
                    <div
                        :class="`absolute inset-0 bg-gradient-to-br ${testimonial.gradient} opacity-0 transition-opacity duration-500 ${
                            hoveredTestimonial === index ? 'opacity-100' : ''
                        } pointer-events-none`"
                    />

                    <div class="relative z-10 space-y-6">
                        <!-- Quote Icon -->
                        <div class="flex items-start">
                            <Quote
                                :class="`h-8 w-8 text-gray-300 transition-transform duration-500 ${
                                    hoveredTestimonial === index ? 'scale-110 rotate-6' : ''
                                }`"
                            />
                        </div>

                        <!-- Stars -->
                        <div class="flex gap-1">
                            <Star
                                v-for="i in testimonial.rating"
                                :key="i"
                                :class="`h-5 w-5 transition-all duration-300 ${
                                    hoveredTestimonial === index
                                        ? 'fill-yellow-400 text-yellow-400 scale-110'
                                        : 'fill-yellow-400/50 text-yellow-400/50'
                                }`"
                            />
                        </div>

                        <!-- Content -->
                        <p
                            :class="`text-gray-700 leading-relaxed text-base lg:text-lg transition-colors duration-300 ${
                                hoveredTestimonial === index ? 'text-gray-900' : ''
                            }`"
                        >
                            "{{ testimonial.content }}"
                        </p>

                        <!-- Author -->
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200/50">
                            <Avatar
                                :class="`transition-transform duration-500 ${
                                    hoveredTestimonial === index ? 'scale-110' : ''
                                }`"
                            >
                                <AvatarFallback
                                    :class="`bg-gray-900 text-white text-sm font-medium ${
                                        hoveredTestimonial === index ? 'bg-black' : ''
                                    }`"
                                >
                                    {{ testimonial.initials }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <div
                                    :class="`font-medium text-sm ${
                                        hoveredTestimonial === index ? 'text-gray-900' : 'text-gray-700'
                                    }`"
                                >
                                    {{ testimonial.name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ testimonial.role }} at {{ testimonial.company }}
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </section>
</template>
