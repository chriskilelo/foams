<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CountyController from '@/actions/App/Http/Controllers/Admin/CountyController';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type County = {
    id: number;
    name: string;
    code: string;
    region: { id: number; name: string } | null;
};

const props = defineProps<{ counties: County[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regions', href: RegionController.index.url() },
    { title: 'Counties', href: CountyController.index.url() },
];

const search = ref('');

const filtered = computed(() =>
    props.counties.filter(
        (c) =>
            c.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (c.region?.name ?? '').toLowerCase().includes(search.value.toLowerCase()),
    ),
);

function destroyCounty(county: County) {
    if (!window.confirm(`Delete county "${county.name}"?`)) {
        return;
    }
    router.delete(CountyController.destroy.url(county.id));
}
</script>

<template>
    <Head title="Counties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <MapPin class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-semibold text-foreground">Counties</h1>
                    <Badge variant="secondary">{{ counties.length }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="CountyController.create.url()">
                        <Plus class="size-4" />
                        New County
                    </Link>
                </Button>
            </div>

            <Input
                v-model="search"
                placeholder="Filter by county or region…"
                class="max-w-sm"
            />

            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Region</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(county, i) in filtered"
                            :key="county.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-medium text-foreground">{{ county.name }}</td>
                            <td class="px-4 py-3 font-mono text-muted-foreground">{{ county.code }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ county.region?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="CountyController.edit.url(county.id)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="destroyCounty(county)"
                                    >
                                        <Trash2 class="size-4" />
                                        <span class="sr-only">Delete</span>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                No counties found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
