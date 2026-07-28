<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Send, Plus, Pencil, Trash2, FileVideo, LoaderCircle } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Team } from '@/types';

type PostMedia = {
    id: number;
    fileName: string;
    type: string;
    url: string;
};

type SocialAccount = {
    id: number;
    name: string;
    platform: string;
};

type Post = {
    id: number;
    caption: string | null;
    platform: string;
    status: string;
    visibility: string;
    scheduledAt: string | null;
    publishedAt: string | null;
    platformPostId: string | null;
    failureReason: string | null;
    retryCount: number;
    socialAccount: SocialAccount;
    media: PostMedia[];
    createdAt: string;
};

type Props = {
    posts: Post[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const confirmingDelete = ref<number | null>(null);
const publishingPostId = ref<number | null>(null);

const handlePublish = (post: Post) => {
    publishingPostId.value = post.id;
    router.post(
        `/${teamSlug.value}/social/posts/${post.id}/publish`,
        {
            preserveScroll: true,
            onFinish: () => {
                publishingPostId.value = null;
            },
        },
    );
};

const handleDelete = (post: Post) => {
    if (confirmingDelete.value === post.id) {
        router.delete(
            `/${teamSlug.value}/social/posts/${post.id}`,
            {
                preserveScroll: true,
                onFinish: () => (confirmingDelete.value = null),
            },
        );
    } else {
        confirmingDelete.value = post.id;
    }
};

const statusColor = (status: string) => {
    switch (status) {
        case 'published':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'scheduled':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'publishing':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const platformIcon = (platform: string) => {
    return platform === 'facebook' ? 'f' : 'ig';
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Posts', href: `/${props.currentTeam.slug}/social/posts` },
        ],
    }),
});
</script>

<template>
    <Head title="Posts" />

    <div class="flex flex-col space-y-6 p-6">
        <!-- Publishing progress banner -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="publishingPostId !== null"
                class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <LoaderCircle class="h-4 w-4 animate-spin" />
                <span class="font-medium">Publishing to Facebook...</span>
                <span class="text-blue-500">This may take a moment while we upload your content.</span>
            </div>
        </Transition>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Posts</h2>
                <p class="text-sm text-muted-foreground">
                    Manage your social media posts
                </p>
            </div>
            <Button as-child>
                <Link :href="`/${teamSlug}/social/posts/create`">
                    <Plus class="mr-2 h-4 w-4" />
                    New Post
                </Link>
            </Button>
        </div>

        <!-- Posts Table -->
        <Card>
            <CardHeader>
                <CardTitle>All Posts</CardTitle>
                <CardDescription>{{ posts.length }} post{{ posts.length !== 1 ? 's' : '' }}</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <!-- Empty state -->
                <div
                    v-if="posts.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Send class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-1 font-medium">No posts yet</h3>
                    <p class="mb-6 text-sm text-muted-foreground">
                        Create your first post to get started.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="`/${teamSlug}/social/posts/create`">
                            <Plus class="mr-2 h-4 w-4" />
                            New Post
                        </Link>
                    </Button>
                </div>

                <!-- Table -->
                <template v-else>
                    <div class="grid grid-cols-12 gap-4 border-b bg-muted/40 px-6 py-3 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <div class="col-span-4">Content</div>
                        <div class="col-span-2">Platform</div>
                        <div class="col-span-2">Account</div>
                        <div class="col-span-1">Status</div>
                        <div class="col-span-2">Date</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div
                        v-for="post in posts"
                        :key="post.id"
                        class="grid grid-cols-12 items-center gap-4 border-b px-6 py-4 last:border-0 hover:bg-muted/30 transition-colors"
                    >
                        <!-- Content + Media Thumbnails -->
                        <div class="col-span-4 min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ post.caption || 'No caption' }}
                            </p>
                            <div v-if="post.media.length > 0" class="flex items-center gap-1.5 mt-1.5">
                                <template v-for="(item, idx) in post.media.slice(0, 4)" :key="item.id">
                                    <img
                                        v-if="item.type === 'image'"
                                        :src="item.url"
                                        :alt="item.fileName"
                                        class="h-8 w-8 rounded-md object-cover ring-1 ring-border"
                                    />
                                    <div v-else class="flex h-8 w-8 items-center justify-center rounded-md bg-muted ring-1 ring-border">
                                        <FileVideo class="h-3.5 w-3.5 text-muted-foreground" />
                                    </div>
                                </template>
                                <span v-if="post.media.length > 4" class="text-xs text-muted-foreground">
                                    +{{ post.media.length - 4 }}
                                </span>
                            </div>
                        </div>

                        <!-- Platform -->
                        <div class="col-span-2">
                            <Badge :variant="post.platform === 'facebook' ? 'default' : 'secondary'">
                                {{ post.platform }}
                            </Badge>
                        </div>

                        <!-- Account -->
                        <div class="col-span-2">
                            <span class="text-sm">{{ post.socialAccount.name }}</span>
                        </div>

                        <!-- Status -->
                        <div class="col-span-1">
                            <Badge :class="statusColor(post.status)" variant="outline">
                                {{ post.status }}
                            </Badge>
                        </div>

                        <!-- Date -->
                        <div class="col-span-2 text-xs text-muted-foreground">
                            <span v-if="post.publishedAt">
                                Published {{ new Date(post.publishedAt).toLocaleDateString() }}
                            </span>
                            <span v-else-if="post.scheduledAt">
                                Scheduled {{ new Date(post.scheduledAt).toLocaleDateString() }}
                            </span>
                            <span v-else>
                                Created {{ new Date(post.createdAt).toLocaleDateString() }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="col-span-1 flex items-center justify-end gap-1">
                            <template v-if="confirmingDelete === post.id">
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="handleDelete(post)"
                                >
                                    Confirm
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="confirmingDelete = null"
                                >
                                    Cancel
                                </Button>
                            </template>

                            <TooltipProvider v-else>
                                <div class="flex items-center gap-1">
                                    <Tooltip v-if="post.status === 'draft' || post.status === 'failed'">
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                                :disabled="publishingPostId === post.id"
                                                @click="handlePublish(post)"
                                            >
                                                <LoaderCircle
                                                    v-if="publishingPostId === post.id"
                                                    class="h-3.5 w-3.5 animate-spin"
                                                />
                                                <Send v-else class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ publishingPostId === post.id ? 'Publishing...' : 'Publish' }}</TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                                as-child
                                            >
                                                <Link :href="`/${teamSlug}/social/posts/${post.id}/edit`">
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit</TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                                @click="handleDelete(post)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Delete</TooltipContent>
                                    </Tooltip>
                                </div>
                            </TooltipProvider>
                        </div>
                    </div>
                </template>
            </CardContent>
        </Card>
    </div>
</template>
