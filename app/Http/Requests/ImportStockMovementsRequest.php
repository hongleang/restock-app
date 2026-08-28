<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStockMovementsRequest extends FormRequest
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
        return [
            'shop_id' => [
                'required',
                Rule::exists('shops', 'id')->where('user_id', $this->user()->id),
            ],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
