<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CheckCircle2, Lock, XCircle, Play } from 'lucide-vue-next';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface PartnerLogo {
    id: number;
    logo_url: string;
    company_name?: string;
}

interface Props {
    onGetStartedClick?: () => void;
    logos?: PartnerLogo[];
    settingsText1?: string;
    settingsText2?: string;
}

const props = withDefaults(defineProps<Props>(), {
    onGetStartedClick: () => {
        router.visit('/company/register');
    },
    logos: () => [],
});

const page = usePage();
const isAuthenticated = computed(() => {
    return !!page.props.auth?.user;
});

const selectedCountryCode = ref('+20'); // Default to Egypt
const countryCodes = [
    { code: '+20', country: 'Egypt', flag: '🇪🇬' },
    { code: '+966', country: 'Saudi Arabia', flag: '🇸🇦' },
    { code: '+971', country: 'UAE', flag: '🇦🇪' },
    { code: '+965', country: 'Kuwait', flag: '🇰🇼' },
    { code: '+974', country: 'Qatar', flag: '🇶🇦' },
    { code: '+973', country: 'Bahrain', flag: '🇧🇭' },
    { code: '+968', country: 'Oman', flag: '🇴🇲' },
    { code: '+1', country: 'USA', flag: '🇺🇸' },
    { code: '+44', country: 'UK', flag: '🇬🇧' },
    { code: '+33', country: 'France', flag: '🇫🇷' },
    { code: '+49', country: 'Germany', flag: '🇩🇪' },
];

const demoForm = useForm({
    first_name: '',
    business_email: '',
    company_name: '',
    phone_number: '',
    number_of_employees: '',
    company_headquarters: 'Egypt',
    choose_time_slot: 'No',
});

const showSuccessMessage = ref(false);

const submitDemoRequest = () => {
    // Combine country code with phone number
    const fullPhoneNumber = selectedCountryCode.value + ' ' + demoForm.phone_number;
    
    demoForm.transform((data) => ({
        ...data,
        phone_number: fullPhoneNumber,
    })).post('/demo-request', {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessMessage.value = true;
            demoForm.reset();
            selectedCountryCode.value = '+20'; // Reset to Egypt
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 5000);
        },
    });
};
</script>

