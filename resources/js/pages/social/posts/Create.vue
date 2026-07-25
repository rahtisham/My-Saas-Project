<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Team } from '@/types';

type Account = {
    id: number;
    platform: string;
    name: string;
};

type MediaItem = {
    id: number;
    fileName: string;
    type: string;
    url: string;
};

type Props = {
    accounts: Account[];
    media: MediaItem[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const form = useForm({
    social_account_id: '',
    caption: '',
    platform: 'facebook',
    visibility: 'public',
    status: 'draft',
    scheduled_at: '',
    media_ids: [] as number[],
});

const submit = () => {
    form.post(`/${teamSlug.value}/social/posts`, {
        preserveScroll: true,
    });
};

const toggleMedia = (mediaId: number) => {
    const index = form.media_ids.indexOf(mediaId);
    if (index === -1) {
        form.media_ids.push(mediaId);
    } else {
        form.media_ids.splice(index, 1);
    }
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Posts', href: `/${props.currentTeam.slug}/social/posts` },
            { title: 'Create', href: `/${props.currentTeam.slug}/social/posts/create` },
        ],
    }),
});
</script>

<template>
    <Head title="Create Post" />

    <div class="flex flex-col space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Create Post</h2>
            <p class="text-sm text-muted-foreground">
                Create a new post for Facebook or Instagram
            </p>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Post Content</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="caption">Caption</Label>
                                <Textarea
                                    id="caption"
                                    v-model="form.caption"
                                    placeholder="Write your post caption..."
                                    rows="4"
                                />
                                <p v-if="form.errors.caption" class="text-sm text-destructive">
                                    {{ form.errors.caption }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Media</Label>
                                <p class="text-sm text-muted-foreground mb-2">Select media to attach to this post</p>
                                <div v-if="props.media.length === 0" class="text-sm text-muted-foreground">
                                    No media uploaded yet. Upload media first.
                                </div>
                                <div v-else class="grid grid-cols-4 gap-2">
                                    <div
                                        v-for="item in props.media"
                                        :key="item.id"
                                        :class="[
                                            'relative cursor-pointer rounded-lg border-2 overflow-hidden transition-colors',
                                            form.media_ids.includes(item.id) ? 'border-primary' : 'border-transparent hover:border-muted-foreground/25',
                                        ]"
                                        @click="toggleMedia(item.id)"
                                    >
                                        <div class="aspect-square">
                                            <img
                                                v-if="item.type === 'image'"
                                                :src="item.url"
                                                :alt="item.fileName"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="flex h-full items-center justify-center bg-muted">
                                                <span class="text-xs">Video</span>
                                            </div>
                                        </div>
                                        <div v-if="form.media_ids.includes(item.id)" class="absolute inset-0 bg-primary/20 flex items-center justify-center">
                                            <div class="h-6 w-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs">✓</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Publish Settings</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="social_account_id">Account</Label>
                                <Select v-model="form.social_account_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select an account" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="account in props.accounts"
                                            :key="account.id"
                                            :value="account.id.toString()"
                                        >
                                            {{ account.name }} ({{ account.platform }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.social_account_id" class="text-sm text-destructive">
                                    {{ form.errors.social_account_id }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="platform">Platform</Label>
                                <Select v-model="form.platform">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="facebook">Facebook</SelectItem>
                                        <SelectItem value="instagram">Instagram</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="visibility">Visibility</Label>
                                <Select v-model="form.visibility">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="public">Public</SelectItem>
                                        <SelectItem value="friends">Friends</SelectItem>
                                        <SelectItem value="only_me">Only Me</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="scheduled">Schedule</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-if="form.status === 'scheduled'" class="space-y-2">
                                <Label for="scheduled_at">Schedule Date & Time</Label>
                                <Input
                                    id="scheduled_at"
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                />
                                <p v-if="form.errors.scheduled_at" class="text-sm text-destructive">
                                    {{ form.errors.scheduled_at }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing" class="flex-1">
                            <Send class="mr-2 h-4 w-4" />
                            {{ form.status === 'scheduled' ? 'Schedule' : 'Save' }} Post
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
