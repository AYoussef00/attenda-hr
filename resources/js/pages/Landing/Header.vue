<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Menu, X, Globe } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

interface Props {
    onLoginClick?: () => void;
    onRegisterClick?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
    onLoginClick: () => {
        router.visit('/company/login');
    },
    onRegisterClick: () => {
        router.visit('/company/register');
    },
});

const page = usePage();
const isAuthenticated = computed(() => {
    return !!page.props.auth?.user;
});

const goToDashboard = () => {
    router.visit('/company/dashboard');
};

const mobileMenuOpen = ref(false);
const isScrolled = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const smoothScroll = (e: Event, targetId: string) => {
    e.preventDefault();
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        mobileMenuOpen.value = false;
    }
};

const currentLang = ref<'en' | 'ar'>(() => {
    if (typeof window !== 'undefined') {
        return (localStorage.getItem('lang') as 'en' | 'ar') || 'en';
    }
    return 'en';
});

const toggleLanguage = () => {
    const newLang = currentLang.value === 'en' ? 'ar' : 'en';
    currentLang.value = newLang;
    if (typeof window !== 'undefined') {
        localStorage.setItem('lang', newLang);
        // Apply language change (you can add your i18n logic here)
        document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr');
        document.documentElement.setAttribute('lang', newLang);
    }
};
</script>

<template>
    <header 
        :class="`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
            isScrolled 
                ? 'bg-white/80 backdrop-blur-xl border-b border-gray-200/50 shadow-sm' 
                : 'bg-transparent'
        }`"
    >
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="flex items-center h-16 lg:h-20">
                <!-- Logo -->
                <div class="flex flex-1 items-center">
                    <a href="/" class="group">
                        <span class="text-xl lg:text-2xl font-bold tracking-tight text-gray-900">
                            attenda<span class="text-gray-400">.</span>
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden flex-1 lg:flex items-center justify-center gap-1">
                    <a
                        href="#features"
                        @click="(e) => smoothScroll(e, 'features')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-full transition-colors duration-200"
                    >
                        Features
                    </a>
                    <a
                        href="#pricing"
                        @click="(e) => smoothScroll(e, 'pricing')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-full transition-colors duration-200"
                    >
                        Pricing
                    </a>
                    <a
                        href="#about"
                        @click="(e) => smoothScroll(e, 'about')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-full transition-colors duration-200"
                    >
                        About
                    </a>
                </nav>

                <!-- Desktop Actions -->
                <div class="hidden flex-1 lg:flex items-center justify-end gap-3">
                    <Button
                        v-if="!isAuthenticated"
                        variant="ghost"
                        @click="onLoginClick"
                        class="text-sm font-medium"
                    >
                        Sign In
                    </Button>
                    <button
                        @click="toggleLanguage"
                        class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200"
                        :aria-label="currentLang === 'en' ? 'Switch to Arabic' : 'Switch to English'"
                        :title="currentLang === 'en' ? 'Switch to Arabic' : 'Switch to English'"
                    >
                        <Globe class="h-5 w-5 text-gray-700" />
                    </button>
                    <Button
                        v-if="!isAuthenticated"
                        @click="onRegisterClick"
                        class="bg-black hover:bg-gray-800 text-white text-sm font-medium px-6 rounded-full transition-all duration-200 hover:scale-105"
                    >
                        Get Started
                    </Button>
                    <Button
                        v-if="isAuthenticated"
                        @click="goToDashboard"
                        class="bg-black hover:bg-gray-800 text-white text-sm font-medium px-6 rounded-full transition-all duration-200 hover:scale-105"
                    >
                        Dashboard
                    </Button>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    @click="toggleMobileMenu"
                    class="lg:hidden p-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
                    aria-label="Toggle menu"
                >
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <div
                v-if="mobileMenuOpen"
                class="lg:hidden border-t border-gray-200 bg-white/95 backdrop-blur-xl"
            >
                <nav class="container mx-auto px-4 py-6 space-y-4">
                    <a
                        href="#features"
                        @click="(e) => smoothScroll(e, 'features')"
                        class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        Features
                    </a>
                    <a
                        href="#pricing"
                        @click="(e) => smoothScroll(e, 'pricing')"
                        class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        Pricing
                    </a>
                    <a
                        href="#about"
                        @click="(e) => smoothScroll(e, 'about')"
                        class="block px-4 py-3 text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        About
                    </a>
                    <div class="pt-4 space-y-3 border-t border-gray-200">
                        <Button
                            v-if="!isAuthenticated"
                            variant="ghost"
                            @click="onLoginClick"
                            class="w-full justify-start"
                        >
                            Sign In
                        </Button>
                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleLanguage"
                                class="flex items-center gap-2 px-4 py-3 w-full text-base font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                                :aria-label="currentLang === 'en' ? 'Switch to Arabic' : 'Switch to English'"
                            >
                                <Globe class="h-5 w-5" />
                                <span>{{ currentLang === 'en' ? 'العربية' : 'English' }}</span>
                            </button>
                            <Button
                                v-if="!isAuthenticated"
                                @click="onRegisterClick"
                                class="flex-1 bg-black hover:bg-gray-800 text-white"
                            >
                                Get Started
                            </Button>
                        </div>
                        <Button
                            v-if="isAuthenticated"
                            @click="goToDashboard"
                            class="w-full bg-black hover:bg-gray-800 text-white"
                        >
                            Dashboard
                        </Button>
                    </div>
                </nav>
            </div>
        </transition>
    </header>
</template>
