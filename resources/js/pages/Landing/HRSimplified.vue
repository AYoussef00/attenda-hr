<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next';
</script>

<template>
    <section id="hr-simplified" class="py-32 lg:py-40 bg-white relative overflow-hidden" aria-labelledby="hr-simplified-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 right-1/4 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl" />
            <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Side: Text Content -->
                <div class="space-y-8">
                    <h2 id="hr-simplified-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                        HR MANAGEMENT SIMPLIFIED
                    </h2>
                    <p class="text-xl lg:text-2xl font-light text-gray-600 leading-relaxed">
                        Adapt to new realities, work faster and smarter. Determine a defined future of work for your organization with a strong, flexible, global HR solution.
                    </p>

                    <!-- Features List -->
                    <div class="space-y-4 pt-4">
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Easy access to documents and information.</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">An employee database that scales.</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Smart HR workflows.</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Automation of HR processes.</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Image -->
                <div class="relative">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl">
                        <img
                            src="/asset/HM-3-1.webp"
                            alt="HR Management Simplified"
                            class="w-full h-auto object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
