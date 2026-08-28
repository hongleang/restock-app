<?php

namespace App\Imports;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockMovementImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private array $errors = [];

    private int $totalRows = 0;

    public function __construct(private $userId) {}

    /**
     * @throws \Throwable
     */
    public function collection(Collection $collection): void
    {
        // Read every rows
        DB::transaction(function () use ($collection) {
            $collection->chunk(1000)->each(function ($chunk) {
                foreach ($chunk as $index => $row) {
                    $data = $row->toArray();

                    $validator = Validator::make($data, [
                        'sku' => ['required', 'exists:products,sku'],
                        'quantity' => ['required', 'integer'],
                        'type' => ['required', Rule::in(StockMovementType::values())],
                        'date' => ['nullable', 'date:Y-m-d'],
                        'note' => ['nullable', 'string', 'max:1000'],
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            $this->errors[] = "Row {$index}: {$message}";
                        }

                        continue;
                    }

                    $quantity = (int) $data['quantity'];

                    $quantity = match ($data['type']) {
                        StockMovementType::Sale->value => -abs($quantity),
                        StockMovementType::Restock->value => abs($quantity),
                        StockMovementType::Adjustment->value => $quantity,
                    };

                    $product = Product::where('sku', $data['sku'])->first();

                    $product->current_stock += $quantity;

                    if ($product->current_stock < 0) {
                        $this->errors[] = "Row {$index}: Quantity more than current stock ('$product->current_stock' larger).";

                        continue;
                    }

                    $product->save();

                    $movement = new StockMovement([
                        'product_id' => $product->id,
                        'user_id' => $this->userId,
                        'quantity' => $quantity,
                        'type' => $data['type'],
                        'note' => $data['note'] ?? null,
                    ]);

                    if (! empty($data['date'])) {
                        $movement->created_at = $data['date'];
                    }

                    $movement->save();

                    $this->totalRows++;
                }
            });
        });
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
}
