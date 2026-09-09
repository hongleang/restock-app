<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Notifications\LowStock;
use App\Services\ForecastService;
use Illuminate\Console\Command;

class SendLowStockNotificationCommand extends Command
{
    protected $signature = 'send:low-stock-notification';

    protected $description = 'check product reorder list per shop and fires a Notification (email, or in-app) if the list is non-empty';

    public function handle(): void
    {
        $forecast = new ForecastService();
        $shops = Shop::with('user')->get();

        foreach ($shops as $shop) {
            $reorderList = $forecast->reorderList($shop->id);

            if ($reorderList->isNotEmpty()) {
                $shop->user->notify(new LowStock($shop, $reorderList));
            }
        }

    }
}
