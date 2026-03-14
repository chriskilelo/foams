<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BellOff, Check, CheckCheck, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import NotificationController from '@/actions/App/Http/Controllers/Notifications/NotificationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type Notification = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    notifications: Paginated<Notification>;
    unreadCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: NotificationController.index.url() },
];

const hasUnread = computed(() => props.unreadCount > 0);

function shortType(type: string): string {
    return type.split('\\').pop() ?? type;
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Nairobi',
    });
}

function getTitle(notification: Notification): string {
    const d = notification.data;
    if (typeof d.title === 'string') return d.title;
    if (typeof d.subject === 'string') return d.subject;
    if (typeof d.message === 'string') return d.message;
    return shortType(notification.type).replace(/([A-Z])/g, ' $1').trim();
}

function getMessage(notification: Notification): string {
    const d = notification.data;
    if (typeof d.body === 'string') return d.body;
    if (typeof d.description === 'string') return d.description;
    if (typeof d.details === 'string') return d.details;
    return '';
}

function markAsRead(notification: Notification) {
    router.patch(
        `/notifications/${notification.id}/mark-read`,
        {},
        { preserveScroll: true },
    );
}

function markAllAsRead() {
    router.post(
        NotificationController.markAllAsRead.url(),
        {},
        { preserveScroll: true },
    );
}

function deleteNotification(notification: Notification) {
    router.delete(`/notifications/${notification.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <BellOff class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-semibold text-foreground">Notifications</h1>
                    <Badge v-if="unreadCount > 0" variant="destructive">
                        {{ unreadCount }} unread
                    </Badge>
                    <Badge v-else variant="secondary">{{ notifications.total }}</Badge>
                </div>

                <Button
                    v-if="hasUnread"
                    variant="outline"
                    size="sm"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="size-4" />
                    Mark all as read
                </Button>
            </div>

            <!-- List -->
            <div class="overflow-hidden rounded-xl border border-border bg-card">
                <div
                    v-for="(notification, i) in notifications.data"
                    :key="notification.id"
                    :class="[
                        i < notifications.data.length - 1 ? 'border-b border-border' : '',
                        notification.read_at ? 'bg-card' : 'bg-[#EEF4FB]',
                    ]"
                    class="flex items-start gap-4 px-5 py-4"
                >
                    <!-- Unread dot -->
                    <div class="mt-1.5 flex-shrink-0">
                        <span
                            v-if="!notification.read_at"
                            class="block size-2 rounded-full bg-[#2E5FA3]"
                        />
                        <span v-else class="block size-2 rounded-full bg-transparent" />
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-foreground">
                            {{ getTitle(notification) }}
                        </p>
                        <p v-if="getMessage(notification)" class="mt-0.5 line-clamp-2 text-sm text-muted-foreground">
                            {{ getMessage(notification) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatDateTime(notification.created_at) }}
                            <span class="ml-2 rounded bg-muted px-1.5 py-0.5 font-mono text-[10px]">
                                {{ shortType(notification.type) }}
                            </span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-shrink-0 items-center gap-1">
                        <Button
                            v-if="!notification.read_at"
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0 text-[#2E5FA3]"
                            title="Mark as read"
                            @click="markAsRead(notification)"
                        >
                            <Check class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-muted-foreground hover:text-destructive h-7 w-7 p-0"
                            title="Delete"
                            @click="deleteNotification(notification)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="notifications.data.length === 0" class="flex flex-col items-center gap-2 px-4 py-12 text-center">
                    <BellOff class="text-muted-foreground size-8" />
                    <p class="text-muted-foreground">No notifications yet.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="notifications.last_page > 1"
                class="flex items-center justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    <template v-if="notifications.from">
                        Showing {{ notifications.from }}–{{ notifications.to }} of {{ notifications.total }}
                    </template>
                </p>
                <div class="flex gap-2">
                    <Button v-if="notifications.prev_page_url" as-child variant="outline" size="sm">
                        <a :href="notifications.prev_page_url">Previous</a>
                    </Button>
                    <Button v-if="notifications.next_page_url" as-child variant="outline" size="sm">
                        <a :href="notifications.next_page_url">Next</a>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
