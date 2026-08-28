<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    #[Authorize('view-any', StockMovement::class)]
    public function index(Request $request): Response
    {
        $shops = $request->user()->shops()->get(['id', 'name']);
        $shopIds = $shops->pluck('id');

        $productFilter = $request->integer('product_id') ?: null;
        $typeFilter = StockMovementType::tryFrom((string) $request->string('type'));
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        $movements = StockMovement::query()
            ->whereHas('product', fn ($query) => $query->whereIn('shop_id', $shopIds))
            ->when($productFilter, fn ($query, $value) => $query->where('product_id', $value))
            ->when($typeFilter, fn ($query, $value) => $query->where('type', $value))
            ->when($from, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($to, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->with(['product:id,name,shop_id', 'user:id,first_name,last_name'])
            ->latest()
            ->simplePaginate(15)
            ->withQueryString();

        $products = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->get(['id', 'shop_id', 'name']);

        $types = array_map(
            fn (StockMovementType $type) => ['value' => $type->value, 'label' => $type->label()],
            StockMovementType::cases(),
        );

        return Inertia::render('stock-movements/Index', [
            'movements' => $movements,
            'products' => $products,
            'types' => $types,
            'shops' => $shops,
            'filters' => [
                'product_id' => $productFilter,
                'type' => $typeFilter?->value,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        StockMovement::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'quantity' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock movement recorded.')]);

        return to_route('stock-movements.index');
    }

    public function update(UpdateStockMovementRequest $request, StockMovement $stockMovement): RedirectResponse
    {
        $stockMovement->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock movement updated.')]);

        return to_route('stock-movements.index');
    }

    #[Authorize('delete', 'stock_movement')]
    public function destroy(StockMovement $stockMovement): RedirectResponse
    {
        $stockMovement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock movement deleted.')]);

        return to_route('stock-movements.index');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
