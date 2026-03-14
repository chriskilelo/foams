<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ResolutionController from '@/actions/App/Http/Controllers/Issues/ResolutionController';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type ResolutionIssue = {
    id: number;
    reference_number: string;
    issue_type: string;
    severity: string;
    status: string;
} | null;

type ResolutionUser = { id: number; name: string } | null;

type Resolution = {
    id: number;
    root_cause: string;
    steps_taken: string[];
    resolution_type: string;
    resolved_at: string;
    issue: ResolutionIssue;
    resolved_by: ResolutionUser;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = { type?: string };

const props = defineProps<{
    resolutions: Paginated<Resolution>;
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Resolutions', href: ResolutionController.index.url() },
];

function severityBadgeClass(severity: string): string {
    const map: Record<string, string> = {
        critical: 'bg-red-100 text-red-800 border-red-200',
        high: 'bg-orange-100 text-orange-800 border-orange-200',
        medium: 'bg-amber-100 text-amber-800 border-amber-200',
        low: 'bg-blue-100 text-blue-800 border-blue-200',
    };
    return map[severity] ?? '';
}

function resolutionTypeBadgeClass(type: string): string {
    return type === 'permanent'
        ? 'bg-green-50 text-green-700 border-green-200'
        : 'bg-amber-50 text-amber-700 border-amber-200';
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Nairobi',
    });
}

const filters = reactive({
    type: props.filters.type ?? '',
});

function applyFilters() {
    router.get(
        ResolutionController.index.url(),
        { type: filters.type || undefined },
        { preserveState: true, replace: true },
    );
}

watch(() => filters.type, applyFilters);
</script>

<template>
    <Head title="Resolutions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CheckCircle class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-semibold text-foreground">Resolutions</h1>
                    <Badge variant="secondary">{{ resolutions.total }}</Badge>
                </div>

                <Select
                    :model-value="filters.type || undefined"
                    @update:model-value="filters.type = $event ?? ''"
                >
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="permanent">Permanent</SelectItem>
                        <SelectItem value="temporary">Temporary</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Issue</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Severity</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Root Cause</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Resolved By</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Resolved At (EAT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(resolution, i) in resolutions.data"
                            :key="resolution.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    v-if="resolution.issue"
                                    :href="IssueController.show.url(resolution.issue.id)"
                                    class="font-mono text-sm font-medium text-[#2E5FA3] hover:underline"
                                >
                                    {{ resolution.issue.reference_number }}
                                </Link>
                                <p v-if="resolution.issue" class="mt-0.5 text-xs text-muted-foreground">
                                    {{ resolution.issue.issue_type }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="resolution.issue"
                                    :class="severityBadgeClass(resolution.issue.severity)"
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ resolution.issue.severity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <p class="line-clamp-2 text-sm text-foreground">
                                    {{ resolution.root_cause }}
                                </p>
                                <p v-if="resolution.steps_taken?.length" class="mt-0.5 text-xs text-muted-foreground">
                                    {{ resolution.steps_taken.length }} step{{ resolution.steps_taken.length !== 1 ? 's' : '' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="resolutionTypeBadgeClass(resolution.resolution_type)"
                                    class="inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ resolution.resolution_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ resolution.resolved_by?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">
                                {{ formatDateTime(resolution.resolved_at) }}
                            </td>
                        </tr>
                        <tr v-if="resolutions.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                No resolutions found.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="resolutions.last_page > 1"
                    class="flex items-center justify-between border-t border-border px-4 py-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <template v-if="resolutions.from">
                            Showing {{ resolutions.from }}–{{ resolutions.to }} of {{ resolutions.total }}
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div class="flex gap-2">
                        <Button v-if="resolutions.prev_page_url" as-child variant="outline" size="sm">
                            <a :href="resolutions.prev_page_url">Previous</a>
                        </Button>
                        <Button v-if="resolutions.next_page_url" as-child variant="outline" size="sm">
                            <a :href="resolutions.next_page_url">Next</a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
