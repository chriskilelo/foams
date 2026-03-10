<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Clock, MessageSquare, Paperclip } from 'lucide-vue-next';
import { ref } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type Region = { id: number; name: string };
type County = { id: number; name: string; region: Region };
type IssueUser = { id: number; name: string } | null;
type Asset = { id: number; asset_code: string; name: string; type: string } | null;
type Resolution = {
    root_cause: string;
    steps_taken: string[];
    resolution_type: string;
    resolved_at: string;
    resolved_by: IssueUser;
} | null;
type Issue = {
    id: number;
    reference_number: string;
    issue_type: string;
    severity: string;
    status: string;
    description: string;
    workaround_applied: boolean;
    is_escalated: boolean;
    sla_due_at: string | null;
    sla_breached: boolean;
    acknowledged_at: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    created_at: string;
    county: County;
    asset: Asset;
    created_by: IssueUser;
    assigned_to: IssueUser;
    resolution: Resolution;
};
type Activity = {
    id: number;
    action_type: string;
    previous_status: string | null;
    new_status: string | null;
    comment: string | null;
    is_internal: boolean;
    created_at: string;
    user: IssueUser;
};
type Attachment = {
    id: number;
    original_name: string;
    mime_type: string;
    size_bytes: number;
    created_at: string;
    uploaded_by: IssueUser;
};
type Option = { value: string; label: string };
type Can = {
    update_status: boolean;
    escalate: boolean;
    resolve: boolean;
    close: boolean;
};

const props = defineProps<{
    issue: Issue;
    activities: Activity[] | undefined;
    attachments: Attachment[] | undefined;
    resolution_types: Option[];
    allowed_transitions: Option[];
    can: Can;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Issues', href: IssueController.index.url() },
    { title: props.issue.reference_number, href: IssueController.show.url(props.issue.id) },
];

const statusForm = useForm({ status: '', comment: '' });
const escalateForm = useForm({ reason: '' });
const resolveForm = useForm({ root_cause: '', steps_taken: [] as string[], resolution_type: '', comment: '' });
const commentForm = useForm({ action_type: 'comment', comment: '', is_internal: false });

const showStatusModal = ref(false);
const showEscalateModal = ref(false);
const showResolveModal = ref(false);

function submitStatus() {
    statusForm.patch(IssueController.updateStatus.url(props.issue.id), {
        onSuccess: () => { showStatusModal.value = false; statusForm.reset(); },
    });
}

function submitEscalate() {
    escalateForm.post(IssueController.escalate.url(props.issue.id), {
        onSuccess: () => { showEscalateModal.value = false; escalateForm.reset(); },
    });
}

function submitResolve() {
    resolveForm.post(IssueController.resolve.url(props.issue.id), {
        onSuccess: () => { showResolveModal.value = false; resolveForm.reset(); },
    });
}

function submitClose() {
    if (!window.confirm('Close this issue? It will no longer be editable.')) return;
    useForm({}).post(IssueController.close.url(props.issue.id));
}

function submitComment() {
    commentForm.post(`/issues/${props.issue.id}/activities`, {
        onSuccess: () => commentForm.reset(),
    });
}

function severityBadgeClass(severity: string): string {
    const map: Record<string, string> = {
        critical: 'bg-red-100 text-red-800 border-red-200',
        high: 'bg-orange-100 text-orange-800 border-orange-200',
        medium: 'bg-amber-100 text-amber-800 border-amber-200',
        low: 'bg-blue-100 text-blue-800 border-blue-200',
    };
    return map[severity] ?? '';
}

