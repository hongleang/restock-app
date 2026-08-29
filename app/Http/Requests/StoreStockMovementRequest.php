<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockMovement::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $shopIds = $this->user()->shops()->pluck('id');

        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereIn('shop_id', $shopIds),
            ],
            'quantity' => ['required', 'integer'],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
