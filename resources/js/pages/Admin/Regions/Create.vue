<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Regions', href: RegionController.index.url() },
    { title: 'New Region', href: RegionController.create.url() },
];

const form = useForm({
    name: '',
    code: '',
    is_active: true,
});

function submit() {
    form.post(RegionController.store.url());
}
</script>

<template>
    <Head title="New Region" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading title="New Region" description="Create a new FOAMS administrative region." />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Nairobi"
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
                        placeholder="e.g. NBI"
                        maxlength="10"
                        required
                        autocomplete="off"
                        class="uppercase"
                    />
                    <InputError :message="form.errors.code" />
                </div>

                <div class="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        :checked="form.is_active"
                        @update:checked="form.is_active = $event"
                    />
                    <Label for="is_active" class="cursor-pointer font-normal">
                        Active
                    </Label>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button type="submit" :disabled="form.processing">Create Region</Button>
                    <Button
                        type="button"
                        variant="ghost"
                        :href="RegionController.index.url()"
                        as="a"
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
