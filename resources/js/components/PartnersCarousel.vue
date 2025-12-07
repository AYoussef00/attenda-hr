<script setup lang="ts">
interface PartnerLogo {
    id: number;
    logo_url: string;
    company_name?: string;
}

interface Props {
    logos?: PartnerLogo[];
}

withDefaults(defineProps<Props>(), {
    logos: () => [],
});
</script>

<template>
    <section
        v-if="logos.length > 0"
        class="relative overflow-hidden bg-gradient-to-r from-[#f7f1ff] via-white to-[#f5fbff] py-12 sm:py-14 lg:py-16"
        aria-labelledby="partners-heading"
    >
        <!-- Subtle dotted background -->
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_#d9e2ff_1px,_transparent_0)] bg-[length:32px_32px] opacity-40"
        />

        <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center">
                <p
                    id="partners-heading"
                    class="text-base font-semibold tracking-[0.18em] text-slate-700 uppercase"
                >
                    Trusted by 600+ Organizations
                </p>
            </div>

            <div
                class="flex flex-wrap items-center justify-center gap-x-10 gap-y-8 opacity-80"
            >
                <div
                    v-for="logo in logos"
                    :key="logo.id"
                    class="flex h-10 sm:h-12 items-center justify-center grayscale hover:grayscale-0 transition-all duration-200"
                >
                    <img
                        :src="logo.logo_url"
                        :alt="logo.company_name || 'Partner Logo'"
                        class="max-h-full w-auto max-w-[140px] object-contain"
                        loading="lazy"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
