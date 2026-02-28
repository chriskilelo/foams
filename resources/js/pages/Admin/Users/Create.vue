<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import { Button } from '@/components/ui/button';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type Region = { id: number; name: string };

const props = defineProps<{
    regions: Region[];
    roles: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: UserController.index.url() },
    { title: 'New User', href: UserController.create.url() },
];

const form = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role: '',
    region_id: '' as string | number,
});

const requiresRegion = computed(() =>
    ['ricto', 'icto', 'aicto'].includes(form.role),
);

function submit() {
    form.post(UserController.store.url());
}
</script>

<template>
    <Head title="New User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <Heading title="New User" description="Create a new FOAMS system user and assign their role." />

            <form class="mt-6 space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Full Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Jane Mwangi"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="username">Username</Label>
                        <Input
                            id="username"
                            v-model="form.username"
                            placeholder="e.g. jane_mwangi"
                            required
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.username" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            placeholder="e.g. 0712345678"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email Address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="jane@ict.go.ke"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Minimum 12 characters"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <Select v-model="form.role">
                        <SelectTrigger id="role">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in roles" :key="role" :value="role">
                                {{ role.toUpperCase() }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role" />
                </div>

                <div v-if="requiresRegion" class="grid gap-2">
                    <Label for="region_id">Region</Label>
                    <Select v-model="form.region_id">
                        <SelectTrigger id="region_id">
                            <SelectValue placeholder="Select a region" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.region_id" />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button type="submit" :disabled="form.processing">Create User</Button>
                    <Button type="button" variant="ghost" as="a" :href="UserController.index.url()">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
