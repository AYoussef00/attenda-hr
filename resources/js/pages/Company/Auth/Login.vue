<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertCircle, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showErrorAlert = ref(false);
const errorMessage = ref('');

// Watch for form errors and show alert
watch(() => form.errors.email, (newError) => {
    if (newError) {
        errorMessage.value = newError;
        showErrorAlert.value = true;
    }
}, { immediate: true });

const closeAlert = () => {
    showErrorAlert.value = false;
    errorMessage.value = '';
};

const submit = () => {
    showErrorAlert.value = false;
    form.post('/company/login', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
        onError: () => {
            if (form.errors.email) {
                errorMessage.value = form.errors.email;
                showErrorAlert.value = true;
            }
        },
    });
};
</script>

<template>
    <AuthBase
        title="Log in to your company"
        description="Enter your email and password below to log in"
    >
        <Head title="Attenda - Company Login | Sign In to HR Management System">
            <meta name="description" content="سجل دخولك إلى نظام Attenda لإدارة الموارد البشرية. وصول آمن إلى لوحة تحكم الشركة وإدارة الموظفين والرواتب." />
        </Head>

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <!-- Error Alert -->
        <div
            v-if="showErrorAlert && errorMessage"
            class="mb-4 w-full rounded-lg border border-red-200 bg-red-50 p-4 shadow-sm animate-in slide-in-from-top-2"
        >
            <div class="flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-semibold text-red-900 mb-1">
                        Authentication Error
                    </h4>
                    <p class="text-sm text-red-800 leading-normal">
                        {{ errorMessage }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeAlert"
                    class="flex-shrink-0 rounded-md p-1 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors"
                    aria-label="Close alert"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        :class="{ 'border-destructive': form.errors.email }"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            :tabindex="3"
                        />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <Spinner v-if="form.processing" />
                    Log in
                </Button>
            </div>
        </form>
    </AuthBase>
</template>

