<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileDown, Search, ScrollText, X } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuditLogController from '@/actions/App/Http/Controllers/Admin/AuditLogController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type AuditLogUser = { id: number; name: string } | null;

type AuditLogRow = {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user: AuditLogUser;
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

type Filters = {
    search?: string;
    user_id?: string;
    event?: string;
    auditable_type?: string;
    date_from?: string;
    date_to?: string;
    ip_address?: string;
};

const props = defineProps<{
    logs: Paginated<AuditLogRow>;
    filters: Filters;
    users: { id: number; name: string }[];
    events: string[];
    auditableTypes: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audit Log', href: AuditLogController.index.url() },
];

// ─── Filters ─────────────────────────────────────────────────────────────────

const form = reactive<Required<Filters>>({
    search: props.filters.search ?? '',
    user_id: props.filters.user_id ?? '',
    event: props.filters.event ?? '',
    auditable_type: props.filters.auditable_type ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    ip_address: props.filters.ip_address ?? '',
});

const hasActiveFilters = computed(() =>
    Object.values(form).some((v) => v !== ''),
);

function applyFilters() {
    router.get(
        AuditLogController.index.url(),
        {
            search: form.search || undefined,
            user_id: form.user_id || undefined,
            event: form.event || undefined,
            auditable_type: form.auditable_type || undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            ip_address: form.ip_address || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    form.search = '';
    form.user_id = '';
    form.event = '';
    form.auditable_type = '';
    form.date_from = '';
    form.date_to = '';
    form.ip_address = '';
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    () => form.search,
    () => {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 350);
    },
);
watch(
    [
        () => form.user_id,
        () => form.event,
        () => form.auditable_type,
        () => form.date_from,
        () => form.date_to,
        () => form.ip_address,
    ],
    applyFilters,
);

// ─── Export URLs (pass active filters as query string) ────────────────────────

const activeQueryParams = computed(() => {
    const params: Record<string, string> = {};
    if (form.search) params.search = form.search;
    if (form.user_id) params.user_id = form.user_id;
    if (form.event) params.event = form.event;
    if (form.auditable_type) params.auditable_type = form.auditable_type;
    if (form.date_from) params.date_from = form.date_from;
    if (form.date_to) params.date_to = form.date_to;
    if (form.ip_address) params.ip_address = form.ip_address;
    return params;
});

function buildExportUrl(base: string): string {
    const qs = new URLSearchParams(activeQueryParams.value).toString();
    return qs ? `${base}?${qs}` : base;
}

const csvExportUrl = computed(() => buildExportUrl(AuditLogController.exportCsv.url()));
const pdfExportUrl = computed(() => buildExportUrl(AuditLogController.exportPdf.url()));

// ─── Detail modal ─────────────────────────────────────────────────────────────

const selectedLog = ref<AuditLogRow | null>(null);
const dialogOpen = ref(false);

function openDetail(log: AuditLogRow) {
    selectedLog.value = log;
    dialogOpen.value = true;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'medium',
        timeZone: 'Africa/Nairobi',
    });
}

function shortModelType(fullType: string): string {
    return fullType.split('\\').pop() ?? fullType;
}

function truncate(value: string | null, length = 40): string {
    if (!value) return '—';
    return value.length > length ? value.slice(0, length) + '…' : value;
}

function prettyJson(value: Record<string, unknown> | null): string {
    if (!value) return '—';
    return JSON.stringify(value, null, 2);
}

