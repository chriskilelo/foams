<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    ClipboardList,
    MapPin,
    Pencil,
    Trash2,
    Wifi,
} from 'lucide-vue-next';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type Region = { id: number; name: string };
type County = { id: number; name: string; code: string; region: Region };
type AssignedUser = { id: number; name: string } | null;
type Asset = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    status: string;
    location_name: string;
    latitude: string | null;
    longitude: string | null;
    assigned_to: AssignedUser;
    installation_date: string | null;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
    county: County;
};
type StatusLog = {
    id: number;
    status: string;
    logged_date: string;
    observed_at: string | null;
    throughput_mbps: string | null;
    remarks: string | null;
    is_amendment: boolean;
    user: { id: number; name: string };
};

const props = defineProps<{
    asset: Asset;
    open_issues_count: number | undefined;
    recent_logs: StatusLog[] | undefined;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Assets', href: AssetController.index.url() },
    { title: props.asset.asset_code, href: AssetController.show.url(props.asset.id) },
];

const typeLabels: Record<string, string> = {
    wifi_hotspot: 'Public WiFi Hotspot',
    nofbi_node: 'NOFBI Node',
    ogn_equipment: 'OGN Equipment',
};

function statusBadgeClass(status: string): string {
    const map: Record<string, string> = {
        operational: 'bg-green-100 text-green-800 border-green-200',
        degraded: 'bg-amber-100 text-amber-800 border-amber-200',
        down: 'bg-red-100 text-red-800 border-red-200',
        maintenance: 'bg-blue-100 text-blue-800 border-blue-200',
        decommissioned: 'bg-gray-100 text-gray-500 border-gray-200',
    };
    return map[status] ?? '';
}

function logStatusBadgeClass(status: string): string {
    const map: Record<string, string> = {
        operational: 'bg-green-100 text-green-800',
        degraded: 'bg-amber-100 text-amber-800',
        down: 'bg-red-100 text-red-800',
        maintenance: 'bg-blue-100 text-blue-800',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
}

function destroyAsset() {
    if (!window.confirm(`Delete asset "${props.asset.asset_code}"? This cannot be undone.`)) {
        return;
    }
    router.delete(AssetController.destroy.url(props.asset.id));
}
</script>

<template>
    <Head :title="`${asset.asset_code} — ${asset.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Page Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Button as-child variant="ghost" size="icon-sm" class="mt-0.5">
                        <Link :href="AssetController.index.url()">
                            <ArrowLeft class="size-4" />
                            <span class="sr-only">Back</span>
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-lg font-bold text-foreground">{{ asset.asset_code }}</span>
                            <span
                                :class="statusBadgeClass(asset.status)"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                            >
                                {{ asset.status }}
                            </span>
                        </div>
                        <h1 class="mt-0.5 text-xl font-semibold text-foreground">{{ asset.name }}</h1>
                        <p class="text-sm text-muted-foreground">{{ typeLabels[asset.type] ?? asset.type }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="AssetController.edit.url(asset.id)">
                            <Pencil class="size-4" />
                            Edit
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="destroyAsset"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Asset Details Card -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-border bg-card p-5">
                        <h2 class="mb-4 flex items-center gap-2 font-medium text-foreground">
                            <ClipboardList class="size-4 text-muted-foreground" />
                            Asset Information
                        </h2>

                        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Location</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ asset.location_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">County / Region</dt>
                                <dd class="mt-1 text-sm text-foreground">
                                    {{ asset.county.name }}
                                    <span class="text-muted-foreground">· {{ asset.county.region.name }}</span>
                                </dd>
                            </div>
                            <div v-if="asset.latitude && asset.longitude">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Coordinates</dt>
                                <dd class="mt-1 font-mono text-sm text-foreground">
                                    {{ asset.latitude }}, {{ asset.longitude }}
                                </dd>
                            </div>
                            <div v-if="asset.installation_date">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Installed</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ asset.installation_date }}</dd>
                            </div>
                            <div v-if="asset.manufacturer">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Manufacturer</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ asset.manufacturer }}</dd>
                            </div>
                            <div v-if="asset.model">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Model</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ asset.model }}</dd>
                            </div>
                            <div v-if="asset.serial_number">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Serial Number</dt>
                                <dd class="mt-1 font-mono text-sm text-foreground">{{ asset.serial_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Assigned To</dt>
                                <dd class="mt-1 text-sm text-foreground">
                                    {{ asset.assigned_to ? asset.assigned_to.name : 'Unassigned' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Stats Sidebar -->
                <div class="flex flex-col gap-4">
                    <!-- Open Issues Count (deferred) -->
                    <div class="rounded-xl border border-border bg-card p-5">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <AlertTriangle class="size-4" />
                            Open Issues
                        </h2>
                        <div v-if="open_issues_count === undefined">
                            <Skeleton class="h-9 w-16" />
                        </div>
                        <div v-else class="text-3xl font-bold text-foreground">
                            {{ open_issues_count }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Status Logs (deferred) -->
            <div class="rounded-xl border border-border bg-card">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="flex items-center gap-2 font-medium text-foreground">
                        <Wifi class="size-4 text-muted-foreground" />
                        Recent Status Logs
                    </h2>
                </div>

                <div v-if="recent_logs === undefined" class="p-5">
                    <div class="space-y-3">
                        <Skeleton v-for="n in 3" :key="n" class="h-10 w-full" />
                    </div>
                </div>

                <table v-else-if="recent_logs.length > 0" class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Throughput</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Logged By</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(log, i) in recent_logs"
                            :key="log.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-mono text-muted-foreground">
                                {{ log.logged_date }}
                                <Badge v-if="log.is_amendment" variant="outline" class="ml-1 text-xs">
                                    Amended
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="logStatusBadgeClass(log.status)"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ log.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ log.throughput_mbps ? `${log.throughput_mbps} Mbps` : '—' }}
                            </td>
                            <td class="px-4 py-3 text-foreground">{{ log.user.name }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-muted-foreground">
                                {{ log.remarks ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="px-4 py-8 text-center text-muted-foreground">
                    No status logs recorded yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
