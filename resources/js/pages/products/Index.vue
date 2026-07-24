<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Package,
    PackagePlus,
    Pencil,
    Plus,
    Trash2,
    TrendingUp,
    AlertTriangle,
    CheckCircle2,
    XCircle,
} from '@lucide/vue';
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
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { create, destroy, edit, index } from '@/routes/products';
import type { Product, Team } from '@/types';

type Props = {
    products: Product[];
};

const props = defineProps<Props>();

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);
const createUrl = computed(() => create(teamSlug.value).url);
const editUrl = (productId: number) =>
    edit({ current_team: teamSlug.value, product: productId }).url;

const confirmingDelete = ref<number | null>(null);

const handleDelete = (product: Product) => {
    if (confirmingDelete.value === product.id) {
        router.delete(
            destroy({ current_team: teamSlug.value, product: product.id }).url,
            {
                preserveScroll: true,
                onFinish: () => (confirmingDelete.value = null),
            },
        );
    } else {
        confirmingDelete.value = product.id;
    }
};

const formatPrice = (price: number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(price);

const totalProducts = computed(() => props.products.length);
const activeProducts = computed(
    () => props.products.filter((p) => p.isActive).length,
);
const outOfStock = computed(
    () => props.products.filter((p) => p.stock === 0).length,
);
const totalValue = computed(() =>
    props.products.reduce((sum, p) => sum + p.price * p.stock, 0),
);

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Products', href: index(props.currentTeam.slug).url },
        ],
    }),
});
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-col space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Products</h2>
                <p class="text-sm text-muted-foreground">
                    Manage your team's product catalogue
                </p>
            </div>
            <Button as-child data-test="products-new-button">
                <Link :href="createUrl">
                    <Plus class="h-4 w-4" />
                    New product
                </Link>
            </Button>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total</p>
                            <p class="text-2xl font-bold">{{ totalProducts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                            <Package class="h-5 w-5 text-primary" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Active</p>
                            <p class="text-2xl font-bold">{{ activeProducts }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                            <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Out of Stock</p>
                            <p class="text-2xl font-bold">{{ outOfStock }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20">
                            <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Inventory Value</p>
                            <p class="text-2xl font-bold">{{ formatPrice(totalValue) }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                            <TrendingUp class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Product Table -->
        <Card>
            <CardHeader>
                <CardTitle>All products</CardTitle>
                <CardDescription>
                    {{ totalProducts }} product{{ totalProducts !== 1 ? 's' : '' }} in your catalogue
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <!-- Empty state -->
                <div
                    v-if="products.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <PackagePlus class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-1 font-medium">No products yet</h3>
                    <p class="mb-6 text-sm text-muted-foreground">
                        Get started by creating your first product.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="createUrl">
                            <Plus class="h-4 w-4" />
                            New product
                        </Link>
                    </Button>
                </div>

                <!-- Table -->
                <template v-else>
                    <!-- Table header -->
                    <div class="grid grid-cols-12 gap-4 border-b bg-muted/40 px-6 py-3 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <div class="col-span-4">Product</div>
                        <div class="col-span-2 hidden sm:block">SKU</div>
                        <div class="col-span-2 text-right">Price</div>
                        <div class="col-span-2 text-right">Stock</div>
                        <div class="col-span-1 text-center hidden sm:block">Status</div>
                        <div class="col-span-1 sm:col-span-1"></div>
                    </div>

                    <!-- Table rows -->
                    <div
                        v-for="product in products"
                        :key="product.id"
                        data-test="product-row"
                        class="grid grid-cols-12 items-center gap-4 border-b px-6 py-4 last:border-0 hover:bg-muted/30 transition-colors"
                    >
                        <!-- Name -->
                        <div class="col-span-4 min-w-0">
                            <p class="truncate font-medium">{{ product.name }}</p>
                            <p
                                v-if="product.description"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ product.description }}
                            </p>
                        </div>

                        <!-- SKU -->
                        <div class="col-span-2 hidden sm:block">
                            <span
                                v-if="product.sku"
                                class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs"
                            >
                                {{ product.sku }}
                            </span>
                            <span v-else class="text-xs text-muted-foreground">—</span>
                        </div>

                        <!-- Price -->
                        <div class="col-span-2 text-right font-medium tabular-nums">
                            {{ formatPrice(product.price) }}
                        </div>

                        <!-- Stock -->
                        <div class="col-span-2 text-right">
                            <span
                                class="font-medium tabular-nums"
                                :class="product.stock === 0 ? 'text-destructive' : ''"
                            >
                                {{ product.stock }}
                            </span>
                        </div>

                        <!-- Status -->
                        <div class="col-span-1 hidden justify-center sm:flex">
                            <Badge
                                v-if="product.isActive"
                                class="bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800"
                                variant="outline"
                            >
                                <CheckCircle2 class="h-3 w-3" />
                                Active
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="text-muted-foreground"
                            >
                                <XCircle class="h-3 w-3" />
                                Inactive
                            </Badge>
                        </div>

                        <!-- Actions -->
                        <div class="col-span-1 flex items-center justify-end gap-1">
                            <template v-if="confirmingDelete === product.id">
                                <Button
                                    data-test="product-delete-confirm-button"
                                    variant="destructive"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="handleDelete(product)"
                                >
                                    Confirm
                                </Button>
                                <Button
                                    data-test="product-delete-cancel-button"
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
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                data-test="product-edit-button"
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                                as-child
                                            >
                                                <Link :href="editUrl(product.id)">
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit</TooltipContent>
                                    </Tooltip>

                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                data-test="product-delete-button"
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                                @click="handleDelete(product)"
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
