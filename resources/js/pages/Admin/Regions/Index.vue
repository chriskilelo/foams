<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type Region = {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
    counties_count: number;
    deleted_at: string | null;
};

defineProps<{ regions: Region[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regions', href: RegionController.index.url() },
];

function destroyRegion(region: Region) {
    if (!window.confirm(`Delete region "${region.name}"? This will hide it from all non-admin views.`)) {
        return;
    }
    router.delete(RegionController.destroy.url(region.id));
}
</script>

<template>
    <Head title="Regions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <MapPin class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-semibold text-foreground">Regions</h1>
                    <Badge variant="secondary">{{ regions.length }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="RegionController.create.url()">
                        <Plus class="size-4" />
                        New Region
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Counties</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(region, i) in regions"
                            :key="region.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-medium text-foreground">{{ region.name }}</td>
                            <td class="px-4 py-3 font-mono text-muted-foreground">{{ region.code }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="region.is_active ? 'default' : 'secondary'">
                                    {{ region.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right text-muted-foreground">
                                {{ region.counties_count }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="RegionController.edit.url(region.id)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="destroyRegion(region)"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only">Delete</span>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="regions.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                No regions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
