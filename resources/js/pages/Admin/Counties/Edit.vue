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
type County = {
    id: number;
    name: string;
    code: string;
    region_id: number;
};

const props = defineProps<{ county: County; regions: Region[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regions', href: RegionController.index.url() },
    { title: 'Counties', href: CountyController.index.url() },
    { title: props.county.name, href: CountyController.edit.url(props.county.id) },
];

const form = useForm({
    name: props.county.name,
    code: props.county.code,
    region_id: props.county.region_id as string | number,
});

function submit() {
    form.put(CountyController.update.url(props.county.id));
}
</script>

<template>
    <Head :title="`Edit ${county.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading
                :title="`Edit ${county.name}`"
                description="Update this county's details."
            />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
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
                        :model-value="String(form.region_id)"
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
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
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
