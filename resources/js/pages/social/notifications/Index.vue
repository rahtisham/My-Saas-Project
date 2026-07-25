<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Team } from '@/types';

type Notification = {
    id: number;
    type: string;
    title: string;
    message: string;
    data: Record<string, unknown> | null;
    readAt: string | null;
    createdAt: string;
};

type Props = {
    notifications: Notification[];
    unreadCount: number;
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const markAsRead = (notification: Notification) => {
    if (notification.readAt) return;

    router.post(
        `/${teamSlug.value}/social/notifications/${notification.id}/read`,
        {},
        { preserveScroll: true },
    );
};

const markAllAsRead = () => {
    router.post(
        `/${teamSlug.value}/social/notifications/read-all`,
        {},
        { preserveScroll: true },
    );
};

const typeColor = (type: string) => {
    switch (type) {
        case 'post_published':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'post_failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'campaign_started':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'campaign_failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const typeIcon = (type: string) => {
    switch (type) {
        case 'post_published':
            return '✓';
        case 'post_failed':
            return '✕';
        case 'campaign_started':
            return '▶';
        case 'campaign_failed':
            return '✕';
        default:
            return '!';
    }
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Notifications', href: `/${props.currentTeam.slug}/social/notifications` },
        ],
    }),
});
</script>

<template>
    <Head title="Notifications" />

    <div class="flex flex-col space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Notifications</h2>
                <p class="text-sm text-muted-foreground">
                    {{ unreadCount }} unread notification{{ unreadCount !== 1 ? 's' : '' }}
                </p>
            </div>
            <Button
                v-if="unreadCount > 0"
                variant="outline"
                @click="markAllAsRead"
            >
                <CheckCheck class="mr-2 h-4 w-4" />
                Mark all as read
            </Button>
        </div>

        <!-- Notifications List -->
        <Card>
            <CardContent class="p-0">
                <div
                    v-if="notifications.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Bell class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-1 font-medium">No notifications</h3>
                    <p class="text-sm text-muted-foreground">
                        You'll be notified when posts are published or campaigns start.
                    </p>
                </div>

                <div v-else>
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        :class="[
                            'flex items-start gap-4 border-b px-6 py-4 last:border-0 transition-colors cursor-pointer',
                            notification.readAt ? 'bg-background' : 'bg-muted/30',
                        ]"
                        @click="markAsRead(notification)"
                    >
                        <div
                            :class="[
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                typeColor(notification.type),
                            ]"
                        >
                            {{ typeIcon(notification.type) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium">{{ notification.title }}</p>
                                <Badge v-if="!notification.readAt" variant="secondary" class="text-xs">
                                    New
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ notification.message }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ new Date(notification.createdAt).toLocaleString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
