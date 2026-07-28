<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Megaphone, Plus, Pencil, Trash2, Pause, Play } from '@lucide/vue';
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

type Campaign = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    platform: string;
    budget: number | null;
    spent: number;
    objective: string | null;
    startDate: string | null;
    endDate: string | null;
    socialPost: { id: number; caption: string | null; platform: string } | null;
    createdAt: string;
};

type Props = {
    campaigns: Campaign[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const confirmingDelete = ref<number | null>(null);

const handleDelete = (campaign: Campaign) => {
    if (confirmingDelete.value === campaign.id) {
        router.delete(
            `/${teamSlug.value}/social/campaigns/${campaign.id}`,
            {
                preserveScroll: true,
                onFinish: () => (confirmingDelete.value = null),
            },
        );
    } else {
        confirmingDelete.value = campaign.id;
    }
};

const handlePause = (campaign: Campaign) => {
    router.post(`/${teamSlug.value}/social/campaigns/${campaign.id}/pause`, {}, {
        preserveScroll: true,
    });
};

const handleResume = (campaign: Campaign) => {
    router.post(`/${teamSlug.value}/social/campaigns/${campaign.id}/resume`, {}, {
        preserveScroll: true,
    });
};

const statusColor = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'paused':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'completed':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'failed':
            return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatCurrency = (amount: number | null) => {
    if (amount === null) return '—';
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Campaigns', href: `/${props.currentTeam.slug}/social/campaigns` },
        ],
    }),
});
</script>

<template>
    <Head title="Campaigns" />

    <div class="flex flex-col space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Campaigns</h2>
                <p class="text-sm text-muted-foreground">
                    Create and manage marketing campaigns
                </p>
            </div>
            <Button as-child>
                <Link :href="`/${teamSlug}/social/campaigns/create`">
                    <Plus class="mr-2 h-4 w-4" />
                    New Campaign
                </Link>
            </Button>
        </div>

        <!-- Campaigns Table -->
        <Card>
            <CardHeader>
                <CardTitle>All Campaigns</CardTitle>
                <CardDescription>{{ campaigns.length }} campaign{{ campaigns.length !== 1 ? 's' : '' }}</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div
                    v-if="campaigns.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <Megaphone class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-1 font-medium">No campaigns yet</h3>
                    <p class="mb-6 text-sm text-muted-foreground">
                        Create your first marketing campaign to get started.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="`/${teamSlug}/social/campaigns/create`">
                            <Plus class="mr-2 h-4 w-4" />
                            New Campaign
                        </Link>
                    </Button>
                </div>

                <template v-else>
                    <div class="grid grid-cols-12 gap-4 border-b bg-muted/40 px-6 py-3 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <div class="col-span-3">Campaign</div>
                        <div class="col-span-2">Platform</div>
                        <div class="col-span-2">Budget</div>
                        <div class="col-span-1">Status</div>
                        <div class="col-span-2">Period</div>
                        <div class="col-span-2"></div>
                    </div>

                    <div
                        v-for="campaign in campaigns"
                        :key="campaign.id"
                        class="grid grid-cols-12 items-center gap-4 border-b px-6 py-4 last:border-0 hover:bg-muted/30 transition-colors"
                    >
                        <!-- Name -->
                        <div class="col-span-3 min-w-0">
                            <p class="truncate text-sm font-medium">{{ campaign.name }}</p>
                            <p v-if="campaign.objective" class="text-xs text-muted-foreground">{{ campaign.objective }}</p>
                        </div>

                        <!-- Platform -->
                        <div class="col-span-2">
                            <Badge :variant="campaign.platform === 'facebook' ? 'default' : 'secondary'">
                                {{ campaign.platform }}
                            </Badge>
                        </div>

                        <!-- Budget -->
                        <div class="col-span-2">
                            <span class="text-sm font-medium">{{ formatCurrency(campaign.budget) }}</span>
                            <span v-if="campaign.budget" class="text-xs text-muted-foreground block">
                                Spent: {{ formatCurrency(campaign.spent) }}
                            </span>
                        </div>

                        <!-- Status -->
                        <div class="col-span-1">
                            <Badge :class="statusColor(campaign.status)" variant="outline">
                                {{ campaign.status }}
                            </Badge>
                        </div>

                        <!-- Period -->
                        <div class="col-span-2 text-xs text-muted-foreground">
                            <span v-if="campaign.startDate">
                                {{ new Date(campaign.startDate).toLocaleDateString() }}
                                <span v-if="campaign.endDate"> - {{ new Date(campaign.endDate).toLocaleDateString() }}</span>
                            </span>
                            <span v-else>—</span>
                        </div>

                        <!-- Actions -->
                        <div class="col-span-2 flex items-center justify-end gap-1">
                            <template v-if="confirmingDelete === campaign.id">
                                <Button variant="destructive" size="sm" class="h-7 px-2 text-xs" @click="handleDelete(campaign)">
                                    Confirm
                                </Button>
                                <Button variant="outline" size="sm" class="h-7 px-2 text-xs" @click="confirmingDelete = null">
                                    Cancel
                                </Button>
                            </template>

                            <TooltipProvider v-else>
                                <div class="flex items-center gap-1">
                                    <Tooltip v-if="campaign.status === 'active'">
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="handlePause(campaign)">
                                                <Pause class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Pause</TooltipContent>
                                    </Tooltip>

                                    <Tooltip v-if="campaign.status === 'paused'">
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="handleResume(campaign)">
                                                <Play class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Resume</TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0" as-child>
                                                <Link :href="`/${teamSlug}/social/campaigns/${campaign.id}/edit`">
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit</TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive" @click="handleDelete(campaign)">
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
