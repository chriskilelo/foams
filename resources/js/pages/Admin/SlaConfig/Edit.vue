<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SlaConfigurationController from '@/actions/App/Http/Controllers/Admin/SlaConfigurationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

type SlaConfiguration = {
    id: number;
    severity: 'critical' | 'high' | 'medium' | 'low';
    acknowledge_within_hrs: number;
    resolve_within_hrs: number;
    effective_from: string;
};

const props = defineProps<{ configuration: SlaConfiguration }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SLA Configuration', href: SlaConfigurationController.index.url() },
    { title: `Edit ${props.configuration.severity}`, href: SlaConfigurationController.edit.url(props.configuration.id) },
];

const severityVariant: Record<string, 'destructive' | 'warning' | 'secondary' | 'default'> = {
    critical: 'destructive',
    high: 'warning',
    medium: 'secondary',
    low: 'default',
};

function toDateTimeLocal(value: string): string {
    return new Date(value).toISOString().slice(0, 16);
}

const form = useForm({
    acknowledge_within_hrs: props.configuration.acknowledge_within_hrs,
    resolve_within_hrs: props.configuration.resolve_within_hrs,
    effective_from: toDateTimeLocal(props.configuration.effective_from),
});

function submit() {
    form.put(SlaConfigurationController.update.url(props.configuration.id));
}
</script>

<template>
    <Head :title="`Edit SLA – ${configuration.severity}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading
                :title="`Edit SLA – ${configuration.severity}`"
                description="Update the SLA targets for this severity level."
            />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Severity</Label>
                    <div>
                        <Badge :variant="severityVariant[configuration.severity]" class="capitalize">
                            {{ configuration.severity }}
                        </Badge>
                    </div>
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
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
                    <Button type="button" variant="ghost" as="a" :href="SlaConfigurationController.index.url()">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
