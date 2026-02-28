<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, PowerOff, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type Role = { id: number; name: string };
type Region = { id: number; name: string } | null;

type UserRow = {
    id: number;
    name: string;
    username: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    roles: Role[];
    region: Region;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{ users: Paginated<UserRow> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: UserController.index.url() },
];

const search = ref('');

const filtered = computed(() =>
    props.users.data.filter(
        (u) =>
            u.name.toLowerCase().includes(search.value.toLowerCase()) ||
            u.email.toLowerCase().includes(search.value.toLowerCase()) ||
            u.username.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

function deactivateUser(user: UserRow) {
    if (!window.confirm(`Deactivate "${user.name}"? Their active sessions will be terminated.`)) {
        return;
    }
    router.patch(UserController.deactivate.url(user.id));
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Users class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-semibold text-foreground">Users</h1>
                    <Badge variant="secondary">{{ users.total }}</Badge>
                </div>
                <Button as-child size="sm">
                    <Link :href="UserController.create.url()">
                        <Plus class="size-4" />
                        New User
                    </Link>
                </Button>
            </div>

            <Input
                v-model="search"
                placeholder="Filter by name, email or username…"
                class="max-w-sm"
            />

            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-[#D6E4F7]/40">
                            <th class="px-4 py-3 text-left font-medium text-foreground">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Username</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Role</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Region</th>
                            <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(user, i) in filtered"
                            :key="user.id"
                            :class="i % 2 === 1 ? 'bg-[#EEF4FB]' : 'bg-card'"
                            class="border-b border-border last:border-0"
                        >
                            <td class="px-4 py-3 font-medium text-foreground">{{ user.name }}</td>
                            <td class="px-4 py-3 font-mono text-muted-foreground">{{ user.username }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="outline" class="text-xs">
                                    {{ user.roles[0]?.name ?? '—' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.region?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Button as-child variant="ghost" size="icon-sm">
                                        <Link :href="UserController.edit.url(user.id)">
                                            <Pencil class="size-4" />
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="user.is_active"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="deactivateUser(user)"
                                    >
                                        <PowerOff class="size-4" />
                                        <span class="sr-only">Deactivate</span>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
