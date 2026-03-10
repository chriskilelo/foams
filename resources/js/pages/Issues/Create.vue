<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type County = { id: number; name: string };
type Asset = { id: number; asset_code: string; name: string; county_id: number };
type Option = { value: string; label: string };

const props = defineProps<{
    counties: County[];
    assets: Asset[];
    severities: Option[];
    reporter_categories: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Issues', href: IssueController.index.url() },
    { title: 'New Issue', href: IssueController.create.url() },
];

const form = useForm({
    asset_id: '',
    county_id: '',
    issue_type: '',
    severity: '',
    reporter_category: '',
    reporter_name: '',
    reporter_email: '',
    reporter_phone: '',
    description: '',
    workaround_applied: false,
});

function submit() {
    form.post(IssueController.store.url());
}

function filteredAssets(countyId: string) {
    if (!countyId) return props.assets;
    return props.assets.filter((a) => String(a.county_id) === countyId);
}
</script>

<template>
    <Head title="New Issue" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-3">
                <Button as-child variant="ghost" size="icon-sm">
                    <Link :href="IssueController.index.url()">
                        <ArrowLeft class="size-4" />
                        <span class="sr-only">Back</span>
                    </Link>
                </Button>
                <h1 class="text-xl font-semibold text-foreground">Raise New Issue</h1>
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- Location -->
                <div class="rounded-xl border border-border bg-card p-5">
                    <h2 class="mb-4 text-sm font-semibold text-[#1F3864] uppercase tracking-wide">
                        Location
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label for="county_id">County <span class="text-destructive">*</span></Label>
                            <Select
                                :model-value="form.county_id || undefined"
                                @update:model-value="
                                    form.county_id = $event ?? '';
                                    form.asset_id = '';
                                "
                            >
                                <SelectTrigger id="county_id">
                                    <SelectValue placeholder="Select county" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in counties" :key="c.id" :value="String(c.id)">
                                        {{ c.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.county_id" class="text-xs text-destructive">
                                {{ form.errors.county_id }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="asset_id">Related Asset (optional)</Label>
                            <Select
                                :model-value="form.asset_id || undefined"
                                @update:model-value="form.asset_id = $event ?? ''"
                            >
                                <SelectTrigger id="asset_id">
                                    <SelectValue placeholder="Select asset" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="a in filteredAssets(form.county_id)"
                                        :key="a.id"
                                        :value="String(a.id)"
                                    >
                                        {{ a.asset_code }} — {{ a.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Issue Details -->
                <div class="rounded-xl border border-border bg-card p-5">
                    <h2 class="mb-4 text-sm font-semibold text-[#1F3864] uppercase tracking-wide">
                        Issue Details
                    </h2>

                    <div class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <Label for="issue_type">Issue Type <span class="text-destructive">*</span></Label>
                                <Input
                                    id="issue_type"
                                    v-model="form.issue_type"
                                    placeholder="e.g. Connectivity, Hardware Failure…"
                                />
                                <p v-if="form.errors.issue_type" class="text-xs text-destructive">
                                    {{ form.errors.issue_type }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label for="severity">Severity <span class="text-destructive">*</span></Label>
                                <Select
                                    :model-value="form.severity || undefined"
                                    @update:model-value="form.severity = $event ?? ''"
                                >
                                    <SelectTrigger id="severity">
                                        <SelectValue placeholder="Select severity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in severities" :key="s.value" :value="s.value">
                                            {{ s.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.severity" class="text-xs text-destructive">
                                    {{ form.errors.severity }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="description">Description <span class="text-destructive">*</span></Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                placeholder="Describe the issue in detail…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <p v-if="form.errors.description" class="text-xs text-destructive">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="workaround_applied"
                                v-model="form.workaround_applied"
                                type="checkbox"
                                class="h-4 w-4 rounded border-input"
                            />
                            <Label for="workaround_applied" class="font-normal">
                                A workaround has been applied
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Reporter -->
                <div class="rounded-xl border border-border bg-card p-5">
                    <h2 class="mb-4 text-sm font-semibold text-[#1F3864] uppercase tracking-wide">
                        Reporter
                    </h2>

                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <Label for="reporter_category">Reporter Category <span class="text-destructive">*</span></Label>
                            <Select
                                :model-value="form.reporter_category || undefined"
                                @update:model-value="form.reporter_category = $event ?? ''"
                            >
                                <SelectTrigger id="reporter_category">
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="r in reporter_categories"
                                        :key="r.value"
                                        :value="r.value"
                                    >
                                        {{ r.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.reporter_category" class="text-xs text-destructive">
                                {{ form.errors.reporter_category }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="flex flex-col gap-1.5">
                                <Label for="reporter_name">Name</Label>
                                <Input id="reporter_name" v-model="form.reporter_name" placeholder="Full name" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <Label for="reporter_email">Email</Label>
                                <Input id="reporter_email" v-model="form.reporter_email" type="email" placeholder="email@example.com" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <Label for="reporter_phone">Phone</Label>
                                <Input id="reporter_phone" v-model="form.reporter_phone" placeholder="+254…" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Button as-child variant="outline">
                        <Link :href="IssueController.index.url()">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing" class="bg-[#1F3864] hover:bg-[#2E5FA3]">
                        {{ form.processing ? 'Submitting…' : 'Raise Issue' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
