<?php

namespace App\Http\Controllers;

use App\Enums\Reorder;
use App\Services\ForecastService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected ForecastService $forecast) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->shops()->exists(), 403);

        $shop = $request->user()->currentShop();

        return Inertia::render('Dashboard', [
            'reorderList' => $this->forecast->reorderList($shop->id)
                ->map(function ($item) use ($shop) {
                    $urgency = Reorder::getUrgency($item['days_until_stockout']);

                    return [
                        'product' => [
                            'id' => $item['product']->id,
                            'name' => $item['product']->name,
                            'sku' => $item['product']->sku,
                            'current_stock' => $item['product']->current_stock,
                        ],
                        'shop' => $shop,
                        'days_until_stockout' => $item['days_until_stockout'],
                        'urgency' => $urgency->value,
                        'urgency_colour' => $urgency->colour(),
                    ];
                })
                ->values(),
        ]);
    }
}
