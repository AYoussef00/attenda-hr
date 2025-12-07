<script setup lang="ts">
import { Building2, Twitter, Linkedin, Github, Facebook } from 'lucide-vue-next';
</script>

<template>
    <footer class="bg-black text-white relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-900 via-black to-gray-900" />
            <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-24 max-w-7xl relative z-10">
            <!-- Main Footer Content -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-2 gap-12 lg:gap-16 mb-16">
                <!-- Brand Column -->
                <div class="space-y-6">
                    <div>
                        <span class="text-3xl lg:text-4xl font-bold tracking-tight">
                            attenda<span class="text-gray-500">.</span>
                        </span>
                    </div>
                    <p class="text-gray-400 font-light leading-relaxed text-sm lg:text-base max-w-xs">
                        The modern HRM solution that helps businesses manage their workforce efficiently.
                    </p>
                    <!-- Social Links -->
                    <div class="flex gap-4">
                        <a
                            href="#"
                            class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110"
                            aria-label="Twitter"
                        >
                            <Twitter class="h-5 w-5 text-gray-300" />
                        </a>
                        <a
                            href="#"
                            class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110"
                            aria-label="LinkedIn"
                        >
                            <Linkedin class="h-5 w-5 text-gray-300" />
                        </a>
                        <a
                            href="#"
                            class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110"
                            aria-label="Facebook"
                        >
                            <Facebook class="h-5 w-5 text-gray-300" />
                        </a>
                        <a
                            href="#"
                            class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 hover:scale-110"
                            aria-label="GitHub"
                        >
                            <Github class="h-5 w-5 text-gray-300" />
                        </a>
                    </div>
                </div>

                <!-- Product Column -->
                <div>
                    <h3 class="text-white font-medium mb-6 text-sm uppercase tracking-wider">
                        Product
                    </h3>
                    <ul class="space-y-4 text-gray-400 font-light">
                        <li>
                            <a
                                href="#features"
                                class="hover:text-white transition-colors duration-200 text-sm"
                            >
                                Features
                            </a>
                        </li>
                        <li>
                            <a
                                href="#pricing"
                                class="hover:text-white transition-colors duration-200 text-sm"
                            >
                                Pricing
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-white/10 pt-8 flex flex-col sm:flex-row justify-between items-center gap-6">
                <p class="text-gray-400 font-light text-sm">
                    &copy; {{ new Date().getFullYear() }} attenda. All rights reserved.
                </p>
                <div class="flex flex-wrap gap-6 text-gray-400 font-light text-sm">
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        Privacy Policy
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        Terms of Service
                    </a>
                    <a
                        href="#"
                        class="hover:text-white transition-colors duration-200"
                    >
                        Cookie Policy
                    </a>
                </div>
            </div>
        </div>
    </footer>
</template>
