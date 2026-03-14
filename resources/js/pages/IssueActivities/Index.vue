<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import IssueActivityController from '@/actions/App/Http/Controllers/Issues/IssueActivityController';
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

type ActivityUser = { id: number; name: string } | null;
type ActivityIssue = { id: number; reference_number: string } | null;

type IssueActivity = {
    id: number;
    action_type: string;
    previous_status: string | null;
    new_status: string | null;
    comment: string | null;
    is_internal: boolean;
    created_at: string;
    issue: ActivityIssue;
    user: ActivityUser;
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

type Filters = { action_type?: string };

const props = defineProps<{
    activities: Paginated<IssueActivity>;
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Issue Activities', href: IssueActivityController.index.url() },
];

const ACTION_TYPES = [
    { value: 'status_change', label: 'Status Change' },
    { value: 'comment', label: 'Comment' },
    { value: 'field_note', label: 'Field Note' },
    { value: 'escalation', label: 'Escalation' },
    { value: 'assignment', label: 'Assignment' },
];

const actionTypeBadgeClass = (type: string): string => {
    const map: Record<string, string> = {
        status_change: 'bg-blue-50 text-blue-700 border-blue-200',
        comment: 'bg-gray-50 text-gray-700 border-gray-200',
        field_note: 'bg-amber-50 text-amber-700 border-amber-200',
        escalation: 'bg-red-50 text-red-700 border-red-200',
        assignment: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    };
    return map[type] ?? 'bg-muted text-muted-foreground border-border';
};

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Nairobi',
    });
}

const filters = reactive({
    action_type: props.filters.action_type ?? '',
});

function applyFilters() {
    router.get(
        IssueActivityController.index.url(),
        { action_type: filters.action_type || undefined },
        { preserveState: true, replace: true },
    );
}

watch(() => filters.action_type, applyFilters);
</script>

<template>
    <Head title="Issue Activities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Activity class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-semibold text-foreground">Issue Activities</h1>
                    <Badge variant="secondary">{{ activities.total }}</Badge>
                </div>

                <Select
                    :model-value="filters.action_type || undefined"
                    @update:model-value="filters.action_type = $event ?? ''"
                >
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="t in ACTION_TYPES" :key="t.value" :value="t.value">
                            {{ t.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Timestamp (EAT)</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Issue</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">By</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status Change</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Comment</th>
                            <th class="px-4 py-3 text-center font-medium text-foreground">Internal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(activity, i) in activities.data"
                            :key="activity.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">
                                {{ formatDateTime(activity.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <Link
                                    v-if="activity.issue"
                                    :href="IssueController.show.url(activity.issue.id)"
                                    class="font-mono text-sm font-medium text-[#2E5FA3] hover:underline"
                                >
                                    {{ activity.issue.reference_number }}
                                </Link>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="actionTypeBadgeClass(activity.action_type)"
                                    class="inline-flex items-center rounded border px-1.5 py-0.5 text-xs font-medium capitalize"
                                >
                                    {{ activity.action_type.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-foreground">
                                {{ activity.user?.name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                <template v-if="activity.previous_status || activity.new_status">
                                    <span class="capitalize">{{ activity.previous_status ?? '—' }}</span>
                                    <span class="mx-1 text-muted-foreground/50">→</span>
                                    <span class="capitalize">{{ activity.new_status ?? '—' }}</span>
                                </template>
                                <span v-else class="text-muted-foreground/50">—</span>
                            </td>
                            <td class="px-4 py-3 max-w-xs text-xs text-muted-foreground">
                                <span v-if="activity.comment" class="line-clamp-2">{{ activity.comment }}</span>
                                <span v-else class="text-muted-foreground/50">—</span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                <span v-if="activity.is_internal" class="text-amber-600">Yes</span>
                                <span v-else class="text-muted-foreground/50">—</span>
                            </td>
                        </tr>
                        <tr v-if="activities.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                                No activities found.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="activities.last_page > 1"
                    class="flex items-center justify-between border-t border-border px-4 py-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <template v-if="activities.from">
                            Showing {{ activities.from }}–{{ activities.to }} of {{ activities.total }}
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div class="flex gap-2">
                        <Button v-if="activities.prev_page_url" as-child variant="outline" size="sm">
                            <a :href="activities.prev_page_url">Previous</a>
                        </Button>
                        <Button v-if="activities.next_page_url" as-child variant="outline" size="sm">
                            <a :href="activities.next_page_url">Next</a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
