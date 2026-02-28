<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CountyController from '@/actions/App/Http/Controllers/Admin/CountyController';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

type Region = { id: number; name: string };

defineProps<{ regions: Region[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regions', href: RegionController.index.url() },
    { title: 'Counties', href: CountyController.index.url() },
    { title: 'New County', href: CountyController.create.url() },
];

const form = useForm({
    name: '',
    code: '',
    region_id: '' as string | number,
});

function submit() {
    form.post(CountyController.store.url());
}
</script>

<template>
    <Head title="New County" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading title="New County" description="Add a county and assign it to a region." />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Nairobi City"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="code">Code</Label>
                    <Input
                        id="code"
                        v-model="form.code"
                        placeholder="e.g. NBI1"
                        maxlength="10"
                        required
                        autocomplete="off"
                        class="uppercase"
                    />
                    <InputError :message="form.errors.code" />
                </div>

                <div class="grid gap-2">
                    <Label for="region_id">Region</Label>
                    <Select
                        :model-value="form.region_id ? String(form.region_id) : undefined"
                        @update:model-value="form.region_id = Number($event)"
                    >
                        <SelectTrigger id="region_id">
                            <SelectValue placeholder="Select a region" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="region in regions"
                                :key="region.id"
                                :value="String(region.id)"
                            >
                                {{ region.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.region_id" />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button type="submit" :disabled="form.processing">Create County</Button>
                    <Button
                        type="button"
                        variant="ghost"
                        :href="CountyController.index.url()"
                        as="a"
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
