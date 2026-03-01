<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    stats: {
        assets_total: number;
        assets_online: number;
        assets_degraded: number;
        assets_down: number;
        avg_uptime_30d: number;
        open_issues: number;
        logs_today: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard.url(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const primaryRole = computed(() => user.value?.roles?.[0] ?? '');

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

const uptimeColor = computed(() => {
    if (props.stats.avg_uptime_30d >= 95) return 'text-green-600';
    if (props.stats.avg_uptime_30d >= 80) return 'text-amber-600';
    return 'text-red-600';
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Welcome, {{ user?.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ displayRole }}
                </p>
            </div>

            <!-- Asset Status Row -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Assets -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Total Assets</p>
                    <p class="mt-1 text-3xl font-bold text-foreground">
                        {{ stats.assets_total.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Managed ICT infrastructure</p>
                </div>

                <!-- Online -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Online</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">
                        {{ stats.assets_online.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Operational assets</p>
                </div>

                <!-- Degraded -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Degraded</p>
                    <p class="mt-1 text-3xl font-bold text-amber-500">
                        {{ stats.assets_degraded.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Performance reduced</p>
                </div>

                <!-- Down -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Down</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">
                        {{ stats.assets_down.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Currently offline</p>
                </div>
            </div>

            <!-- Activity & Uptime Row -->
            <div class="grid gap-4 sm:grid-cols-3">
                <!-- 30-Day Uptime -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">30-Day Uptime</p>
                    <p class="mt-1 text-3xl font-bold" :class="uptimeColor">
                        {{ stats.avg_uptime_30d.toFixed(1) }}%
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Average across all assets
                        <span
                            v-if="stats.avg_uptime_30d < 95"
                            class="ml-1 font-medium text-amber-600"
                        >
                            · Below 95% threshold
                        </span>
                    </p>
                </div>

                <!-- Open Issues -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Open Issues</p>
                    <p
                        class="mt-1 text-3xl font-bold"
                        :class="stats.open_issues > 0 ? 'text-amber-500' : 'text-foreground'"
                    >
                        {{ stats.open_issues.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Requiring attention</p>
                </div>

                <!-- Logs Today -->
                <div class="rounded-xl border border-border bg-card p-6">
                    <p class="text-sm font-medium text-muted-foreground">Logs Today</p>
                    <p class="mt-1 text-3xl font-bold text-[#2E5FA3]">
                        {{ stats.logs_today.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Status entries submitted</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
