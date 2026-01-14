<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next';
</script>

<template>
    <section id="payroll-management" class="py-32 lg:py-40 bg-white relative overflow-hidden" aria-labelledby="payroll-management-heading">
        <!-- Background Elements -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-green-500/5 rounded-full blur-3xl" />
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl" />
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Side: Image -->
                <div class="relative order-2 lg:order-1">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl">
                        <img
                            src="/asset/payroll-mgmt.webp"
                            alt="Payroll Management"
                            class="w-full h-auto object-cover"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        />
                    </div>
                </div>

                <!-- Right Side: Text Content -->
                <div class="space-y-8 order-1 lg:order-2">
                    <h2 id="payroll-management-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                        PAYROLL MANAGEMENT
                    </h2>
                    <p class="text-xl lg:text-2xl font-light text-gray-600 leading-relaxed">
                        Simplify the complex processes of the payroll with attenda, also a powerful, secure, accurate, automated and 100% compliant system! Rapid HR provides quicker processing, precise payouts, any business that wants to prevent potential financial and legal repercussions from non-compliance and guarantee that employees are paid accurately and on time with precise payroll administration.
                    </p>

                    <!-- Features List -->
                    <div class="space-y-4 pt-4">
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Quickly determine payroll calculations and deductions</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Generate accurate Payslips</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Using payroll management to plan future costs</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                            <span class="text-lg text-gray-700">Secure Data's and Privacy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
