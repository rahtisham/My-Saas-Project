<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Users, Trash2, Plus, ExternalLink } from '@lucide/vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    pageId: string | null;
    instagramAccountId: string | null;
    profilePictureUrl: string | null;
    isActive: boolean;
    createdAt: string;
};

type Props = {
    accounts: Account[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const showingForm = ref(false);

const form = useForm({
    platform: 'facebook',
    name: '',
    page_id: '',
    instagram_account_id: '',
    access_token: '',
});

const submit = () => {
    form.post(`/${teamSlug.value}/social/accounts`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showingForm.value = false;
        },
    });
};

const confirmingDelete = ref<number | null>(null);

const handleDelete = (account: Account) => {
    if (confirmingDelete.value === account.id) {
        router.delete(
            `/${teamSlug.value}/social/accounts/${account.id}`,
            {
                preserveScroll: true,
                onFinish: () => (confirmingDelete.value = null),
            },
        );
    } else {
        confirmingDelete.value = account.id;
    }
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Accounts', href: `/${props.currentTeam.slug}/social/accounts` },
        ],
    }),
});
</script>

<template>
    <Head title="Social Accounts" />

    <div class="flex flex-col space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Connected Accounts</h2>
                <p class="text-sm text-muted-foreground">
                    Manage your Facebook and Instagram account connections
                </p>
            </div>
            <Button @click="showingForm = !showingForm">
                <Plus class="mr-2 h-4 w-4" />
                Connect Account
            </Button>
        </div>

        <!-- Connect Form -->
        <Card v-if="showingForm" class="border-primary/20">
            <CardHeader>
                <CardTitle>Connect a Social Account</CardTitle>
                <CardDescription>
                    Enter your Facebook or Instagram page details. You can find your Page ID and Access Token in the Facebook Developer dashboard.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
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
                            <p v-if="form.errors.platform" class="text-sm text-destructive">{{ form.errors.platform }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="name">Page Name</Label>
                            <Input id="name" v-model="form.name" placeholder="My Facebook Page" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="page_id">{{ form.platform === 'instagram' ? 'Instagram Business Account ID' : 'Facebook Page ID' }}</Label>
                            <Input id="page_id" v-model="form.page_id" :placeholder="form.platform === 'instagram' ? '17841400...' : '1234567890'" />
                            <p v-if="form.platform === 'facebook'" class="text-xs text-muted-foreground">
                                Find it in your Facebook Page Settings → About → Page ID
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Find it via Graph API Explorer: GET /me?fields=instagram_business_account
                            </p>
                            <p v-if="form.errors.page_id" class="text-sm text-destructive">{{ form.errors.page_id }}</p>
                        </div>

                        <div v-if="form.platform === 'facebook'" class="space-y-2">
                            <Label for="instagram_account_id">Linked Instagram Business Account ID (optional)</Label>
                            <Input id="instagram_account_id" v-model="form.instagram_account_id" placeholder="17841400..." />
                            <p class="text-xs text-muted-foreground">
                                Required to publish Instagram posts from this Facebook page. Find it via Graph API Explorer: GET /{page-id}?fields=instagram_business_account
                            </p>
                            <p v-if="form.errors.instagram_account_id" class="text-sm text-destructive">{{ form.errors.instagram_account_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="access_token">Page Access Token</Label>
                            <Input id="access_token" v-model="form.access_token" type="password" placeholder="EAAB..." />
                            <p v-if="form.errors.access_token" class="text-sm text-destructive">{{ form.errors.access_token }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Connecting...' : 'Connect Account' }}
                        </Button>
                        <Button type="button" variant="ghost" size="sm" @click="showingForm = false">
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Accounts Grid -->
        <div>
            <div
                v-if="accounts.length === 0"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                    <Users class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="mb-1 font-medium">No accounts connected</h3>
                <p class="mb-6 text-sm text-muted-foreground">
                    Click "Connect Account" above to add your Facebook or Instagram page.
                </p>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="account in accounts"
                    :key="account.id"
                    class="group relative overflow-hidden"
                >
                    <CardContent class="pt-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    :class="[
                                        'flex h-12 w-12 items-center justify-center rounded-xl text-base font-bold text-white shadow-sm',
                                        account.platform === 'facebook' ? 'bg-blue-600' : 'bg-gradient-to-br from-purple-500 to-pink-500',
                                    ]"
                                >
                                    {{ account.platform === 'facebook' ? 'f' : 'ig' }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ account.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ account.platform }} · ID: {{ account.pageId || 'N/A' }}
                                    </p>
                                    <p v-if="account.instagramAccountId" class="text-xs text-muted-foreground">
                                        IG Account: {{ account.instagramAccountId }}
                                    </p>
                                </div>
                            </div>
                            <Badge :variant="account.isActive ? 'default' : 'secondary'" class="text-xs">
                                {{ account.isActive ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t pt-4">
                            <p class="text-xs text-muted-foreground">
                                Connected {{ new Date(account.createdAt).toLocaleDateString() }}
                            </p>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <template v-if="confirmingDelete === account.id">
                                    <Button variant="destructive" size="sm" class="h-7 px-2 text-xs" @click="handleDelete(account)">
                                        Confirm
                                    </Button>
                                    <Button variant="outline" size="sm" class="h-7 px-2 text-xs" @click="confirmingDelete = null">
                                        Cancel
                                    </Button>
                                </template>
                                <Button
                                    v-else
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                    @click="handleDelete(account)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
