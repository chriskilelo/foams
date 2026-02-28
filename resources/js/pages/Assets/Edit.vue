<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
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

type County = { id: number; name: string };
type TypeOption = { value: string; label: string };
type StatusOption = { value: string; label: string };
type Asset = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    county_id: number;
    location_name: string;
    latitude: string | null;
    longitude: string | null;
    assigned_to: number | null;
    installation_date: string | null;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
    status: string;
};

const props = defineProps<{
    asset: Asset;
    counties: County[];
    types: TypeOption[];
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Assets', href: AssetController.index.url() },
    { title: props.asset.asset_code, href: AssetController.show.url(props.asset.id) },
    { title: 'Edit', href: AssetController.edit.url(props.asset.id) },
];

const form = useForm({
    name: props.asset.name,
    type: props.asset.type,
    county_id: props.asset.county_id as string | number,
    location_name: props.asset.location_name,
    latitude: props.asset.latitude ?? '',
    longitude: props.asset.longitude ?? '',
    assigned_to: props.asset.assigned_to ?? ('' as string | number),
    installation_date: props.asset.installation_date ?? '',
    manufacturer: props.asset.manufacturer ?? '',
    model: props.asset.model ?? '',
    serial_number: props.asset.serial_number ?? '',
    status: props.asset.status,
});

function submit() {
    form
        .transform((data) => ({
            ...data,
            county_id: data.county_id || undefined,
            assigned_to: data.assigned_to || undefined,
            latitude: data.latitude || undefined,
            longitude: data.longitude || undefined,
            installation_date: data.installation_date || undefined,
            manufacturer: data.manufacturer || undefined,
            model: data.model || undefined,
            serial_number: data.serial_number || undefined,
        }))
        .put(AssetController.update.url(props.asset.id));
}
</script>

<template>
    <Head :title="`Edit ${asset.asset_code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <Heading
                :title="`Edit ${asset.asset_code}`"
                description="Update asset details. The asset code cannot be changed after creation."
            />

            <!-- Asset code (read-only) -->
            <div class="mt-4 flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-4 py-3">
                <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Asset Code</span>
                <span class="font-mono font-semibold text-foreground">{{ asset.asset_code }}</span>
            </div>

            <form class="mt-5 space-y-5" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="name">Asset Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="type">Asset Type <span class="text-destructive">*</span></Label>
                        <Select
                            :model-value="form.type || undefined"
                            @update:model-value="form.type = $event ?? ''"
                        >
                            <SelectTrigger id="type">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="t in types"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="county_id">County <span class="text-destructive">*</span></Label>
                        <Select
                            :model-value="form.county_id ? String(form.county_id) : undefined"
                            @update:model-value="form.county_id = Number($event)"
                        >
                            <SelectTrigger id="county_id">
                                <SelectValue placeholder="Select county" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="county in counties"
                                    :key="county.id"
                                    :value="String(county.id)"
                                >
                                    {{ county.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.county_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status <span class="text-destructive">*</span></Label>
                        <Select
                            :model-value="form.status || undefined"
                            @update:model-value="form.status = $event ?? ''"
                        >
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="s in statuses"
                                    :key="s.value"
                                    :value="s.value"
                                >
                                    {{ s.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="location_name">Location Name <span class="text-destructive">*</span></Label>
                    <Input
                        id="location_name"
                        v-model="form.location_name"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.location_name" />
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="grid gap-2">
                        <Label for="latitude">Latitude</Label>
                        <Input
                            id="latitude"
                            v-model="form.latitude"
                            type="number"
                            step="0.0000001"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.latitude" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="longitude">Longitude</Label>
                        <Input
                            id="longitude"
                            v-model="form.longitude"
                            type="number"
                            step="0.0000001"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.longitude" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="manufacturer">Manufacturer</Label>
                        <Input
                            id="manufacturer"
                            v-model="form.manufacturer"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.manufacturer" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="model">Model</Label>
                        <Input
                            id="model"
                            v-model="form.model"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.model" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="serial_number">Serial Number</Label>
                        <Input
                            id="serial_number"
                            v-model="form.serial_number"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.serial_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="installation_date">Installation Date</Label>
                        <Input
                            id="installation_date"
                            v-model="form.installation_date"
                            type="date"
                        />
                        <InputError :message="form.errors.installation_date" />
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
                    <Button
                        type="button"
                        variant="ghost"
                        :href="AssetController.show.url(asset.id)"
                        as="a"
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
