<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ArrowRight, CheckCircle2, Play } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    onGetStartedClick?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
    onGetStartedClick: () => {
        router.visit('/company/register');
    },
});

const imageError = ref(false);
const imageSrc = 'https://images.unsplash.com/photo-1630283017802-785b7aff9aac?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtb2Rlcm4lMjBvZmZpY2UlMjB3b3Jrc3BhY2V8ZW58MXx8fHwxNzYzMTcxNzMzfDA&ixlib=rb-4.1.0&q=80&w=1080';
const fallbackImageSrc = 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1080&q=80';

const handleImageError = () => {
    imageError.value = true;
};

const handleScheduleDemo = () => {
    router.visit('/company/register');
};
</script>

<template>
    <section id="cta" class="py-32 lg:py-40 bg-black text-white relative overflow-hidden" aria-labelledby="cta-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-900 via-black to-gray-900" />
            <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
            <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-20 items-center">
                <!-- Left Content -->
                <div class="space-y-10">
                    <div class="inline-block px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-6">
                        <span class="text-sm font-medium text-white/90">Get Started</span>
                    </div>

                    <h2 id="cta-heading" class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-light tracking-tight leading-tight">
                        Ready to transform
                        <br />
                        <span class="font-medium">your HR management?</span>
                    </h2>

                    <p class="text-xl lg:text-2xl font-light text-gray-300 leading-relaxed max-w-2xl">
                        Join thousands of companies that trust attenda to manage their workforce. Start your free 14-day trial today.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <Button
                            size="lg"
                            @click="onGetStartedClick"
                            class="bg-white text-black hover:bg-gray-100 text-base font-medium px-8 h-14 rounded-full transition-all duration-300 hover:scale-105 hover:shadow-2xl"
                        >
                            Start Free Trial
                            <ArrowRight class="ml-2 h-5 w-5" />
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            @click="handleScheduleDemo"
                            class="border-2 border-white/30 text-white hover:bg-white/10 backdrop-blur-sm text-base font-medium px-8 h-14 rounded-full transition-all duration-300 hover:scale-105"
                        >
                            <Play class="mr-2 h-5 w-5" />
                            Schedule Demo
                        </Button>
                    </div>

                    <!-- Features List -->
                    <div class="flex flex-wrap gap-6 pt-6">
                        <div class="flex items-center gap-2 text-gray-300">
                            <CheckCircle2 class="h-5 w-5 text-white" />
                            <span class="text-sm font-light">No credit card required</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-300">
                            <CheckCircle2 class="h-5 w-5 text-white" />
                            <span class="text-sm font-light">14-day free trial</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-300">
                            <CheckCircle2 class="h-5 w-5 text-white" />
                            <span class="text-sm font-light">Cancel anytime</span>
                        </div>
                    </div>
                </div>

                <!-- Right Image -->
                <div class="relative">
                    <!-- Glow Effect -->
                    <div class="absolute inset-0 bg-white/10 rounded-3xl blur-3xl transform scale-110" />
                    
                    <!-- Image Container -->
                    <div class="relative rounded-3xl overflow-hidden">
                        <div class="aspect-[4/3] overflow-hidden rounded-3xl">
                            <img
                                :src="imageError ? fallbackImageSrc : imageSrc"
                                alt="Modern office workspace"
                                class="w-full h-full object-cover transition-transform duration-700 hover:scale-105"
                                @error="handleImageError"
                                loading="lazy"
                            />
                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