<template>
    <section
        class="relative overflow-hidden bg-gradient-to-br from-[#f7f1ff] via-white to-[#f5fbff] pt-24 pb-20 lg:pt-32 lg:pb-32"
        aria-labelledby="hero-heading"
    >
        <!-- Subtle dotted background -->
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_#d9e2ff_1px,_transparent_0)] bg-[length:32px_32px] opacity-40"
        />
        <div class="pointer-events-none absolute -right-40 top-10 h-96 w-96 rounded-full bg-purple-200/40 blur-3xl" />
        <div class="pointer-events-none absolute -left-40 bottom-0 h-96 w-96 rounded-full bg-pink-200/40 blur-3xl" />

        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-start gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Left Section: Marketing Content -->
                <div class="space-y-8">
                    <!-- Badge -->
                    <div
                        class="inline-flex items-center rounded-full border border-slate-200 bg-white/80 px-4 py-1 text-xs font-medium text-slate-700 shadow-sm"
                    >
                        #1 Performance Review Platform
                    </div>

                    <!-- Main Headline -->
                    <h1
                        id="hero-heading"
                        class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl lg:text-5xl xl:text-[3.25rem]"
                    >
                        {{ props.settingsText1 || 'Finally, a performance management platform that works your way.' }}
                    </h1>

                    <!-- Description -->
                    <p class="max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                        {{ props.settingsText2 || 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.' }}
                    </p>

                    <!-- Ratings Row -->
                    <div class="space-y-4 pt-2">
                        <div class="flex flex-wrap items-center gap-6">
                            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-xs font-semibold text-orange-500">
                                    ★
                                </span>
                                <span><span class="font-semibold">4.7</span> on G2</span>
                    </div>
                            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-500">
                                    ★
                                </span>
                                <span><span class="font-semibold">4.6</span> on Capterra</span>
                        </div>
                        </div>

                        <!-- Watch Now Button (video icon + text) -->
                        <div>
                            <Button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full bg-black px-5 py-2 text-sm font-medium text-white shadow-md hover:bg-black/90 transition-colors"
                            >
                                <Play class="h-4 w-4" />
                                <span>Watch Now</span>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Demo Request Form -->
                <aside
                    class="relative rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl backdrop-blur-sm sm:p-8 lg:p-10"
                    aria-label="Request a demo"
                >
                    <!-- Step indicator -->
                    <div class="mb-8 flex items-center gap-4 text-xs font-medium text-slate-700">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-600 text-[11px] font-semibold text-white"
                            >
                                1
                            </span>
                            <span>Details</span>
                        </div>
                        <div class="h-px flex-1 bg-slate-200">
                            <div class="h-px w-1/2 bg-slate-800" />
                        </div>
                        <div class="flex items-center gap-2 text-slate-400">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 text-[11px] font-semibold"
                            >
                                2
                            </span>
                            <span>Date &amp; Time</span>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <Alert
                        v-if="showSuccessMessage"
                        variant="default"
                        class="mb-6 border-green-500 bg-green-50 text-green-900"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        <AlertTitle>Success!</AlertTitle>
                        <AlertDescription>
                            Your demo request has been submitted successfully. We will contact you soon!
                        </AlertDescription>
                    </Alert>

                    <!-- Error Messages -->
                    <Alert
                        v-if="demoForm.errors && Object.keys(demoForm.errors).length > 0"
                        variant="destructive"
                        class="mb-6"
                    >
                        <XCircle class="h-4 w-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>
                            <ul class="list-disc list-inside">
                                <li v-for="(error, field) in demoForm.errors" :key="field">
                                    {{ error }}
                                </li>
                            </ul>
                        </AlertDescription>
                    </Alert>

                    <form @submit.prevent="submitDemoRequest" class="space-y-6">
                        <!-- First Row: Name & Number of Employees -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="first_name" class="text-sm font-medium text-slate-700">
                                    Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="first_name"
                                    v-model="demoForm.first_name"
                                    type="text"
                                    required
                                    class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                    placeholder="Enter your name"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="number_of_employees" class="text-sm font-medium text-slate-700">
                                    Number of Employees <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="number_of_employees"
                                    v-model="demoForm.number_of_employees"
                                    type="text"
                                    required
                                    class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                    placeholder="Please select"
                                />
                            </div>
                        </div>

                        <!-- Company Name -->
                            <div class="space-y-2">
                            <Label for="company_name" class="text-sm font-medium text-slate-700">
                                    Company Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="company_name"
                                    v-model="demoForm.company_name"
                                    type="text"
                                    required
                                class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                    placeholder="Your company name"
                            />
                        </div>

                        <!-- Second Row: Work Email & Phone Number -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="business_email" class="text-sm font-medium text-slate-700">
                                    Work Email <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="business_email"
                                    v-model="demoForm.business_email"
                                    type="email"
                                    required
                                    class="h-11 rounded-xl border-slate-200 bg-slate-50/60 focus:border-slate-900 focus:ring-slate-900"
                                    placeholder="you@company.com"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="phone_number" class="text-sm font-medium text-slate-700">
                                    Phone number <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <select
                                        v-model="selectedCountryCode"
                                        class="absolute left-2 top-1/2 z-10 h-7 -translate-y-1/2 rounded-full border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-slate-900 cursor-pointer"
                                    >
                                        <option
                                            v-for="country in countryCodes"
                                            :key="country.code"
                                            :value="country.code"
                                        >
                                            {{ country.flag }} {{ country.code }}
                                        </option>
                                    </select>
                                    <Input
                                        id="phone_number"
                                        v-model="demoForm.phone_number"
                                        type="tel"
                                        required
                                        class="h-11 rounded-xl border-slate-200 bg-slate-50/60 pl-28 focus:border-slate-900 focus:ring-slate-900"
                                        placeholder="Enter phone number"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Third Row: Region (maps to company_headquarters) -->
                            <div class="space-y-2">
                            <Label for="company_headquarters" class="text-sm font-medium text-slate-700">
                                Region <span class="text-red-500">*</span>
                                </Label>
                                <select
                                    id="company_headquarters"
                                    v-model="demoForm.company_headquarters"
                                    required
                                class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-800 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                                >
                                    <option value="Egypt">Egypt</option>
                                    <option value="Saudi Arabia">Saudi Arabia</option>
                                    <option value="UAE">UAE</option>
                                    <option value="Kuwait">Kuwait</option>
                                    <option value="Qatar">Qatar</option>
                                    <option value="Bahrain">Bahrain</option>
                                    <option value="Oman">Oman</option>
                                    <option value="Other">Other</option>
                                </select>
                        </div>

                        <!-- Hidden choose_time_slot (kept for backend validation) -->
                                    <input
                                        v-model="demoForm.choose_time_slot"
                            type="hidden"
                                    />

                        <!-- Submit Button -->
                        <div class="pt-2">
                        <Button
                            type="submit"
                            :disabled="demoForm.processing"
                                class="mx-auto flex h-11 w-full items-center justify-center rounded-full bg-black px-10 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-[1.02] hover:bg-black/90 disabled:opacity-50 sm:w-auto"
                        >
                                <span v-if="demoForm.processing">Booking...</span>
                                <span v-else>Book Demo</span>
                        </Button>
                        </div>
                    </form>
                </aside>
            </div>

            <!-- Trusted logos strip (inside hero section) -->
            <div
                v-if="props.logos && props.logos.length > 0"
                class="mt-16 border-t border-white/40 pt-10"
            >
                <p
                    class="mb-8 text-center text-xs font-semibold uppercase tracking-[0.18em] text-slate-700"
                >
                    Trusted by 600+ Organizations
                </p>
                <div
                    class="flex flex-wrap items-center justify-center gap-x-14 gap-y-12 opacity-80"
                >
                    <div
                        v-for="logo in props.logos"
                        :key="logo.id"
                        class="flex h-14 sm:h-20 items-center justify-center grayscale hover:grayscale-0 transition-all duration-200"
                    >
                        <img
                            :src="logo.logo_url"
                            :alt="logo.company_name ? `شعار شركة ${logo.company_name}` : 'شعار شريك Attenda'"
                            class="max-h-full w-auto max-w-[220px] object-contain"
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
