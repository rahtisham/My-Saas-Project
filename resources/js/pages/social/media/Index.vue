<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Image, Plus, Trash2, Upload, FileVideo } from '@lucide/vue';
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
import type { Team } from '@/types';

type MediaItem = {
    id: number;
    fileName: string;
    mimeType: string;
    type: string;
    url: string;
    createdAt: string;
};

type Props = {
    media: MediaItem[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const confirmingDelete = ref<number | null>(null);

const handleDelete = (item: MediaItem) => {
    if (confirmingDelete.value === item.id) {
        router.delete(
            `/${teamSlug.value}/social/media/${item.id}`,
            {
                preserveScroll: true,
                onFinish: () => (confirmingDelete.value = null),
            },
        );
    } else {
        confirmingDelete.value = item.id;
    }
};

const images = computed(() => props.media.filter((m) => m.type === 'image'));
const videos = computed(() => props.media.filter((m) => m.type === 'video'));

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Media Library', href: `/${props.currentTeam.slug}/social/media` },
        ],
    }),
});
</script>

<template>
    <Head title="Media Library" />

    <div class="flex flex-col space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Media Library</h2>
                <p class="text-sm text-muted-foreground">
                    Upload and manage images and videos for your posts
                </p>
            </div>
            <Button as-child>
                <Link :href="`/${teamSlug}/social/media/create`">
                    <Upload class="mr-2 h-4 w-4" />
                    Upload Media
                </Link>
            </Button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Images</p>
                            <p class="text-2xl font-bold">{{ images.length }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                            <Image class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Videos</p>
                            <p class="text-2xl font-bold">{{ videos.length }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/20">
                            <Image class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Media Grid -->
        <Card>
            <CardHeader>
                <CardTitle>All Media</CardTitle>
                <CardDescription>{{ media.length }} file{{ media.length !== 1 ? 's' : '' }} uploaded</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="media.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Image class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-1 font-medium">No media yet</h3>
                    <p class="mb-6 text-sm text-muted-foreground">
                        Upload images and videos to use in your posts.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="`/${teamSlug}/social/media/create`">
                            <Upload class="mr-2 h-4 w-4" />
                            Upload Media
                        </Link>
                    </Button>
                </div>
                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <div
                        v-for="item in media"
                        :key="item.id"
                        class="group relative overflow-hidden rounded-lg border bg-muted ring-1 ring-border/50 hover:ring-primary/50 transition-all cursor-pointer"
                    >
                        <div class="aspect-square">
                            <img
                                v-if="item.type === 'image'"
                                :src="item.url"
                                :alt="item.fileName"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full items-center justify-center bg-muted/80">
                                <FileVideo class="h-8 w-8 text-muted-foreground" />
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute inset-x-0 bottom-0 p-3">
                                <p class="truncate text-xs font-medium text-white">{{ item.fileName }}</p>
                            </div>
                            <div class="absolute right-2 top-2 flex gap-1">
                                <Badge variant="secondary" class="text-[10px] bg-white/20 text-white border-0 backdrop-blur-sm">
                                    {{ item.type }}
                                </Badge>
                                <Button
                                    v-if="confirmingDelete !== item.id"
                                    variant="destructive"
                                    size="sm"
                                    class="h-7 w-7 p-0"
                                    @click.stop="handleDelete(item)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    v-else
                                    variant="destructive"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click.stop="handleDelete(item)"
                                >
                                    Confirm
                                </Button>
                            </div>
                        </div>
                        <div class="px-2.5 py-2">
                            <p class="truncate text-xs text-muted-foreground">{{ item.fileName }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
