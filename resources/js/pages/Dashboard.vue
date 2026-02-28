<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard.url(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const primaryRole = computed(() => user.value?.roles?.[0] ?? 'Unknown');

const roleLabel: Record<string, string> = {
    admin: 'System Administrator',
    director: 'Director',
    noc: 'NOC Officer',
    ricto: 'RICTO',
    icto: 'ICTO',
    aicto: 'AICTO',
    public_servant: 'Public Servant',
    public: 'General Public',
};

const displayRole = computed(
    () => roleLabel[primaryRole.value] ?? primaryRole.value,
);

const statTiles = [
    { label: 'Total Assets', description: 'Managed ICT assets' },
    { label: 'Open Issues', description: 'Requiring attention' },
    { label: 'SLA Compliance', description: 'This month' },
    { label: 'Active Officers', description: 'Field personnel' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Welcome, {{ user?.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ displayRole }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="tile in statTiles"
                    :key="tile.label"
                    class="rounded-xl border border-border bg-card p-6"
                >
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ tile.label }}
                    </p>
                    <p class="mt-1 text-3xl font-bold text-foreground">—</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ tile.description }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
