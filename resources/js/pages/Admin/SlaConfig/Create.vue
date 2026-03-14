<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SlaConfigurationController from '@/actions/App/Http/Controllers/Admin/SlaConfigurationController';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

defineProps<{ severities: string[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SLA Configuration', href: SlaConfigurationController.index.url() },
    { title: 'New Config', href: SlaConfigurationController.create.url() },
];

const form = useForm({
    severity: '',
    acknowledge_within_hrs: 1,
    resolve_within_hrs: 1,
    effective_from: new Date().toISOString().slice(0, 16),
});

function submit() {
    form.post(SlaConfigurationController.store.url());
}
</script>

<template>
    <Head title="New SLA Config" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading
                title="New SLA Configuration"
                description="Add a new SLA target for a severity level. The effective date determines when it takes effect."
            />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="severity">Severity</Label>
                    <select
                        id="severity"
                        v-model="form.severity"
                        required
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50 capitalize"
                    >
                        <option value="" disabled>Select severity…</option>
                        <option v-for="s in severities" :key="s" :value="s" class="capitalize">
                            {{ s }}
                        </option>
                    </select>
                    <InputError :message="form.errors.severity" />
                </div>

                <div class="grid gap-2">
                    <Label for="acknowledge_within_hrs">Acknowledge Within (hours)</Label>
                    <Input
                        id="acknowledge_within_hrs"
                        v-model.number="form.acknowledge_within_hrs"
                        type="number"
                        min="1"
                        required
                    />
                    <InputError :message="form.errors.acknowledge_within_hrs" />
                </div>

                <div class="grid gap-2">
                    <Label for="resolve_within_hrs">Resolve Within (hours)</Label>
                    <Input
                        id="resolve_within_hrs"
                        v-model.number="form.resolve_within_hrs"
                        type="number"
                        min="1"
                        required
                    />
                    <InputError :message="form.errors.resolve_within_hrs" />
                </div>

                <div class="grid gap-2">
                    <Label for="effective_from">Effective From</Label>
                    <Input
                        id="effective_from"
                        v-model="form.effective_from"
                        type="datetime-local"
                        required
                    />
                    <InputError :message="form.errors.effective_from" />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button type="submit" :disabled="form.processing">Create Config</Button>
                    <Button type="button" variant="ghost" as="a" :href="SlaConfigurationController.index.url()">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
