<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Highcharts from 'highcharts';
// Map module (typed import is fine even if TS can't find types)
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import HCMap from 'highcharts/modules/map';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
HCMap(Highcharts);

const props = defineProps<{
    countries: Array<{
        country_name: string;
        country_code: string;
        visits: number;
    }>;
}>();

const containerRef = ref<HTMLDivElement | null>(null);
let chart: Highcharts.Chart | null = null;

// Map ISO 2-letter codes (our data) to ISO 3-letter codes used by Highcharts world map
const ISO2_TO_A3: Record<string, string> = {
    SA: 'SAU',
    EG: 'EGY',
    AE: 'ARE',
    QA: 'QAT',
    KW: 'KWT',
    BH: 'BHR',
    OM: 'OMN',
    US: 'USA',
    CA: 'CAN',
    GB: 'GBR',
    FR: 'FRA',
    DE: 'DEU',
    ES: 'ESP',
    IT: 'ITA',
    TR: 'TUR',
    IN: 'IND',
    PK: 'PAK',
    BD: 'BGD',
    PH: 'PHL',
    ID: 'IDN',
    NG: 'NGA',
    KE: 'KEN',
    ZA: 'ZAF',
};

const buildSeriesData = () => {
    if (!props.countries?.length) return [];

    return props.countries.map((c) => {
        const iso2 = (c.country_code || '').toUpperCase();
        const code = ISO2_TO_A3[iso2] || iso2;

        return {
            code,
            value: c.visits,
            name: c.country_name || iso2,
        };
    });
};

const initChart = async () => {
    if (!containerRef.value) return;

    try {
        const topology = await fetch(
            'https://code.highcharts.com/mapdata/custom/world.topo.json',
        ).then((response) => response.json());

        const data = buildSeriesData();

        chart = Highcharts.mapChart(containerRef.value, {
            chart: {
                map: topology,
                backgroundColor: 'transparent',
                style: {
                    fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                },
            },
            title: {
                text: '',
            },
            credits: {
                enabled: false,
            },
            mapNavigation: {
                enabled: true,
                buttonOptions: {
                    verticalAlign: 'bottom',
                },
            },
            legend: {
                enabled: false,
            },
            colorAxis: {
                min: 1,
                type: 'logarithmic',
                minColor: '#0f172a',
                maxColor: '#22c55e',
            },
            tooltip: {
                headerFormat: '',
                pointFormat:
                    '<b>{point.name}</b><br/>Visitors: <b>{point.value}</b>',
            },
            series: [
                {
                    type: 'map',
                    name: 'Visitors',
                    joinBy: ['iso-a3', 'code'],
                    data,
                    borderColor: '#0f766e',
                    borderWidth: 0.6,
                    states: {
                        hover: {
                            color: '#22c55e',
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                },
            ],
        } as Highcharts.Options);
    } catch (e) {
        // Silent fail; map will just not render
        // eslint-disable-next-line no-console
        console.error('Failed to init world map', e);
    }
};

onMounted(() => {
    initChart();
});

watch(
    () => props.countries,
    () => {
        if (!chart) return;
        const data = buildSeriesData();
        if (chart.series[0]) {
            (chart.series[0] as Highcharts.Series).setData(data as any, true);
        }
    },
    { deep: true },
);

onUnmounted(() => {
    if (chart) {
        chart.destroy();
        chart = null;
    }
});
</script>

<template>
    <div
        class="relative w-full max-w-2xl overflow-hidden rounded-3xl bg-slate-950 shadow-2xl border border-emerald-900/40"
    >
        <div
            class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950"
        />
        <div
            ref="containerRef"
            class="relative h-[360px] w-full"
        />
        <div class="absolute left-4 bottom-4 text-[11px] text-slate-100/80 z-10">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-md" />
                <span>Visitor origin by country (top {{ countries.length }})</span>
            </div>
        </div>
    </div>
</template>

