<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import { AlertTriangle, Plus, Search } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
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
type IssueCounty = { id: number; name: string; region: Region };
type IssueUser = { id: number; name: string } | null;
type Issue = {
    id: number;
    reference_number: string;
    issue_type: string;
    severity: string;
    status: string;
    sla_due_at: string | null;
    sla_breached: boolean;
    is_escalated: boolean;
    created_at: string;
    county: IssueCounty;
    created_by: IssueUser;
    assigned_to: IssueUser;
};
type PaginatedIssues = {
    data: Issue[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
type Option = { value: string; label: string };
type Filters = {
    search?: string;
    severity?: string;
    status?: string;
    county_id?: string;
};

const props = defineProps<{
    issues: PaginatedIssues;
    counties: County[];
    severities: Option[];
    statuses: Option[];
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Issues', href: IssueController.index.url() }];

function severityBadgeClass(severity: string): string {
    const map: Record<string, string> = {
        critical: 'bg-red-100 text-red-800 border-red-200',
        high: 'bg-orange-100 text-orange-800 border-orange-200',
        medium: 'bg-amber-100 text-amber-800 border-amber-200',
        low: 'bg-blue-100 text-blue-800 border-blue-200',
    };
    return map[severity] ?? '';
}

function statusBadgeClass(status: string, breached: boolean): string {
    if (breached) return 'bg-red-100 text-red-800 border-red-200';
    const map: Record<string, string> = {
        new: 'bg-gray-100 text-gray-700 border-gray-200',
        acknowledged: 'bg-blue-100 text-blue-700 border-blue-200',
        in_progress: 'bg-indigo-100 text-indigo-700 border-indigo-200',
        pending_third_party: 'bg-purple-100 text-purple-700 border-purple-200',
        escalated: 'bg-orange-100 text-orange-800 border-orange-200',
        resolved: 'bg-green-100 text-green-700 border-green-200',
        closed: 'bg-gray-100 text-gray-500 border-gray-200',
        duplicate: 'bg-gray-100 text-gray-500 border-gray-200',
    };
    return map[status] ?? '';
}

function slaCssClass(issue: Issue): string {
    if (!issue.sla_due_at) return '';
    if (issue.sla_breached) return 'text-red-600 font-semibold';
    const due = new Date(issue.sla_due_at);
    const diffHours = (due.getTime() - Date.now()) / 3600000;
    if (diffHours < 2) return 'text-orange-600 font-medium';
    return 'text-muted-foreground';
}

function formatSlaCountdown(issue: Issue): string {
    if (!issue.sla_due_at) return '—';
    if (issue.sla_breached) return 'Breached';
    const due = new Date(issue.sla_due_at);
    const diffMs = due.getTime() - Date.now();
    if (diffMs <= 0) return 'Breached';
    const h = Math.floor(diffMs / 3600000);
    const m = Math.floor((diffMs % 3600000) / 60000);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

const filters = reactive({
    search: props.filters.search ?? '',
    severity: props.filters.severity ?? '',
    status: props.filters.status ?? '',
    county_id: props.filters.county_id ?? '',
});

function applyFilters() {
    router.get(
        IssueController.index.url(),
        {
            search: filters.search || undefined,
            severity: filters.severity || undefined,
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
watch([() => filters.severity, () => filters.status, () => filters.county_id], applyFilters);
</script>

<template>
    <Head title="Issues" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AlertTriangle class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-semibold text-foreground">Issues</h1>
                    <Badge variant="secondary">{{ issues.total }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="IssueController.create.url()">
                        <Plus class="size-4" />
                        New Issue
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-56 flex-1">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input v-model="filters.search" placeholder="Search reference, type, description…" class="pl-9" />
                </div>

                <Select
                    :model-value="filters.severity || undefined"
                    @update:model-value="filters.severity = $event ?? ''"
                >
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="Severity" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in severities" :key="s.value" :value="s.value">
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="filters.status || undefined"
                    @update:model-value="filters.status = $event ?? ''"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                            {{ s.label }}
                        </SelectItem>
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
                    v-if="filters.search || filters.severity || filters.status || filters.county_id"
                    variant="ghost"
                    size="sm"
                    @click="
                        filters.search = '';
                        filters.severity = '';
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
                            <th class="px-4 py-3 text-left font-medium text-foreground">Reference</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Severity</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">County</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">SLA</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Assigned To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(issue, i) in issues.data"
                            :key="issue.id"
                            :class="[i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card', issue.sla_breached ? 'border-l-2 border-l-red-500' : '']"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-mono text-sm font-medium">
                                <Link :href="IssueController.show.url(issue.id)" class="text-[#2E5FA3] hover:underline">
                                    {{ issue.reference_number }}
                                </Link>
                                <Badge v-if="issue.is_escalated" variant="destructive" class="ml-2 text-xs">
                                    Escalated
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-foreground">{{ issue.issue_type }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="severityBadgeClass(issue.severity)"
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ issue.severity }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="statusBadgeClass(issue.status, issue.sla_breached)"
                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ issue.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ issue.county.name }}
                                <span class="text-xs text-muted-foreground/60">({{ issue.county.region.name }})</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="slaCssClass(issue)" class="text-xs">
                                    {{ formatSlaCountdown(issue) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ issue.assigned_to?.name ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="issues.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                                No issues found.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="issues.last_page > 1"
                    class="flex items-center justify-between border-t border-border px-4 py-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <template v-if="issues.from">
                            Showing {{ issues.from }}–{{ issues.to }} of {{ issues.total }}
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div class="flex gap-2">
                        <Button v-if="issues.prev_page_url" as-child variant="outline" size="sm">
                            <Link :href="issues.prev_page_url">Previous</Link>
                        </Button>
                        <Button v-if="issues.next_page_url" as-child variant="outline" size="sm">
                            <Link :href="issues.next_page_url">Next</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
