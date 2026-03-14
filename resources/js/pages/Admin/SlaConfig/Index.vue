<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Pencil, Plus, Settings } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SlaConfigurationController from '@/actions/App/Http/Controllers/Admin/SlaConfigurationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type SlaConfiguration = {
    id: number;
    severity: 'critical' | 'high' | 'medium' | 'low';
    acknowledge_within_hrs: number;
    resolve_within_hrs: number;
    effective_from: string;
    created_by: { id: number; name: string } | null;
};

defineProps<{
    configurations: SlaConfiguration[];
    severities: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SLA Configuration', href: SlaConfigurationController.index.url() },
];

const severityVariant: Record<string, 'destructive' | 'warning' | 'secondary' | 'default'> = {
    critical: 'destructive',
    high: 'warning',
    medium: 'secondary',
    low: 'default',
};

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Nairobi',
    });
}

function formatHrs(hrs: number): string {
    if (hrs < 24) {
        return `${hrs}h`;
    }

    const days = Math.floor(hrs / 24);
    const rem = hrs % 24;

    return rem > 0 ? `${days}d ${rem}h` : `${days}d`;
}
</script>

<template>
    <Head title="SLA Configuration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Settings class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-semibold text-foreground">SLA Configuration</h1>
                    <Badge variant="secondary">{{ configurations.length }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="SlaConfigurationController.create.url()">
                        <Plus class="size-4" />
                        New Config
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Severity</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Acknowledge Within</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Resolve Within</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Effective From</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Created By</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(config, i) in configurations"
                            :key="config.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3">
                                <Badge :variant="severityVariant[config.severity]" class="capitalize">
                                    {{ config.severity }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-1.5 text-foreground">
                                    <Clock class="size-3.5 text-muted-foreground" />
                                    {{ formatHrs(config.acknowledge_within_hrs) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-1.5 text-foreground">
                                    <Clock class="size-3.5 text-muted-foreground" />
                                    {{ formatHrs(config.resolve_within_hrs) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDateTime(config.effective_from) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ config.created_by?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="SlaConfigurationController.edit.url(config.id)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="configurations.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No SLA configurations found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
