<script setup lang="ts">
import { Facebook, Twitter, Linkedin, Instagram, Youtube } from 'lucide-vue-next';

const currentYear = new Date().getFullYear();

const socialLinks = [
    { name: 'Facebook', icon: Facebook, href: '#', color: 'hover:text-blue-500' },
    { name: 'Twitter', icon: Twitter, href: '#', color: 'hover:text-sky-400' },
    { name: 'LinkedIn', icon: Linkedin, href: '#', color: 'hover:text-blue-600' },
    { name: 'Instagram', icon: Instagram, href: '#', color: 'hover:text-pink-500' },
    { name: 'YouTube', icon: Youtube, href: '#', color: 'hover:text-red-500' },
];
</script>

<template>
    <footer class="bg-black text-white relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-900 via-black to-gray-900" />
            <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16 max-w-7xl relative z-10">
            <div class="flex flex-col items-center justify-center gap-8">
                <!-- Logo -->
                <div>
                    <span class="text-3xl lg:text-4xl font-bold tracking-tight">
                        attenda<span class="text-gray-500">.</span>
                    </span>
                </div>

                <!-- Social Media Links -->
                <div class="flex gap-4">
                    <a
                        v-for="social in socialLinks"
                        :key="social.name"
                        :href="social.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="`p-3 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110 ${social.color}`"
                        :aria-label="social.name"
                    >
                        <component :is="social.icon" class="h-5 w-5" />
                    </a>
                </div>

                <!-- Copyright -->
                <p class="text-gray-400 font-light text-sm text-center">
                    &copy; {{ currentYear }} attenda. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>

<style scoped>
/* Smooth hover transitions */
a {
    transition: all 0.2s ease;
}
</style>
