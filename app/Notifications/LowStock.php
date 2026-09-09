<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStock extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Shop       $shop,
        private readonly Collection $reorderList
    )
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $productCount = $this->reorderList->count();

        return (new MailMessage)
            ->subject("$productCount products need ordering - {$this->shop->name}")
            ->markdown('mail.stock.low', [
                'shop' => $this->shop,
                'reorderList' => $this->reorderList,
                'url' => route('dashboard'),
            ]);
    }
}
