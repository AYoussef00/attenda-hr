<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Home, Sparkles, DollarSign, Info, ArrowRight } from 'lucide-vue-next';

const helpfulLinks = [
    {
        title: 'Home',
        href: '/',
        description: 'Return to the homepage',
        icon: Home,
    },
    {
        title: 'Features',
        href: '/#features',
        description: 'Discover our features',
        icon: Sparkles,
    },
    {
        title: 'Pricing',
        href: '/#pricing',
        description: 'View our pricing plans',
        icon: DollarSign,
    },
    {
        title: 'About',
        href: '/#about',
        description: 'Learn more about us',
        icon: Info,
    },
];
</script>

<template>
    <Head title="404 - Page Not Found" />
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full text-center">
            <!-- 404 Number -->
            <div class="mb-8">
                <h1 class="text-9xl sm:text-[12rem] font-bold text-gray-200 dark:text-gray-800 leading-none">
                    404
                </h1>
            </div>

            <!-- Error Message -->
            <div class="mb-12">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-light text-gray-900 dark:text-gray-100 mb-4">
                    Page Not Found
                </h2>
                <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Oops! The page you're looking for doesn't exist or has been moved. 
                    Let's get you back on track.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <Button
                    as-child
                    class="bg-black hover:bg-gray-800 text-white px-8 py-6 text-base font-medium rounded-full transition-all duration-200 hover:scale-105"
                >
                    <Link href="/">
                        <Home class="h-5 w-5 inline-block mr-2" />
                        Go to Homepage
                    </Link>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    class="border-2 border-gray-300 hover:border-gray-400 px-8 py-6 text-base font-medium rounded-full transition-all duration-200"
                >
                    <Link href="/company/register">
                        Get Started
                        <ArrowRight class="h-5 w-5 inline-block ml-2" />
                    </Link>
                </Button>
            </div>

            <!-- Helpful Links -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl p-8 sm:p-12 border border-gray-200 dark:border-gray-800">
                <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">
                    Popular Pages
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link
                        v-for="link in helpfulLinks"
                        :key="link.href"
                        :href="link.href"
                        class="group p-6 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-gray-600 transition-all duration-200 hover:shadow-lg text-left"
                    >
                        <div class="flex items-start gap-4">
                            <div class="p-3 rounded-xl bg-gray-100 dark:bg-gray-800 group-hover:bg-black dark:group-hover:bg-gray-700 transition-colors">
                                <component
                                    :is="link.icon"
                                    class="h-6 w-6 text-gray-700 dark:text-gray-300 group-hover:text-white transition-colors"
                                />
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-black dark:group-hover:text-white transition-colors">
                                    {{ link.title }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ link.description }}
                                </p>
                            </div>
                            <ArrowRight class="h-5 w-5 text-gray-400 group-hover:text-black dark:group-hover:text-white transition-colors flex-shrink-0 mt-1" />
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Search Suggestion -->
            <div class="mt-12 p-6 bg-gray-100 dark:bg-gray-800 rounded-2xl">
                <p class="text-gray-700 dark:text-gray-300">
                    <strong>Tip:</strong> You can use the navigation menu at the top to explore our features, 
                    pricing, and learn more about Attenda HR Management System.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth animations */
.group {
    transition: all 0.3s ease;
}
</style>
