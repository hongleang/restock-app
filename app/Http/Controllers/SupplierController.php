<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    #[Authorize('view-any', Supplier::class)]
    public function index(Request $request): Response
    {
        $shops = $request->user()->shops()->get(['id', 'name']);
        $shopIds = $shops->pluck('id');

        $search = trim((string) $request->string('search'));
        $shopFilter = $request->integer('shop_id') ?: null;

        $suppliers = Supplier::query()
            ->whereIn('shop_id', $shopIds)
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.addcslashes($search, '%_').'%';

                $query->where(fn ($query) => $query
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('contact_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($shopFilter, fn ($query, $value) => $query->where('shop_id', $value))
            ->with('shop:id,name')
            ->latest()
            ->simplePaginate(15)
            ->withQueryString();

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'shops' => $shops,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'shop_id' => $shopFilter,
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier created.')]);

        return to_route('suppliers.index');
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier updated.')]);

        return to_route('suppliers.index');
    }

    #[Authorize('delete', 'supplier')]
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier deleted.')]);

        return to_route('suppliers.index');
    }
}
