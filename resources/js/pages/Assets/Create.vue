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

defineProps<{
    counties: County[];
    types: TypeOption[];
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Assets', href: AssetController.index.url() },
    { title: 'New Asset', href: AssetController.create.url() },
];

const form = useForm({
    name: '',
    type: '',
    county_id: '' as string | number,
    location_name: '',
    latitude: '',
    longitude: '',
    assigned_to: '' as string | number,
    installation_date: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    status: 'operational',
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
        .post(AssetController.store.url());
}
</script>

<template>
    <Head title="New Asset" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <Heading
                title="New Asset"
                description="Register a new ICT asset. The asset code is generated automatically."
            />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <!-- Core fields -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="name">Asset Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g. Mombasa Town Hotspot"
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
                        placeholder="e.g. Moi Avenue, Mombasa CBD"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.location_name" />
                </div>

                <!-- GPS Coordinates -->
                <div class="grid grid-cols-2 gap-5">
                    <div class="grid gap-2">
                        <Label for="latitude">Latitude</Label>
                        <Input
                            id="latitude"
                            v-model="form.latitude"
                            type="number"
                            step="0.0000001"
                            placeholder="-4.0435"
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
                            placeholder="39.6682"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.longitude" />
                    </div>
                </div>

                <!-- Optional fields -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="manufacturer">Manufacturer</Label>
                        <Input
                            id="manufacturer"
                            v-model="form.manufacturer"
                            placeholder="e.g. Cisco"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.manufacturer" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="model">Model</Label>
                        <Input
                            id="model"
                            v-model="form.model"
                            placeholder="e.g. Aironet 2800"
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
                            placeholder="e.g. SN-12345678"
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
                    <Button type="submit" :disabled="form.processing">Register Asset</Button>
                    <Button type="button" variant="ghost" :href="AssetController.index.url()" as="a">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
