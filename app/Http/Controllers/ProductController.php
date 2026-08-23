<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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

        $products = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['shop:id,name', 'supplier:id,first_name,last_name'])
            ->latest()
            ->simplePaginate();

        $suppliers = Supplier::query()
            ->whereIn('shop_id', $shopIds)
            ->get(['id', 'shop_id', 'first_name', 'last_name']);

        return Inertia::render('products/Index', [
            'products' => $products,
            'shops' => $shops,
            'suppliers' => $suppliers,
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
