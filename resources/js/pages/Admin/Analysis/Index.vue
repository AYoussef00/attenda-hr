<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import admin from '@/routes/admin';
import { Globe2, BarChart3, Users, MapPin } from 'lucide-vue-next';
import VisitorsWorldMap from '@/components/VisitorsWorldMap.vue';

const props = defineProps<{
    stats: {
        total_visits: number;
        unique_ips: number;
    };
    topPages: Array<{
        path: string;
        visits: number;
    }>;
    topCountries: Array<{
        country_name: string;
        country_code: string;
        visits: number;
    }>;
    recentVisits: Array<{
        id: number;
        ip_address: string;
        country_code: string | null;
        country_name: string | null;
        city: string | null;
        path: string;
        referrer: string | null;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: admin.dashboard().url,
    },
    {
        title: 'Analysis',
        href: '/system/analysis',
    },
];
</script>

<template>
    <Head title="Attenda - System Analysis | Analytics & Insights">
        <meta name="description" content="تحليل النظام في Attenda. عرض إحصائيات شاملة عن استخدام النظام، الشركات، الموظفين، والأداء." />
    </Head>

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Top Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Total Visits
                        </CardTitle>
                        <Users class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ stats.total_visits }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            All recorded page views
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Unique Visitors
                        </CardTitle>
                        <Users class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">
                            {{ stats.unique_ips }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Based on unique IP addresses
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Top Country
                        </CardTitle>
                        <MapPin class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="topCountries.length" class="text-lg font-semibold">
                            {{ topCountries[0].country_name || 'Unknown' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ topCountries[0]?.visits || 0 }} visits
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
                <!-- World Map with visitor markers -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Globe2 class="h-5 w-5 text-emerald-600" />
                            Visitors by Country
                        </CardTitle>
                        <CardDescription>
                            Visual distribution of visitors around the world (top 20 countries)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-center">
                            <VisitorsWorldMap :countries="topCountries" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Countries List -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5 text-emerald-600" />
                            Top Countries
                        </CardTitle>
                        <CardDescription>
                            Countries with the highest number of visits
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topCountries.length" class="space-y-2">
                            <div
                                v-for="country in topCountries"
                                :key="country.country_code + country.country_name"
                                class="flex items-center justify-between text-sm"
                            >
                                <span>
                                    {{ country.country_name || 'Unknown' }}
                                    <span class="text-xs text-muted-foreground ml-1">
                                        ({{ country.country_code || 'XX' }})
                                    </span>
                                </span>
                                <span class="font-medium">
                                    {{ country.visits }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No visitor data yet.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                <!-- Top Pages -->
                <Card>
                    <CardHeader>
                        <CardTitle>Top Pages</CardTitle>
                        <CardDescription>
                            Most visited pages on your site
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topPages.length" class="space-y-2">
                            <div
                                v-for="page in topPages"
                                :key="page.path"
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ page.path }}
                                </span>
                                <span class="font-medium">
                                    {{ page.visits }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No page view data yet.
                        </p>
                    </CardContent>
                </Card>

                <!-- Recent Visits -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Visits</CardTitle>
                        <CardDescription>
                            Last 50 visitors (IP, country, and page)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-h-80 overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b">
                                        <th class="px-2 py-2 text-left text-[11px] font-medium text-muted-foreground">
                                            Time
                                        </th>
                                        <th class="px-2 py-2 text-left text-[11px] font-medium text-muted-foreground">
                                            IP
                                        </th>
                                        <th class="px-2 py-2 text-left text-[11px] font-medium text-muted-foreground">
                                            Country
                                        </th>
                                        <th class="px-2 py-2 text-left text-[11px] font-medium text-muted-foreground">
                                            Page
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-if="recentVisits.length === 0"
                                    >
                                        <td colspan="4" class="px-2 py-4 text-center text-xs text-muted-foreground">
                                            No visits recorded yet.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="visit in recentVisits"
                                        :key="visit.id"
                                        class="border-b"
                                    >
                                        <td class="px-2 py-1 align-top text-[11px] text-muted-foreground">
                                            {{ visit.created_at }}
                                        </td>
                                        <td class="px-2 py-1 align-top text-[11px]">
                                            {{ visit.ip_address }}
                                        </td>
                                        <td class="px-2 py-1 align-top text-[11px]">
                                            {{ visit.country_name || 'Unknown' }}
                                            <span v-if="visit.city" class="text-[10px] text-muted-foreground">
                                                ({{ visit.city }})
                                            </span>
                                        </td>
                                        <td class="px-2 py-1 align-top text-[11px] font-mono text-[10px] text-muted-foreground">
                                            {{ visit.path }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>


