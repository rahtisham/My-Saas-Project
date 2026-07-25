<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Megaphone } from '@lucide/vue';
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

type Post = {
    id: number;
    caption: string | null;
    platform: string;
};

type Props = {
    posts: Post[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const form = useForm({
    name: '',
    description: '',
    social_post_id: '',
    platform: 'facebook',
    budget: '',
    objective: 'Engagement',
    start_date: '',
    end_date: '',
});

const submit = () => {
    form.post(`/${teamSlug.value}/social/campaigns`, {
        preserveScroll: true,
    });
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Campaigns', href: `/${props.currentTeam.slug}/social/campaigns` },
            { title: 'Create', href: `/${props.currentTeam.slug}/social/campaigns/create` },
        ],
    }),
});
</script>

<template>
    <Head title="Create Campaign" />

    <div class="flex flex-col space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Create Campaign</h2>
            <p class="text-sm text-muted-foreground">
                Create a new marketing campaign for your posts
            </p>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Campaign Details</CardTitle>
                            <CardDescription>Basic information about your campaign</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Campaign Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Summer Sale 2026"
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Describe your campaign goals..."
                                    rows="3"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="social_post_id">Linked Post (optional)</Label>
                                <Select v-model="form.social_post_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a post to promote" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No linked post</SelectItem>
                                        <SelectItem
                                            v-for="post in props.posts"
                                            :key="post.id"
                                            :value="post.id.toString()"
                                        >
                                            {{ post.caption?.slice(0, 50) || 'No caption' }} ({{ post.platform }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Campaign Settings</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
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
                                <Label for="objective">Objective</Label>
                                <Select v-model="form.objective">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Engagement">Engagement</SelectItem>
                                        <SelectItem value="Traffic">Traffic</SelectItem>
                                        <SelectItem value="Conversions">Conversions</SelectItem>
                                        <SelectItem value="Brand Awareness">Brand Awareness</SelectItem>
                                        <SelectItem value="Reach">Reach</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="budget">Budget (USD)</Label>
                                <Input
                                    id="budget"
                                    v-model="form.budget"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    placeholder="100.00"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="datetime-local"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="datetime-local"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" :disabled="form.processing" class="w-full">
                        <Megaphone class="mr-2 h-4 w-4" />
                        Create Campaign
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
