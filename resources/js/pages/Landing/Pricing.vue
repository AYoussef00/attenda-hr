<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Check, Sparkles } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Plan {
    id: number | string;
    name: string;
    price: string;
    price_raw: number | null;
    yearly_price: string | null;
    yearly_price_raw: number | null;
    max_employees: number | null;
    features: string[];
    popular: boolean;
    description: string;
}

interface Props {
    plans?: Plan[];
    onSelectPlan?: (payload: any) => void;
}

const props = withDefaults(defineProps<Props>(), {
    plans: () => [],
    onSelectPlan: () => {
        router.visit('/company/register');
    },
});

const plans = props.plans && props.plans.length > 0 ? props.plans : [];
const hoveredPlan = ref<number | null>(null);
const billingPeriod = ref<'monthly' | 'yearly'>('monthly');

// Format price based on billing period (use explicit yearly price if provided)
const getDisplayPrice = (plan: Plan): string => {
    if (billingPeriod.value === 'yearly') {
        if (plan.yearly_price === null) return 'Custom';
        return plan.yearly_price;
    }
    
    return plan.price;
};

// Check if plan is Enterprise (no price)
const isEnterprisePlan = (plan: Plan): boolean => {
    return plan.price === 'Custom' || plan.price_raw === null || plan.name.toLowerCase() === 'enterprise';
};

// Get original yearly price (for potential comparisons if needed later)
const getOriginalYearlyPrice = (monthlyPrice: number): number => {
    return monthlyPrice * 12;
};
</script>

