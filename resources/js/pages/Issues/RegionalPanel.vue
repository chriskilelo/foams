<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { CheckCircle2, MapPin, XCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import RegionalPanelController from '@/actions/App/Http/Controllers/Issues/RegionalPanelController';
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

type County = { id: number; name: string };
type IssueCounty = { id: number; name: string };
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
type Stats = {
    critical_open: number;
    high_open: number;
    medium_open: number;
    resolved_today: number;
};
type OfficerCompliance = {
    id: number;
    name: string;
    logged_today: number;
    total_assets: number;
};
type Filters = {
    severity?: string;
    status?: string;
    county_id?: string;
    issue_type?: string;
    sla_breached?: string;
};

const props = defineProps<{
    stats: Stats;
    issues: PaginatedIssues;
    compliance: OfficerCompliance[];
    counties: County[];
    severities: Option[];
    statuses: Option[];
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regional Panel', href: RegionalPanelController.url() },
];

const filters = reactive({
    severity: props.filters.severity ?? '',
    status: props.filters.status ?? '',
    county_id: props.filters.county_id ?? '',
    issue_type: props.filters.issue_type ?? '',
    sla_breached: props.filters.sla_breached ?? '',
});

const hasActiveFilter = computed(() =>
    Object.values(filters).some(Boolean),
);

function applyFilters() {
    router.get(
        RegionalPanelController.url(),
        {
            severity: filters.severity || undefined,
            status: filters.status || undefined,
            county_id: filters.county_id || undefined,
            issue_type: filters.issue_type || undefined,
            sla_breached: filters.sla_breached || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    filters.severity = '';
    filters.status = '';
    filters.county_id = '';
    filters.issue_type = '';
    filters.sla_breached = '';
}

watch(
    [
        () => filters.severity,
        () => filters.status,
        () => filters.county_id,
        () => filters.issue_type,
        () => filters.sla_breached,
    ],
    applyFilters,
);

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

function slaElapsedFraction(issue: Issue): number {
    if (!issue.sla_due_at || !issue.created_at) return 0;
    const created = new Date(issue.created_at).getTime();
    const due = new Date(issue.sla_due_at).getTime();
    const total = due - created;
    if (total <= 0) return 1;
    return Math.min(1, (Date.now() - created) / total);
}

function slaCssClass(issue: Issue): string {
    if (issue.sla_breached) return 'text-red-600 font-semibold';
    const fraction = slaElapsedFraction(issue);
    if (fraction >= 0.5) return 'text-amber-600 font-medium';
    return 'text-emerald-600';
}

function formatSlaCountdown(issue: Issue): string {
    if (!issue.sla_due_at) return '—';
    if (issue.sla_breached) return 'Breached';
    const diffMs = new Date(issue.sla_due_at).getTime() - Date.now();
    if (diffMs <= 0) return 'Breached';
    const h = Math.floor(diffMs / 3_600_000);
    const m = Math.floor((diffMs % 3_600_000) / 60_000);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

const statTiles = computed(() => [
    {
        label: 'Critical Open',
        value: props.stats.critical_open,
        cls: 'border-l-red-500 bg-red-50',
        valueCls: 'text-red-700',
    },
    {
        label: 'High Open',
        value: props.stats.high_open,
        cls: 'border-l-orange-500 bg-orange-50',
        valueCls: 'text-orange-700',
    },
    {
        label: 'Medium Open',
        value: props.stats.medium_open,
        cls: 'border-l-amber-500 bg-amber-50',
        valueCls: 'text-amber-700',
    },
    {
        label: 'Resolved Today',
        value: props.stats.resolved_today,
        cls: 'border-l-emerald-500 bg-emerald-50',
        valueCls: 'text-emerald-700',
    },
]);

function complianceBarWidth(officer: OfficerCompliance): string {
    if (officer.total_assets === 0) return '0%';
    return Math.min(100, (officer.logged_today / officer.total_assets) * 100) + '%';
}

function complianceBarClass(officer: OfficerCompliance): string {
    if (officer.total_assets === 0) return 'bg-gray-300';
    const pct = officer.logged_today / officer.total_assets;
    if (pct >= 1) return 'bg-emerald-500';
    if (pct >= 0.5) return 'bg-amber-500';
    return 'bg-red-500';
}
</script>

<template>
    <Head title="Regional Panel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-2">
                <MapPin class="text-muted-foreground size-5" />
                <h1 class="text-xl font-semibold text-foreground">Regional Issues Panel</h1>
                <Badge variant="secondary">{{ issues.total }}</Badge>
            </div>

            <!-- Stat tiles -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="tile in statTiles"
                    :key="tile.label"
                    :class="['rounded-xl border border-border border-l-4 p-4', tile.cls]"
                >
                    <p class="text-sm text-muted-foreground">{{ tile.label }}</p>
                    <p :class="['mt-1 text-3xl font-bold tabular-nums', tile.valueCls]">
                        {{ tile.value }}
                    </p>
                </div>
            </div>

            <!-- Officer compliance widget -->
            <div
                v-if="compliance.length > 0"
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <div class="border-b border-border bg-[#D6E4F7]/40 px-4 py-3">
                    <h2 class="text-sm font-semibold text-foreground">
                        Officer Compliance — Today's Status Logs
                    </h2>
                </div>
                <div class="divide-y divide-border">
                    <div
                        v-for="officer in compliance"
                        :key="officer.id"
                        class="flex items-center gap-4 px-4 py-3"
                    >
                        <div class="w-40 shrink-0">
                            <p class="text-sm font-medium text-foreground">{{ officer.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ officer.logged_today }} / {{ officer.total_assets }} assets
                            </p>
                        </div>
                        <div class="flex flex-1 items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                                <div
                                    :class="complianceBarClass(officer)"
                                    :style="{ width: complianceBarWidth(officer) }"
                                    class="h-full transition-all"
                                />
                            </div>
                            <CheckCircle2
                                v-if="officer.logged_today >= officer.total_assets && officer.total_assets > 0"
                                class="size-4 shrink-0 text-emerald-500"
                            />
                            <XCircle
                                v-else
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
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
                    <SelectTrigger class="w-44">
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
                        <SelectItem v-for="c in counties" :key="c.id" :value="String(c.id)">
                            {{ c.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="filters.sla_breached || undefined"
                    @update:model-value="filters.sla_breached = $event ?? ''"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="SLA status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="1">Breached only</SelectItem>
                    </SelectContent>
                </Select>

                <Button v-if="hasActiveFilter" variant="ghost" size="sm" @click="clearFilters">
                    Clear
                </Button>
            </div>

            <!-- Issues table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Reference</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Severity</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">County</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">SLA Countdown</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(issue, i) in issues.data"
                            :key="issue.id"
                            :class="[
                                i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card',
                                issue.sla_breached ? 'border-l-2 border-l-red-500' : '',
                            ]"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-mono text-sm font-medium">
                                <Link
                                    :href="IssueController.show.url(issue.id)"
                                    class="text-[#2E5FA3] hover:underline"
                                >
                                    {{ issue.reference_number }}
                                </Link>
                                <Badge
                                    v-if="issue.is_escalated"
                                    variant="destructive"
                                    class="ml-2 text-xs"
                                >
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
                                    {{ issue.status.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ issue.county.name }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="slaCssClass(issue)" class="text-xs tabular-nums">
                                    {{ formatSlaCountdown(issue) }}
                                </span>
                                <div class="mt-1 h-1 w-20 overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        :class="
                                            issue.sla_breached
                                                ? 'bg-red-500'
                                                : slaElapsedFraction(issue) >= 0.5
                                                  ? 'bg-amber-500'
                                                  : 'bg-emerald-500'
                                        "
                                        :style="{ width: Math.min(100, slaElapsedFraction(issue) * 100) + '%' }"
                                        class="h-full transition-all"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ issue.assigned_to?.name ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="issues.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                                No issues match the current filters.
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