const eventColorClass = (event: string): string => {
    if (event.endsWith('.deleted') || event.endsWith('.deactivated')) {
        return 'bg-red-50 text-red-700 border-red-200';
    }
    if (event.endsWith('.created')) {
        return 'bg-green-50 text-green-700 border-green-200';
    }
    if (event.endsWith('.updated')) {
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
    return 'bg-[#EEF4FB] text-[#1F3864] border-[#2E5FA3]/30';
};
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <ScrollText class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-semibold text-foreground">Audit Log</h1>
                    <Badge variant="secondary">{{ logs.total.toLocaleString() }}</Badge>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <a :href="csvExportUrl">
                            <FileDown class="size-4" />
                            Export CSV
                        </a>
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <a :href="pdfExportUrl">
                            <FileDown class="size-4" />
                            Export PDF
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-border bg-card p-4">
                <div class="flex flex-wrap gap-3">
                    <!-- Search -->
                    <div class="relative min-w-52 flex-1">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="form.search"
                            placeholder="Search event, model, IP, user agent…"
                            class="pl-9"
                        />
                    </div>

                    <!-- User -->
                    <Select
                        :model-value="form.user_id || undefined"
                        @update:model-value="form.user_id = $event ?? ''"
                    >
                        <SelectTrigger class="w-44">
                            <SelectValue placeholder="All users" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">
                                {{ u.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Event -->
                    <Select
                        :model-value="form.event || undefined"
                        @update:model-value="form.event = $event ?? ''"
                    >
                        <SelectTrigger class="w-52">
                            <SelectValue placeholder="All events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="ev in events" :key="ev" :value="ev">
                                {{ ev }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Model type -->
                    <Select
                        :model-value="form.auditable_type || undefined"
                        @update:model-value="form.auditable_type = $event ?? ''"
                    >
                        <SelectTrigger class="w-44">
                            <SelectValue placeholder="All model types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in auditableTypes" :key="t" :value="t">
                                {{ shortModelType(t) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <!-- Date from -->
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-muted-foreground whitespace-nowrap">From</label>
                        <Input
                            v-model="form.date_from"
                            type="date"
                            class="w-36"
                        />
                    </div>

                    <!-- Date to -->
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-muted-foreground whitespace-nowrap">To</label>
                        <Input
                            v-model="form.date_to"
                            type="date"
                            class="w-36"
                        />
                    </div>

                    <!-- IP address -->
                    <Input
                        v-model="form.ip_address"
                        placeholder="Filter by IP address…"
                        class="w-44"
                    />

                    <!-- Clear -->
                    <Button
                        v-if="hasActiveFilters"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        <X class="size-3.5" />
                        Clear filters
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-[#D6E4F7]/40">
                                <th class="px-4 py-3 text-left font-medium text-foreground">Timestamp (EAT)</th>
                                <th class="px-4 py-3 text-left font-medium text-foreground">User</th>
                                <th class="px-4 py-3 text-left font-medium text-foreground">Event</th>
                                <th class="px-4 py-3 text-left font-medium text-foreground">Model</th>
                                <th class="px-4 py-3 text-left font-medium text-foreground">IP Address</th>
                                <th class="px-4 py-3 text-left font-medium text-foreground">User Agent</th>
                                <th class="px-4 py-3 text-center font-medium text-foreground">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(log, i) in logs.data"
                                :key="log.id"
                                :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">
                                    {{ formatDateTime(log.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-foreground">
                                    {{ log.user?.name ?? '—' }}
                                    <span v-if="!log.user" class="text-xs text-muted-foreground">System</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="eventColorClass(log.event)"
                                        class="inline-block rounded border px-1.5 py-0.5 font-mono text-xs"
                                    >
                                        {{ log.event }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    <span class="font-medium text-foreground">{{ shortModelType(log.auditable_type) }}</span>
                                    <span v-if="log.auditable_id" class="ml-1 font-mono text-xs">#{{ log.auditable_id }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {{ log.ip_address ?? '—' }}
                                </td>
                                <td class="px-4 py-3 max-w-xs text-xs text-muted-foreground">
                                    {{ truncate(log.user_agent, 48) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Button
                                        v-if="log.old_values || log.new_values"
                                        variant="ghost"
                                        size="sm"
                                        class="text-[#2E5FA3] hover:text-[#1F3864] text-xs"
                                        @click="openDetail(log)"
                                    >
                                        View
                                    </Button>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                                    No audit log entries found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="logs.last_page > 1"
                    class="flex items-center justify-between border-t border-border px-4 py-3"
                >
                    <p class="text-sm text-muted-foreground">
                        <template v-if="logs.from">
                            Showing {{ logs.from.toLocaleString() }}–{{ logs.to?.toLocaleString() }} of {{ logs.total.toLocaleString() }}
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div class="flex gap-2">
                        <Button v-if="logs.prev_page_url" as-child variant="outline" size="sm">
                            <Link :href="logs.prev_page_url">Previous</Link>
                        </Button>
                        <Button v-if="logs.next_page_url" as-child variant="outline" size="sm">
                            <Link :href="logs.next_page_url">Next</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Changes detail modal -->
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <span
                        v-if="selectedLog"
                        :class="eventColorClass(selectedLog.event)"
                        class="inline-block rounded border px-1.5 py-0.5 font-mono text-xs"
                    >
                        {{ selectedLog?.event }}
                    </span>
                    <span class="text-sm font-normal text-muted-foreground">
                        {{ selectedLog ? formatDateTime(selectedLog.created_at) : '' }}
                    </span>
                </DialogTitle>
            </DialogHeader>

            <div v-if="selectedLog" class="grid grid-cols-2 gap-4 pt-2">
                <div>
                    <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">Old Values</p>
                    <pre class="overflow-auto rounded-lg bg-muted p-3 text-xs leading-relaxed max-h-72">{{ prettyJson(selectedLog.old_values) }}</pre>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">New Values</p>
                    <pre class="overflow-auto rounded-lg bg-muted p-3 text-xs leading-relaxed max-h-72">{{ prettyJson(selectedLog.new_values) }}</pre>
                </div>
            </div>

            <div v-if="selectedLog" class="mt-2 rounded-lg border border-border bg-[#EEF4FB] p-3 text-xs text-muted-foreground">
                <span class="font-medium text-foreground">{{ shortModelType(selectedLog.auditable_type) }}</span>
                <span v-if="selectedLog.auditable_id"> #{{ selectedLog.auditable_id }}</span>
                <span class="mx-2">·</span>
                <span>{{ selectedLog.user?.name ?? 'System' }}</span>
                <span v-if="selectedLog.ip_address" class="mx-2">·</span>
                <span v-if="selectedLog.ip_address" class="font-mono">{{ selectedLog.ip_address }}</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
