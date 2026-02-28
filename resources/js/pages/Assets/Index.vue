<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import { MapPin, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type County = { id: number; name: string };
type Region = { id: number; name: string };
type AssetCounty = { id: number; name: string; code: string; region: Region };
type Asset = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    status: string;
    location_name: string;
    county: AssetCounty;
};
type PaginatedAssets = {
    data: Asset[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
type Filters = {
    search?: string;
    type?: string;
    status?: string;
    county_id?: string;
};

const props = defineProps<{
    assets: PaginatedAssets;
    counties: County[];
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Assets', href: AssetController.index.url() }];

const typeLabels: Record<string, string> = {
    wifi_hotspot: 'Public WiFi',
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

const filters = reactive({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    county_id: props.filters.county_id ?? '',
});

function applyFilters() {
    router.get(
        AssetController.index.url(),
        {
            search: filters.search || undefined,
            type: filters.type || undefined,
            status: filters.status || undefined,
            county_id: filters.county_id || undefined,
        },
        { preserveState: true, replace: true },
    );
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    () => filters.search,
    () => {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 300);
    },
);
watch([() => filters.type, () => filters.status, () => filters.county_id], applyFilters);

function destroyAsset(asset: Asset) {
    if (!window.confirm(`Delete asset "${asset.asset_code} — ${asset.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(AssetController.destroy.url(asset.id));
}
</script>

<template>
    <Head title="Assets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <MapPin class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-semibold text-foreground">Assets</h1>
                    <Badge variant="secondary">{{ assets.total }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="AssetController.create.url()">
                        <Plus class="size-4" />
                        New Asset
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-56 flex-1">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="filters.search" placeholder="Search code, name, location…" class="pl-9" />
                </div>

                <Select
                    :model-value="filters.type || undefined"
                    @update:model-value="filters.type = $event ?? ''"
                >
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="wifi_hotspot">Public WiFi</SelectItem>
                        <SelectItem value="nofbi_node">NOFBI Node</SelectItem>
                        <SelectItem value="ogn_equipment">OGN Equipment</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="filters.status || undefined"
                    @update:model-value="filters.status = $event ?? ''"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="operational">Operational</SelectItem>
                        <SelectItem value="degraded">Degraded</SelectItem>
                        <SelectItem value="down">Down</SelectItem>
                        <SelectItem value="maintenance">Maintenance</SelectItem>
                        <SelectItem value="decommissioned">Decommissioned</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="filters.county_id || undefined"
                    @update:model-value="filters.county_id = $event ?? ''"
                >
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All counties" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="county in counties" :key="county.id" :value="String(county.id)">
                            {{ county.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    v-if="filters.search || filters.type || filters.status || filters.county_id"
                    variant="ghost"
                    size="sm"
                    @click="
                        filters.search = '';
                        filters.type = '';
                        filters.status = '';
                        filters.county_id = '';
                    "
                >
                    Clear
                </Button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Asset Code</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">County</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(asset, i) in assets.data"
                            :key="asset.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-mono text-sm font-medium text-foreground">
                                <Link :href="AssetController.show.url(asset.id)" class="hover:underline">
                                    {{ asset.asset_code }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-foreground">{{ asset.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ typeLabels[asset.type] ?? asset.type }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ asset.county.name }}
                                <span class="text-xs text-muted-foreground/60">({{ asset.county.region.name }})</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="statusBadgeClass(asset.status)"
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ asset.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="AssetController.show.url(asset.id)">
                                            <span class="sr-only">View</span>
                                            <MapPin class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="AssetController.edit.url(asset.id)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="destroyAsset(asset)"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only">Delete</span>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="assets.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                No assets found.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="assets.last_page > 1"
                    class="flex items-center justify-between border-t border-border px-4 py-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <template v-if="assets.from">
                            Showing {{ assets.from }}–{{ assets.to }} of {{ assets.total }}
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div class="flex gap-2">
                        <Button
                            v-if="assets.prev_page_url"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link :href="assets.prev_page_url">Previous</Link>
                        </Button>
                        <Button
                            v-if="assets.next_page_url"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link :href="assets.next_page_url">Next</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
