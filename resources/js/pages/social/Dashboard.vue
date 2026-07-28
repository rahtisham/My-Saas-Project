<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Share2,
    Image,
    FileVideo,
    Send,
    Megaphone,
    Bell,
    Users,
    ArrowRight,
    Calendar,
    CheckCircle2,
    AlertTriangle,
    Clock,
} from '@lucide/vue';
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
import { usePage } from '@inertiajs/vue3';
import type { Team } from '@/types';

type Stats = {
    totalPosts: number;
    publishedPosts: number;
    scheduledPosts: number;
    failedPosts: number;
    totalMedia: number;
    activeCampaigns: number;
    connectedAccounts: number;
};

type RecentPost = {
    id: number;
    caption: string | null;
    platform: string;
    status: string;
    socialAccount: { name: string; platform: string };
    createdAt: string;
};

type Props = {
    stats: Stats;
    recentPosts: RecentPost[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const statusColor = (status: string) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'scheduled':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
        ],
    }),
});
</script>

<template>
    <Head title="Social Media Dashboard" />

    <div class="flex flex-col space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Social Media</h2>
                <p class="text-sm text-muted-foreground">
                    Manage your Facebook and Instagram presence
                </p>
            </div>
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <Link :href="`/${teamSlug}/social/accounts`">
                        <Users class="mr-2 h-4 w-4" />
                        Accounts
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="`/${teamSlug}/social/posts/create`">
                        <Send class="mr-2 h-4 w-4" />
                        New Post
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Posts</p>
                            <p class="text-2xl font-bold">{{ stats.totalPosts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                            <Send class="h-5 w-5 text-primary" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Published</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.publishedPosts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 dark:bg-green-900/20">
                            <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Scheduled</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.scheduledPosts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/20">
                            <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Failed</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.failedPosts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/20">
                            <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <Card as-child>
                <Link :href="`/${teamSlug}/social/media`" class="block hover:bg-muted/50 transition-colors">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                <Image class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            </div>
                            Media Library
                        </CardTitle>
                        <CardDescription>{{ stats.totalMedia }} files uploaded</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-sm text-muted-foreground">
                            Upload and manage images and videos
                            <ArrowRight class="ml-auto h-4 w-4" />
                        </div>
                    </CardContent>
                </Link>
            </Card>

            <Card as-child>
                <Link :href="`/${teamSlug}/social/campaigns`" class="block hover:bg-muted/50 transition-colors">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/20">
                                <Megaphone class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            Campaigns
                        </CardTitle>
                        <CardDescription>{{ stats.activeCampaigns }} active campaigns</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-sm text-muted-foreground">
                            Create and manage marketing campaigns
                            <ArrowRight class="ml-auto h-4 w-4" />
                        </div>
                    </CardContent>
                </Link>
            </Card>

            <Card as-child>
                <Link :href="`/${teamSlug}/social/notifications`" class="block hover:bg-muted/50 transition-colors">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                                <Bell class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            Notifications
                        </CardTitle>
                        <CardDescription>Stay updated on your posts</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-sm text-muted-foreground">
                            View post and campaign notifications
                            <ArrowRight class="ml-auto h-4 w-4" />
                        </div>
                    </CardContent>
                </Link>
            </Card>
        </div>

        <!-- Recent Posts -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Recent Posts</CardTitle>
                    <CardDescription>Your latest social media posts</CardDescription>
                </div>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="`/${teamSlug}/social/posts`">
                        View all
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                <div v-if="recentPosts.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                    <Send class="mb-4 h-8 w-8 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">No posts yet. Create your first post!</p>
                </div>
                <div v-else class="space-y-2">
                    <Link
                        v-for="post in recentPosts"
                        :key="post.id"
                        :href="`/${teamSlug}/social/posts/${post.id}/edit`"
                        class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50 transition-colors"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ post.caption || 'No caption' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ post.socialAccount.name }} · {{ post.platform }}
                            </p>
                        </div>
                        <Badge :class="statusColor(post.status)" variant="outline">
                            {{ post.status }}
                        </Badge>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
