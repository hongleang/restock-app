<x-mail::message>
# Greeting
Hi {{ $shop->user->name }},
Based on your recent sales, the following products are projected to run out before your next scheduled delivery
arrives. Reordering now should keep you covered.

<x-mail::table>
| Product | Current Stock | Est. Days Until Stockout | Supplier |
| -------- | :--------: | :--------: | -------- |
@foreach ($reorderList as $item)
| {{ $item['product']->name }} | {{ $item['product']->current_stock }} | {{ $item['days_until_stockout'] !== null ? round($item['days_until_stockout']) . ' days' : '—' }} | {{ "{$item['product']['supplier']?->first_name} {$item['product']['supplier']?->last_name}" }} |
@endforeach
</x-mail::table>

This is an automated check based on your last 30 days of sales — if something looks off, it's worth a quick manual review before placing an order.

<x-mail::button :url="$url">
    View Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
