<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\SaveProductRequest;
use App\Models\Product;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the team's products.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [Product::class, $currentTeam]);

        $products = $currentTeam->products()
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product));

        return Inertia::render('products/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [Product::class, $currentTeam]);

        return Inertia::render('products/Create');
    }

    /**
     * Store a newly created product.
     */
    public function store(SaveProductRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [Product::class, $currentTeam]);

        $product = $currentTeam->products()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.edit', ['current_team' => $currentTeam->slug, 'product' => $product->id]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Team $currentTeam, Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('products/Edit', [
            'product' => $this->formatProduct($product),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(SaveProductRequest $request, Team $currentTeam, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.edit', ['current_team' => $currentTeam->slug, 'product' => $product->id]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Team $currentTeam, Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Format a product for the frontend.
     *
     * @return array{id: int, name: string, sku: string|null, description: string|null, price: float, stock: int, isActive: bool}
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'isActive' => $product->is_active,
        ];
    }
}
