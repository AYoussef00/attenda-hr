<script setup lang="ts">
import { Twitter, Linkedin, Facebook, Instagram, Youtube } from 'lucide-vue-next';

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
            <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10 text-center">
            <!-- Brand Logo -->
            <div class="mb-6">
                <span class="text-3xl lg:text-4xl font-bold tracking-tight">
                    attenda<span class="text-gray-500">.</span>
                </span>
            </div>

            <!-- Social Links -->
            <div class="flex justify-center gap-4 mb-6">
                <a
                    v-for="link in socialLinks"
                    :key="link.name"
                    :href="link.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="link.name"
                    :class="`p-2.5 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110 ${link.color}`"
                >
                    <component :is="link.icon" class="h-5 w-5 text-gray-300" />
                </a>
            </div>

            <!-- Copyright -->
            <p class="text-gray-400 font-light text-sm">
                &copy; {{ currentYear }} attenda. All rights reserved.
            </p>
        </div>
    </footer>
</template>
