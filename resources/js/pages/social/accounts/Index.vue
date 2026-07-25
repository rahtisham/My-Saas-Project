<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Users, Trash2, Plus } from '@lucide/vue';
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

    <div class="flex flex-col space-y-6">
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
        <Card v-if="showingForm">
            <CardHeader>
                <CardTitle>Connect a Social Account</CardTitle>
                <CardDescription>Enter your Facebook or Instagram page details</CardDescription>
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
                            <Label for="page_id">Page ID</Label>
                            <Input id="page_id" v-model="form.page_id" placeholder="1234567890" />
                            <p v-if="form.errors.page_id" class="text-sm text-destructive">{{ form.errors.page_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="access_token">Page Access Token</Label>
                            <Input id="access_token" v-model="form.access_token" type="password" placeholder="EAAB..." />
                            <p v-if="form.errors.access_token" class="text-sm text-destructive">{{ form.errors.access_token }}</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">
                            Connect Account
                        </Button>
                        <Button type="button" variant="outline" @click="showingForm = false">
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Accounts List -->
        <Card>
            <CardHeader>
                <CardTitle>Accounts</CardTitle>
                <CardDescription>{{ accounts.length }} connected account{{ accounts.length !== 1 ? 's' : '' }}</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
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

                <div v-else>
                    <div
                        v-for="account in accounts"
                        :key="account.id"
                        class="flex items-center justify-between border-b px-6 py-4 last:border-0"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white',
                                    account.platform === 'facebook' ? 'bg-blue-600' : 'bg-gradient-to-br from-purple-500 to-pink-500',
                                ]"
                            >
                                {{ account.platform === 'facebook' ? 'f' : 'ig' }}
                            </div>
                            <div>
                                <p class="text-sm font-medium">{{ account.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ account.platform }} &middot; ID: {{ account.pageId || 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge :variant="account.isActive ? 'default' : 'secondary'">
                                {{ account.isActive ? 'Active' : 'Inactive' }}
                            </Badge>

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
                </div>
            </CardContent>
        </Card>
    </div>
</template>