<template>
    <section id="pricing" class="py-32 lg:py-40 bg-gray-50 relative overflow-hidden" aria-labelledby="pricing-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-1/4 left-0 w-96 h-96 bg-black/5 rounded-full blur-3xl" />
            <div class="absolute bottom-1/4 right-0 w-96 h-96 bg-black/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="inline-block px-4 py-2 rounded-full bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 mb-6">
                    <span class="text-sm font-medium text-gray-700">Pricing</span>
                </div>
                <h2 id="pricing-heading" class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-light tracking-tight text-gray-900 mb-6 leading-tight">
                    Choose the perfect plan
                    <br />
                    <span class="font-medium">for your business.</span>
                </h2>
                <!-- Billing Period Toggle (show if there's at least one non-Enterprise plan) -->
                <div 
                    v-if="plans.some(p => !isEnterprisePlan(p))"
                    class="flex items-center justify-center gap-4 mb-8"
                >
                    <span
                        :class="`text-base font-medium transition-colors ${
                            billingPeriod === 'monthly' ? 'text-gray-900' : 'text-gray-500'
                        }`"
                    >
                        Monthly
                    </span>
                    <button
                        @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                        :class="`relative inline-flex h-8 w-14 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 ${
                            billingPeriod === 'yearly' ? 'bg-[#1e3b3b]' : 'bg-gray-300'
                        }`"
                        role="switch"
                        :aria-checked="billingPeriod === 'yearly'"
                    >
                        <span
                            :class="`inline-block h-6 w-6 transform rounded-full bg-white transition-transform ${
                                billingPeriod === 'yearly' ? 'translate-x-7' : 'translate-x-1'
                            }`"
                        />
                    </button>
                    <div class="flex items-center gap-2">
                        <span
                            :class="`text-base font-medium transition-colors ${
                                billingPeriod === 'yearly' ? 'text-gray-900' : 'text-gray-500'
                            }`"
                        >
                            Yearly
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div
                v-if="plans.length > 0"
                :class="`grid gap-8 lg:gap-10 max-w-6xl mx-auto ${
                    plans.length === 1 ? 'md:grid-cols-1' : 
                    plans.length === 2 ? 'md:grid-cols-2' : 
                    'md:grid-cols-3'
                }`"
            >
                <Card
                    v-for="(plan, index) in plans"
                    :key="plan.id"
                    @mouseenter="hoveredPlan = plan.id"
                    @mouseleave="hoveredPlan = null"
                    :class="`relative p-8 lg:p-10 rounded-3xl border-2 transition-all duration-500 flex flex-col ${
                        plan.popular
                            ? 'border-black bg-black text-white shadow-2xl scale-105'
                            : hoveredPlan === plan.id
                            ? 'border-gray-400 bg-white shadow-2xl scale-105'
                            : 'border-gray-200 bg-white hover:border-gray-300'
                    }`"
                >
                    <!-- Popular Badge -->
                    <div
                        v-if="plan.popular"
                        class="absolute -top-4 left-1/2 -translate-x-1/2 bg-white text-black px-5 py-1.5 rounded-full text-sm font-medium flex items-center gap-2 shadow-lg"
                    >
                        <Sparkles class="h-4 w-4" />
                        Most Popular
                    </div>

                    <div class="flex flex-col flex-grow space-y-8">
                        <!-- Header -->
                        <div class="space-y-2">
                            <h3
                                :class="`text-2xl font-medium ${
                                    plan.popular ? 'text-white' : 'text-gray-900'
                                }`"
                            >
                                {{ plan.name }}
                            </h3>
                            <p
                                :class="`text-sm ${
                                    plan.popular ? 'text-gray-300' : 'text-gray-600'
                                }`"
                            >
                                {{ plan.description }}
                            </p>
                        </div>

                        <!-- Price -->
                        <div class="space-y-2">
                            <div v-if="!isEnterprisePlan(plan)" class="flex items-baseline gap-2">
                                <span
                                    :class="`text-lg ${
                                        plan.popular ? 'text-gray-300' : 'text-gray-600'
                                    }`"
                                >
                                    $
                                </span>
                                <span
                                    :class="`text-6xl lg:text-7xl font-light tracking-tight ${
                                        plan.popular ? 'text-white' : 'text-gray-900'
                                    }`"
                                >
                                    {{ getDisplayPrice(plan) }}
                                </span>
                                <span
                                    :class="`text-lg ${
                                        plan.popular ? 'text-gray-300' : 'text-gray-600'
                                    }`"
                                >
                                    /{{ billingPeriod === 'yearly' ? 'year' : 'month' }}
                                </span>
                            </div>
                            
                            <!-- Enterprise Plan - Custom Pricing -->
                            <div v-else class="space-y-2">
                                <div class="flex items-center justify-center">
                                    <span
                                        :class="`text-4xl lg:text-5xl font-medium ${
                                            plan.popular ? 'text-white' : 'text-gray-900'
                                        }`"
                                    >
                                        Custom Pricing
                                    </span>
                                </div>
                                <p
                                    :class="`text-sm text-center ${
                                        plan.popular ? 'text-gray-300' : 'text-gray-600'
                                    }`"
                                >
                                    Tailored solutions for your organization
                                </p>
                            </div>
                            
                            <!-- You can later show discount info by comparing yearly_price_raw to 12 * price_raw -->
                        </div>

                        <!-- Features -->
                        <div class="space-y-4 pt-4 border-t border-gray-200/50">
                            <div
                                v-for="(feature, featureIndex) in plan.features"
                                :key="featureIndex"
                                class="flex items-start gap-3"
                            >
                                <Check
                                    :class="`h-5 w-5 flex-shrink-0 mt-0.5 ${
                                        plan.popular
                                            ? 'text-white'
                                            : 'text-green-600'
                                    }`"
                                />
                                <span
                                    :class="`text-sm ${
                                        plan.popular ? 'text-gray-200' : 'text-gray-700'
                                    }`"
                                >
                                    {{ feature }}
                                </span>
                            </div>
                        </div>

                        <!-- CTA Button - At the bottom -->
                        <div class="mt-auto pt-8">
                            <Button
                                @click="onSelectPlan({ plan, billingPeriod: billingPeriod })"
                                :class="`w-full h-14 rounded-full text-base font-medium transition-all duration-300 ${
                                    plan.popular
                                        ? 'bg-white text-black hover:bg-gray-100 hover:scale-105'
                                        : hoveredPlan === plan.id
                                        ? 'bg-black text-white hover:bg-gray-800 hover:scale-105'
                                        : 'bg-gray-900 text-white hover:bg-gray-800'
                                }`"
                            >
                                {{ isEnterprisePlan(plan) ? 'Contact Sales' : 'Reserve Now' }}
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="text-center py-20"
            >
                <p class="text-xl text-gray-600 font-light">
                    No pricing plans available at the moment. Please check back later.
                </p>
            </div>

            <!-- Footer Note -->
            <div
                v-if="plans.length > 0"
                class="text-center mt-16"
            >
                <p class="text-gray-600 font-light text-lg">
                    All plans include free updates and basic security features.
                    <a
                        href="#"
                        class="text-gray-900 hover:underline font-medium ml-1"
                    >
                        Compare all features →
                    </a>
                </p>
            </div>
        </div>
    </section>
</template>
