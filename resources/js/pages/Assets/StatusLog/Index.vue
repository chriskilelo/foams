<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { CheckCircle2, Clock, ClipboardList, AlertCircle, PlusCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusLogController from '@/actions/App/Http/Controllers/Assets/StatusLogController';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type AssetRow = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    status: string;
    location_name: string;
    county: string;
    logged_today: boolean;
};

const props = defineProps<{
    assets: AssetRow[];
    today: string;
    logged_count: number;
    total_count: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Status Logs', href: StatusLogController.index.url() },
];

const typeLabels: Record<string, string> = {
    wifi_hotspot: 'WiFi Hotspot',
    nofbi_node: 'NOFBI Node',
    ogn_equipment: 'OGN Equipment',
};

function assetStatusClass(status: string): string {
    const map: Record<string, string> = {
        operational: 'bg-green-100 text-green-800 border-green-200',
        degraded: 'bg-amber-100 text-amber-800 border-amber-200',
        down: 'bg-red-100 text-red-800 border-red-200',
        maintenance: 'bg-blue-100 text-blue-800 border-blue-200',
        decommissioned: 'bg-gray-100 text-gray-500 border-gray-200',
    };
    return map[status] ?? '';
}

// ─── 4 PM EAT countdown ───────────────────────────────────────────────────────

const countdown = ref('');

function computeCountdown() {
    // EAT = UTC+3; deadline is 16:00 EAT = 13:00 UTC
    const now = new Date();
    const deadline = new Date();
    deadline.setUTCHours(13, 0, 0, 0); // 16:00 EAT

    const diffMs = deadline.getTime() - now.getTime();

    if (diffMs <= 0) {
        countdown.value = 'Submission window closed for today';
        return;
    }

    const totalSecs = Math.floor(diffMs / 1000);
    const hrs = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;

    if (hrs > 0) {
        countdown.value = `${hrs}h ${mins}m until 4:00 PM cut-off`;
    } else if (mins > 0) {
        countdown.value = `${mins}m ${secs}s until 4:00 PM cut-off`;
    } else {
        countdown.value = `${secs}s until 4:00 PM cut-off`;
    }
}

let timer: ReturnType<typeof setInterval>;

onMounted(() => {
    computeCountdown();
    timer = setInterval(computeCountdown, 1000);
});

onUnmounted(() => clearInterval(timer));

const allLogged = computed(() => props.logged_count === props.total_count && props.total_count > 0);

const countdownClass = computed(() => {
    const now = new Date();
    const deadline = new Date();
    deadline.setUTCHours(13, 0, 0, 0);
    const diffMs = deadline.getTime() - now.getTime();
    const diffMins = diffMs / 60000;

    if (diffMs <= 0) { return 'bg-red-50 text-red-700 border-red-200'; }
    if (diffMins < 60) { return 'bg-amber-50 text-amber-700 border-amber-200'; }
    return 'bg-blue-50 text-[#2E5FA3] border-blue-200';
});
</script>

<template>
    <Head title="Daily Status Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-foreground">Daily Status Logs</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Log the operational status of your assigned assets for today,
                        <span class="font-medium text-foreground">{{ today }}</span>.
                    </p>
                </div>

                <!-- Countdown -->
                <div :class="countdownClass" class="flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium">
                    <Clock class="size-4 shrink-0" />
                    {{ countdown }}
                </div>
            </div>

            <!-- Progress Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-border bg-card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Assigned Assets</p>
                    <p class="mt-2 text-3xl font-bold text-foreground">{{ total_count }}</p>
                </div>
                <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-green-700">Logged Today</p>
                    <p class="mt-2 text-3xl font-bold text-green-800">{{ logged_count }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Pending</p>
                    <p class="mt-2 text-3xl font-bold text-amber-800">{{ total_count - logged_count }}</p>
                </div>
            </div>

            <!-- All logged banner -->
            <div
                v-if="allLogged"
                class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"
            >
                <CheckCircle2 class="size-5 shrink-0 text-green-600" />
                <span class="text-sm font-medium">All assigned assets have been logged for today. Great work!</span>
            </div>

            <!-- Asset Table -->
            <div class="rounded-xl border border-border bg-card">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="flex items-center gap-2 font-medium text-foreground">
                        <ClipboardList class="size-4 text-muted-foreground" />
                        Assigned Assets
                    </h2>
                </div>

                <div v-if="assets.length === 0" class="px-5 py-10 text-center text-muted-foreground">
                    <AlertCircle class="mx-auto mb-2 size-8 text-muted-foreground/50" />
                    <p class="text-sm">No assets are currently assigned to you.</p>
                    <p class="mt-1 text-xs">Contact your RICTO to get assets assigned.</p>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Asset</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Location</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Asset Status</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Log Status</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(asset, i) in assets"
                            :key="asset.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="AssetController.show.url(asset.id)"
                                    class="font-mono text-xs font-semibold text-[#2E5FA3] hover:underline"
                                >
                                    {{ asset.asset_code }}
                                </Link>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ asset.name }}</p>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ typeLabels[asset.type] ?? asset.type }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ asset.location_name }}
                                <span class="block text-xs">{{ asset.county }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="assetStatusClass(asset.status)"
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ asset.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="asset.logged_today"
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                                >
                                    <CheckCircle2 class="size-3" />
                                    Logged
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    <Clock class="size-3" />
                                    Pending
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Button as-child size="sm" :variant="asset.logged_today ? 'outline' : 'default'">
                                    <Link :href="StatusLogController.create.url(asset.id)">
                                        <PlusCircle class="size-3.5" />
                                        {{ asset.logged_today ? 'Amend' : 'Log' }}
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
