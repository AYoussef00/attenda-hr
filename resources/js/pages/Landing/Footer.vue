<script setup lang="ts">
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
                            href="https://wa.me/1234567890"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="p-2 rounded-full bg-white/10 hover:bg-green-500/20 transition-all duration-300 hover:scale-110"
                            aria-label="WhatsApp"
                        >
                            <svg
                                class="h-5 w-5 text-gray-300 hover:text-green-400"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                                />
                            </svg>
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
