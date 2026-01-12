<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import CompanyRegisterForm from '@/components/CompanyRegisterForm.vue';

const props = defineProps<{
    plans: Array<{
        id: number;
        name: string;
        price: string;
        price_raw: number;
        max_employees: number;
        features: string[];
    }>;
}>();
</script>

<template>
    <AuthBase
        title="Create your Attenda HR account"
        description="Fill in your company details, account owner, and choose the best plan for your team."
    >
        <Head title="Company Registration" />

        <CompanyRegisterForm :plans="plans" />
    </AuthBase>
</template>


