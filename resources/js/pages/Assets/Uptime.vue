<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import type { BreadcrumbItem } from '@/types';

type CalendarDay = {
    date: string;
    status: 'operational' | 'degraded' | 'down' | 'maintenance' | 'no_log';
};

type Asset = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    status: string;
    county: {
        name: string;
        region: { name: string };
    };
};

const props = defineProps<{
    asset: Asset;
    uptime_percent: number;
    calendar: CalendarDay[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Assets', href: AssetController.index.url() },
    { title: props.asset.asset_code, href: AssetController.show.url(props.asset.id) },
    { title: '30-Day Uptime', href: '#' },
];

const typeLabels: Record<string, string> = {
    wifi_hotspot: 'Public WiFi Hotspot',
    nofbi_node: 'NOFBI Node',
    ogn_equipment: 'OGN Equipment',
};

const uptimeColor = computed(() => {
    if (props.uptime_percent >= 95) return 'text-green-600';
    if (props.uptime_percent >= 80) return 'text-amber-500';
    return 'text-red-600';
});

const uptimeRingColor = computed(() => {
    if (props.uptime_percent >= 95) return '#16a34a';
    if (props.uptime_percent >= 80) return '#f59e0b';
    return '#dc2626';
});

// SVG circle progress
const radius = 54;
const circumference = 2 * Math.PI * radius;
const dashOffset = computed(
    () => circumference - (props.uptime_percent / 100) * circumference,
);

function dayClass(status: string): string {
    const map: Record<string, string> = {
        operational: 'bg-green-500',
        degraded: 'bg-amber-400',
        down: 'bg-red-500',
        maintenance: 'bg-blue-400',
        no_log: 'bg-gray-200',
    };
    return map[status] ?? 'bg-gray-200';
}

function dayLabel(status: string): string {
    const map: Record<string, string> = {
        operational: 'Operational',
        degraded: 'Degraded',
        down: 'Down',
        maintenance: 'Under Maintenance',
        no_log: 'No log submitted',
    };
    return map[status] ?? status;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

const operationalDays = computed(
    () => props.calendar.filter((d) => d.status === 'operational').length,
);
const degradedDays = computed(
    () => props.calendar.filter((d) => d.status === 'degraded').length,
);
const downDays = computed(
    () => props.calendar.filter((d) => d.status === 'down').length,
);
const noLogDays = computed(
    () => props.calendar.filter((d) => d.status === 'no_log').length,
);
</script>

<template>
    <Head :title="`${asset.asset_code} — Uptime`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-start gap-3">
                <Link
                    :href="AssetController.show.url(asset.id)"
                    class="mt-1 rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    <span class="sr-only">Back to asset</span>
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-lg font-bold text-foreground">
                            {{ asset.asset_code }}
                        </span>
                    </div>
                    <h1 class="mt-0.5 text-xl font-semibold text-foreground">
                        {{ asset.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ typeLabels[asset.type] ?? asset.type }} ·
                        {{ asset.county.name }}, {{ asset.county.region.name }}
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Uptime Ring -->
                <div class="flex flex-col items-center justify-center rounded-xl border border-border bg-card p-8">
                    <svg width="140" height="140" viewBox="0 0 140 140" class="-rotate-90">
                        <!-- Background track -->
                        <circle
                            cx="70"
                            cy="70"
                            :r="radius"
                            fill="none"
                            stroke="#e5e7eb"
                            stroke-width="12"
                        />
                        <!-- Progress arc -->
                        <circle
                            cx="70"
                            cy="70"
                            :r="radius"
                            fill="none"
                            :stroke="uptimeRingColor"
                            stroke-width="12"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashOffset"
                            style="transition: stroke-dashoffset 0.6s ease"
                        />
                    </svg>
                    <div class="-mt-20 text-center">
                        <p class="text-4xl font-bold" :class="uptimeColor">
                            {{ uptime_percent.toFixed(1) }}%
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">30-Day Uptime</p>
                    </div>

                    <div
                        v-if="uptime_percent < 95"
                        class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-center text-xs font-medium text-amber-700"
                    >
                        Below 95% threshold
                    </div>
                </div>

                <!-- Day Breakdown -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <h2 class="mb-4 text-sm font-semibold text-foreground">
                        30-Day Breakdown
                    </h2>
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-2 text-sm text-muted-foreground">
                                <span class="inline-block h-3 w-3 rounded-sm bg-green-500"></span>
                                Operational
                            </dt>
                            <dd class="text-sm font-semibold text-foreground">
                                {{ operationalDays }} days
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-2 text-sm text-muted-foreground">
                                <span class="inline-block h-3 w-3 rounded-sm bg-amber-400"></span>
                                Degraded
                            </dt>
                            <dd class="text-sm font-semibold text-foreground">
                                {{ degradedDays }} days
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-2 text-sm text-muted-foreground">
                                <span class="inline-block h-3 w-3 rounded-sm bg-red-500"></span>
                                Down
                            </dt>
                            <dd class="text-sm font-semibold text-foreground">
                                {{ downDays }} days
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-2 text-sm text-muted-foreground">
                                <span class="inline-block h-3 w-3 rounded-sm bg-gray-200"></span>
                                No log
                            </dt>
                            <dd class="text-sm font-semibold text-foreground">
                                {{ noLogDays }} days
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Legend -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <h2 class="mb-4 text-sm font-semibold text-foreground">Legend</h2>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded bg-green-500"></span>
                            <span class="text-muted-foreground">Operational — fully functional</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded bg-amber-400"></span>
                            <span class="text-muted-foreground">Degraded — performance reduced</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded bg-red-500"></span>
                            <span class="text-muted-foreground">Down — offline</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded bg-blue-400"></span>
                            <span class="text-muted-foreground">Maintenance — scheduled work</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded bg-gray-200"></span>
                            <span class="text-muted-foreground">No log — not recorded</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 30-Day Calendar Grid -->
            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 text-sm font-semibold text-foreground">Daily Availability</h2>
                <div class="grid grid-cols-6 gap-2 sm:grid-cols-10">
                    <div
                        v-for="day in calendar"
                        :key="day.date"
                        :class="dayClass(day.status)"
                        :title="`${formatDate(day.date)}: ${dayLabel(day.status)}`"
                        class="h-8 w-full cursor-default rounded-sm"
                    ></div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Hover over a square to see the date and status.
                    Each square represents one calendar day, oldest on the left.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
