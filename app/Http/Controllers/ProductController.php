<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    #[Authorize('view-any', Product::class)]
    public function index(Request $request): Response
    {
        $shops = $request->user()->shops()->get(['id', 'name']);
        $shopIds = $shops->pluck('id');

        $search = trim((string) $request->string('search'));
        $shopFilter = $request->integer('shop') ?: null;
        $category = trim((string) $request->string('category'));
        $supplierFilter = $request->integer('supplier') ?: null;
        $lowStock = $request->boolean('low_stock');

        $products = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->searchBy($search, ['name', 'sku'])
            ->when($shopFilter, fn ($query, $value) => $query->where('shop_id', $value))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($supplierFilter, fn ($query, $value) => $query->where('supplier_id', $value))
            ->when($lowStock, fn ($query) => $query->whereColumn('current_stock', '<=', 'reorder_point'))
            ->with(['shop:id,name', 'supplier:id,first_name,last_name'])
            ->latest()
            ->simplePaginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()
            ->whereIn('shop_id', $shopIds)
            ->get(['id', 'shop_id', 'first_name', 'last_name']);

        $categories = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('products/Index', [
            'products' => ProductResource::collection($products),
            'shops' => $shops,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'shop_id' => $shopFilter,
                'category' => $category !== '' ? $category : null,
                'supplier_id' => $supplierFilter,
                'low_stock' => $lowStock,
            ],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.index');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.index');
    }

    #[Authorize('delete', 'product')]
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index');
    }
}