function statusBadgeClass(status: string): string {
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

function activityIcon(type: string): string {
    const map: Record<string, string> = {
        status_change: '🔄',
        comment: '💬',
        field_note: '📋',
        escalation: '⚠️',
        assignment: '👤',
    };
    return map[type] ?? '•';
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}
</script>

<template>
    <Head :title="`${issue.reference_number} — ${issue.issue_type}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Button as-child variant="ghost" size="icon-sm" class="mt-0.5">
                        <Link :href="IssueController.index.url()">
                            <ArrowLeft class="size-4" />
                            <span class="sr-only">Back</span>
                        </Link>
                    </Button>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-lg font-bold text-foreground">{{ issue.reference_number }}</span>
                            <span
                                :class="severityBadgeClass(issue.severity)"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                            >
                                {{ issue.severity }}
                            </span>
                            <span
                                :class="statusBadgeClass(issue.status)"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                            >
                                {{ issue.status.replace(/_/g, ' ') }}
                            </span>
                            <Badge v-if="issue.is_escalated" variant="destructive" class="text-xs">Escalated</Badge>
                            <Badge v-if="issue.sla_breached" variant="destructive" class="text-xs">SLA Breached</Badge>
                        </div>
                        <h1 class="mt-0.5 text-xl font-semibold text-foreground">{{ issue.issue_type }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ issue.county.name }} · {{ issue.county.region.name }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="can.update_status && allowed_transitions.length > 0"
                        variant="outline"
                        size="sm"
                        @click="showStatusModal = true"
                    >
                        Update Status
                    </Button>
                    <Button
                        v-if="can.escalate && !issue.is_escalated && !['resolved','closed','duplicate'].includes(issue.status)"
                        variant="outline"
                        size="sm"
                        class="text-orange-700 border-orange-200 hover:bg-orange-50"
                        @click="showEscalateModal = true"
                    >
                        Escalate
                    </Button>
                    <Button
                        v-if="can.resolve && ['in_progress','pending_third_party','escalated','acknowledged'].includes(issue.status)"
                        variant="outline"
                        size="sm"
                        class="text-green-700 border-green-200 hover:bg-green-50"
                        @click="showResolveModal = true"
                    >
                        Resolve
                    </Button>
                    <Button
                        v-if="can.close && issue.status === 'resolved'"
                        variant="outline"
                        size="sm"
                        @click="submitClose"
                    >
                        Close
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <!-- Issue Info -->
                    <div class="rounded-xl border border-border bg-card p-5">
                        <h2 class="mb-4 text-sm font-medium text-foreground">Issue Information</h2>
                        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Description</dt>
                                <dd class="mt-1 text-sm text-foreground whitespace-pre-wrap">{{ issue.description }}</dd>
                            </div>
                            <div v-if="issue.asset">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Related Asset</dt>
                                <dd class="mt-1 text-sm text-foreground">
                                    {{ issue.asset.asset_code }} — {{ issue.asset.name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Workaround Applied</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ issue.workaround_applied ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Created By</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ issue.created_by?.name ?? 'System' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Assigned To</dt>
                                <dd class="mt-1 text-sm text-foreground">{{ issue.assigned_to?.name ?? 'Unassigned' }}</dd>
                            </div>
                            <div v-if="issue.sla_due_at">
                                <dt class="text-xs font-medium text-muted-foreground uppercase tracking-wide">SLA Deadline</dt>
                                <dd :class="issue.sla_breached ? 'text-red-600 font-semibold' : 'text-foreground'" class="mt-1 text-sm">
                                    {{ issue.sla_due_at }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Resolution (if resolved) -->
                    <div v-if="issue.resolution" class="rounded-xl border border-green-200 bg-green-50 p-5">
                        <h2 class="mb-4 text-sm font-medium text-green-800">Resolution</h2>
                        <dl class="flex flex-col gap-3">
                            <div>
                                <dt class="text-xs font-medium text-green-700 uppercase tracking-wide">Root Cause</dt>
                                <dd class="mt-1 text-sm text-green-900">{{ issue.resolution.root_cause }}</dd>
                            </div>
                            <div v-if="issue.resolution.steps_taken?.length > 0">
                                <dt class="text-xs font-medium text-green-700 uppercase tracking-wide">Steps Taken</dt>
                                <dd class="mt-1">
                                    <ol class="list-decimal list-inside space-y-1 text-sm text-green-900">
                                        <li v-for="(step, i) in issue.resolution.steps_taken" :key="i">{{ step }}</li>
                                    </ol>
                                </dd>
                            </div>
                            <div class="flex gap-6">
                                <div>
                                    <dt class="text-xs font-medium text-green-700 uppercase tracking-wide">Type</dt>
                                    <dd class="mt-1 text-sm text-green-900 capitalize">{{ issue.resolution.resolution_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-green-700 uppercase tracking-wide">Resolved By</dt>
                                    <dd class="mt-1 text-sm text-green-900">{{ issue.resolution.resolved_by?.name }}</dd>
                                </div>
                            </div>
                        </dl>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="rounded-xl border border-border bg-card">
                        <div class="border-b border-border px-5 py-4">
                            <h2 class="flex items-center gap-2 font-medium text-foreground">
                                <Clock class="size-4 text-muted-foreground" />
                                Activity Timeline
                            </h2>
                        </div>

                        <div v-if="activities === undefined" class="p-5">
                            <div class="space-y-4">
                                <Skeleton v-for="n in 3" :key="n" class="h-12 w-full" />
                            </div>
                        </div>

                        <div v-else-if="activities.length === 0" class="px-5 py-8 text-center text-muted-foreground">
                            No activity recorded yet.
                        </div>

                        <div v-else class="divide-y divide-border">
                            <div
                                v-for="activity in activities"
                                :key="activity.id"
                                :class="activity.is_internal ? 'bg-amber-50/50' : ''"
                                class="flex gap-3 px-5 py-4"
                            >
                                <span class="mt-0.5 text-base">{{ activityIcon(activity.action_type) }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-medium text-foreground">{{ activity.user?.name ?? 'System' }}</span>
                                        <span class="text-xs text-muted-foreground capitalize">
                                            {{ activity.action_type.replace('_', ' ') }}
                                        </span>
                                        <Badge v-if="activity.is_internal" variant="outline" class="text-xs text-amber-700 border-amber-300">
                                            Internal
                                        </Badge>
                                        <span class="text-xs text-muted-foreground/70 ml-auto">{{ activity.created_at }}</span>
                                    </div>
                                    <div v-if="activity.previous_status && activity.new_status" class="mt-0.5 text-xs text-muted-foreground">
                                        {{ activity.previous_status.replace('_', ' ') }} → {{ activity.new_status.replace('_', ' ') }}
                                    </div>
                                    <p v-if="activity.comment" class="mt-1 text-sm text-foreground">{{ activity.comment }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Add Comment -->
                        <div class="border-t border-border px-5 py-4">
                            <form class="flex flex-col gap-3" @submit.prevent="submitComment">
                                <textarea
                                    v-model="commentForm.comment"
                                    placeholder="Add a comment…"
                                    rows="3"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                />
                                <p v-if="commentForm.errors.comment" class="text-xs text-destructive">
                                    {{ commentForm.errors.comment }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <input v-model="commentForm.is_internal" type="checkbox" class="h-4 w-4 rounded border-input" />
                                        Internal note
                                    </label>
                                    <Button type="submit" size="sm" :disabled="commentForm.processing || !commentForm.comment">
                                        <MessageSquare class="size-4" />
                                        Add Comment
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="rounded-xl border border-border bg-card">
                        <div class="border-b border-border px-5 py-4">
                            <h2 class="flex items-center gap-2 font-medium text-foreground">
                                <Paperclip class="size-4 text-muted-foreground" />
                                Attachments
                            </h2>
                        </div>

                        <div v-if="attachments === undefined" class="p-5">
                            <Skeleton class="h-10 w-full" />
                        </div>

                        <div v-else-if="attachments.length === 0" class="px-5 py-6 text-center text-sm text-muted-foreground">
                            No attachments.
                        </div>

                        <ul v-else class="divide-y divide-border">
                            <li v-for="att in attachments" :key="att.id" class="flex items-center justify-between px-5 py-3 text-sm">
                                <div>
                                    <span class="font-medium text-foreground">{{ att.original_name }}</span>
                                    <span class="ml-2 text-xs text-muted-foreground">{{ formatBytes(att.size_bytes) }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-muted-foreground">{{ att.uploaded_by?.name }}</span>
                                    <a
                                        :href="`/attachments/${att.id}/download`"
                                        target="_blank"
                                        class="text-[#2E5FA3] hover:underline text-xs"
                                    >
                                        Download
                                    </a>
                                </div>
                            </li>
                        </ul>

                        <!-- Upload -->
                        <div class="border-t border-border px-5 py-4">
                            <form
                                method="POST"
                                :action="`/issues/${issue.id}/attachments`"
                                enctype="multipart/form-data"
                                class="flex items-center gap-3"
                            >
                                <input type="hidden" name="_token" :value="(document as any).__inertia_csrf_token ?? ''" />
                                <input
                                    type="file"
                                    name="file"
                                    accept=".jpg,.jpeg,.png,.pdf,.mp4,.log"
                                    class="text-sm text-muted-foreground file:mr-3 file:rounded file:border file:border-input file:bg-background file:px-3 file:py-1 file:text-sm"
                                />
                                <Button type="submit" variant="outline" size="sm">Upload</Button>
                            </form>
                            <p class="mt-1 text-xs text-muted-foreground">Max 10 MB · jpg, jpeg, png, pdf, mp4, log</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="flex flex-col gap-4">
                    <div class="rounded-xl border border-border bg-card p-5">
                        <h2 class="mb-4 text-sm font-medium text-muted-foreground uppercase tracking-wide">Timeline</h2>
                        <dl class="flex flex-col gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-muted-foreground">Created</dt>
                                <dd class="mt-0.5 text-foreground">{{ issue.created_at }}</dd>
                            </div>
                            <div v-if="issue.acknowledged_at">
                                <dt class="text-xs text-muted-foreground">Acknowledged</dt>
                                <dd class="mt-0.5 text-foreground">{{ issue.acknowledged_at }}</dd>
                            </div>
                            <div v-if="issue.resolved_at">
                                <dt class="text-xs text-muted-foreground">Resolved</dt>
                                <dd class="mt-0.5 text-foreground">{{ issue.resolved_at }}</dd>
                            </div>
                            <div v-if="issue.closed_at">
                                <dt class="text-xs text-muted-foreground">Closed</dt>
                                <dd class="mt-0.5 text-foreground">{{ issue.closed_at }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div v-if="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-card p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-foreground">Update Status</h3>
                <form class="flex flex-col gap-4" @submit.prevent="submitStatus">
                    <div class="flex flex-col gap-1.5">
                        <Label>New Status</Label>
                        <Select
                            :model-value="statusForm.status || undefined"
                            @update:model-value="statusForm.status = $event ?? ''"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in allowed_transitions" :key="t.value" :value="t.value">
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="statusForm.errors.status" class="text-xs text-destructive">{{ statusForm.errors.status }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label>Comment (optional)</Label>
                        <textarea
                            v-model="statusForm.comment"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Reason for status change…"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="showStatusModal = false">Cancel</Button>
                        <Button type="submit" :disabled="statusForm.processing || !statusForm.status">
                            Update
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Escalate Modal -->
        <div v-if="showEscalateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-card p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-foreground">Escalate Issue</h3>
                <form class="flex flex-col gap-4" @submit.prevent="submitEscalate">
                    <div class="flex flex-col gap-1.5">
                        <Label>Escalation Reason <span class="text-destructive">*</span></Label>
                        <textarea
                            v-model="escalateForm.reason"
                            rows="4"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Explain why this issue needs escalation…"
                        />
                        <p v-if="escalateForm.errors.reason" class="text-xs text-destructive">{{ escalateForm.errors.reason }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="showEscalateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="escalateForm.processing || !escalateForm.reason"
                            class="bg-orange-600 hover:bg-orange-700 text-white">
                            Escalate
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resolve Modal -->
        <div v-if="showResolveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-card p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-foreground">Resolve Issue</h3>
                <form class="flex flex-col gap-4" @submit.prevent="submitResolve">
                    <div class="flex flex-col gap-1.5">
                        <Label>Root Cause <span class="text-destructive">*</span></Label>
                        <textarea
                            v-model="resolveForm.root_cause"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Describe the root cause…"
                        />
                        <p v-if="resolveForm.errors.root_cause" class="text-xs text-destructive">{{ resolveForm.errors.root_cause }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label>Resolution Type <span class="text-destructive">*</span></Label>
                        <Select
                            :model-value="resolveForm.resolution_type || undefined"
                            @update:model-value="resolveForm.resolution_type = $event ?? ''"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="r in resolution_types" :key="r.value" :value="r.value">
                                    {{ r.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="resolveForm.errors.resolution_type" class="text-xs text-destructive">{{ resolveForm.errors.resolution_type }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="showResolveModal = false">Cancel</Button>
                        <Button
                            type="submit"
                            :disabled="resolveForm.processing || !resolveForm.root_cause || !resolveForm.resolution_type"
                            class="bg-green-700 hover:bg-green-800 text-white"
                        >
                            Mark Resolved
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
