<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Package } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { index, store } from '@/routes/products';
import type { Team } from '@/types';

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const form = useForm({
    name: '',
    sku: '',
    description: '',
    price: '',
    stock: '0',
    is_active: true,
});

const submit = () => {
    form.post(store(teamSlug.value).url);
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Products', href: index(props.currentTeam.slug).url },
            { title: 'New product', href: '#' },
        ],
    }),
});
</script>

<template>
    <Head title="New product" />

    <div class="mx-auto max-w-2xl space-y-6">
        <!-- Back link + header -->
        <div>
            <Button variant="ghost" size="sm" class="-ml-2 mb-3 text-muted-foreground" as-child>
                <a :href="index(teamSlug).url">
                    <ArrowLeft class="h-4 w-4" />
                    Back to products
                </a>
            </Button>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                    <Package class="h-5 w-5 text-primary" />
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">New product</h2>
                    <p class="text-sm text-muted-foreground">Add a product to your catalogue</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Basic information</CardTitle>
                    <CardDescription>Name, SKU, and description of the product.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="name">
                            Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            data-test="product-name-input"
                            placeholder="e.g. Wireless Headphones"
                            :class="form.errors.name ? 'border-destructive' : ''"
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            v-model="form.sku"
                            data-test="product-sku-input"
                            placeholder="e.g. WH-1000XM5"
                            class="font-mono"
                        />
                        <p class="text-xs text-muted-foreground">
                            Optional stock keeping unit identifier.
                        </p>
                        <InputError :message="form.errors.sku" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            data-test="product-description-input"
                            placeholder="Optional product description..."
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                        ></textarea>
                        <InputError :message="form.errors.description" />
                    </div>
                </CardContent>
            </Card>

            <!-- Pricing & Inventory -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Pricing & inventory</CardTitle>
                    <CardDescription>Set the price and stock level.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="price">
                                Price (USD) <span class="text-destructive">*</span>
                            </Label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted-foreground text-sm">$</span>
                                <Input
                                    id="price"
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    data-test="product-price-input"
                                    placeholder="0.00"
                                    class="pl-7"
                                    :class="form.errors.price ? 'border-destructive' : ''"
                                />
                            </div>
                            <InputError :message="form.errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="stock">
                                Stock <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="stock"
                                v-model="form.stock"
                                type="number"
                                min="0"
                                step="1"
                                data-test="product-stock-input"
                                placeholder="0"
                                :class="form.errors.stock ? 'border-destructive' : ''"
                            />
                            <InputError :message="form.errors.stock" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Settings -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Settings</CardTitle>
                </CardHeader>
                <CardContent>
                    <label class="flex cursor-pointer items-start gap-3" for="is_active">
                        <Checkbox
                            id="is_active"
                            v-model:checked="form.is_active"
                            data-test="product-active-checkbox"
                            class="mt-0.5"
                        />
                        <div>
                            <p class="font-medium text-sm">Active</p>
                            <p class="text-xs text-muted-foreground">
                                Active products are visible in your catalogue.
                            </p>
                        </div>
                    </label>
                    <InputError :message="form.errors.is_active" />
                </CardContent>
            </Card>

            <!-- Footer actions -->
            <div class="flex items-center justify-end gap-3">
                <Button variant="outline" as-child>
                    <a :href="index(teamSlug).url">Cancel</a>
                </Button>
                <Button
                    type="submit"
                    data-test="product-save-button"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Creating…</span>
                    <span v-else>Create product</span>
                </Button>
            </div>
        </form>
    </div>
</template>
