<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Header from './Header.vue';
import Hero from './Hero.vue';
import Features from './Features.vue';
import Pricing from './Pricing.vue';
import Testimonials from './Testimonials.vue';
import CTA from './CTA.vue';
import About from './About.vue';
import ChatWidget from '@/components/ChatWidget.vue';
import Footer from './Footer.vue';
import CompanyRegisterForm from '@/components/CompanyRegisterForm.vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';

const props = defineProps<{
    plans?: Array<{
        id: number;
        name: string;
        price: string;
        price_raw: number;
        yearly_price: string | null;
        yearly_price_raw: number | null;
        max_employees: number;
        features: string[];
        popular: boolean;
        description: string;
    }>;
    partnerLogos?: Array<{
        id: number;
        logo_url: string;
        company_name?: string;
        testimonial?: string;
    }>;
}>();

const showRegister = ref(false);
const selectedPlan = ref<any>(null);
const selectedBillingPeriod = ref<'monthly' | 'yearly'>('monthly');

const openRegister = (payload?: any) => {
    if (payload && payload.plan) {
        selectedPlan.value = payload.plan;
        selectedBillingPeriod.value = payload.billingPeriod || 'monthly';
    } else {
        selectedPlan.value = payload || null;
        selectedBillingPeriod.value = 'monthly';
    }
    showRegister.value = true;
};

// SEO Meta Data
const siteUrl = computed(() => window.location.origin);
const pageTitle = 'Attenda - Advanced Cloud HR Management System | HRMS Software';
const pageDescription = 'Streamline your HR operations with Attenda, a comprehensive cloud-based HR management system. Features include employee management, payroll, attendance tracking, leave management, and more. Trusted by 3000+ companies worldwide.';
const pageKeywords = 'HR management system, HRMS software, cloud HR, employee management, payroll software, attendance tracking, leave management, HR software Saudi Arabia, HR system, human resource management';
const ogImage = computed(() => `${siteUrl.value}/asset/logo.png`);

// Structured Data (JSON-LD)
const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    'name': 'Attenda',
    'applicationCategory': 'BusinessApplication',
    'operatingSystem': 'Web',
    'offers': {
        '@type': 'AggregateOffer',
        'priceCurrency': 'SAR',
        'availability': 'https://schema.org/InStock',
    },
    'aggregateRating': {
        '@type': 'AggregateRating',
        'ratingValue': '4.8',
        'ratingCount': '3000',
    },
    'description': pageDescription,
    'url': siteUrl.value,
}));

const organizationData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    'name': 'Attenda',
    'url': siteUrl.value,
    'logo': ogImage.value,
    'description': pageDescription,
    'sameAs': [],
    'contactPoint': {
        '@type': 'ContactPoint',
        'contactType': 'Customer Service',
        'areaServed': 'Worldwide',
    },
}));

const breadcrumbData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
        {
            '@type': 'ListItem',
            'position': 1,
            'name': 'Home',
            'item': siteUrl.value,
        },
    ],
}));

const faqData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    'mainEntity': [
        {
            '@type': 'Question',
            'name': 'What is Attenda HR Management System?',
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': 'Attenda is an advanced cloud-based HR management system (HRMS) that helps businesses manage their human resources from acquisition to retirement. It includes features for employee management, payroll processing, attendance tracking, leave management, and more.',
            },
        },
        {
            '@type': 'Question',
            'name': 'What features does Attenda offer?',
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': 'Attenda offers comprehensive HR features including employee management, attendance tracking, payroll management, performance reviews, document management, security & compliance, time & scheduling, and automated workflows.',
            },
        },
        {
            '@type': 'Question',
            'name': 'Is Attenda suitable for small businesses?',
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': 'Yes, Attenda is designed to scale with businesses of all sizes. Whether you are a small startup or a large enterprise, Attenda adapts to your needs and grows with your company.',
            },
        },
        {
            '@type': 'Question',
            'name': 'How many companies use Attenda?',
            'acceptedAnswer': {
                '@type': 'Answer',
                'text': 'Attenda is trusted by 3000+ companies worldwide, managing over 50,000 employees across 50+ countries.',
            },
        },
    ],
}));
</script>

<template>
    <Head :title="pageTitle">
        <!-- Primary Meta Tags -->
        <meta name="title" :content="pageTitle" />
        <meta name="description" :content="pageDescription" />
        <meta name="keywords" :content="pageKeywords" />
        <meta name="author" content="Attenda" />
        <meta name="robots" content="index, follow" />
        <meta name="language" content="English" />
        <meta name="revisit-after" content="7 days" />
        <link rel="canonical" :href="siteUrl" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="siteUrl" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="pageDescription" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:site_name" content="Attenda" />
        <meta property="og:locale" content="en_US" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="siteUrl" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="pageDescription" />
        <meta name="twitter:image" :content="ogImage" />

        <!-- Additional SEO -->
        <meta name="theme-color" content="#1e3b3b" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="Attenda" />

        <!-- Structured Data -->
        <script type="application/ld+json" v-html="JSON.stringify(structuredData)" />
        <script type="application/ld+json" v-html="JSON.stringify(organizationData)" />
        <script type="application/ld+json" v-html="JSON.stringify(breadcrumbData)" />
        <script type="application/ld+json" v-html="JSON.stringify(faqData)" />
    </Head>
    <div class="min-h-screen bg-white antialiased">
        <Header :on-register-click="openRegister" />
        <main class="relative" role="main">
            <Hero :on-get-started-click="openRegister" :logos="partnerLogos" />
            <Features />
            <Pricing :plans="plans" :on-select-plan="openRegister" />
            <Testimonials />
            <CTA :on-get-started-click="openRegister" />
            <About />
        </main>
        <Footer />

        <!-- Floating Chatbot -->
        <ChatWidget />

        <!-- Registration Modal -->
        <Dialog :open="showRegister" @update:open="showRegister = $event">
            <DialogContent class="max-w-6xl border-none bg-transparent p-0 shadow-none">
                <div
                    class="max-h-[90vh] w-full overflow-y-auto rounded-3xl border bg-white p-6 shadow-2xl sm:p-10"
                >
                    <CompanyRegisterForm 
                        :plans="plans || []" 
                        :selected-plan="selectedPlan"
                        :billing-period="selectedBillingPeriod"
                        @close="showRegister = false"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style>
/* Smooth Scroll */
html {
    scroll-behavior: smooth;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
