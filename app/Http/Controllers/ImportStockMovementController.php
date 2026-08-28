<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStockMovementsRequest;
use App\Imports\StockMovementImport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ImportStockMovementController extends Controller
{
    public function __invoke(ImportStockMovementsRequest $request): RedirectResponse
    {
        $importer = new StockMovementImport(auth()->user()->id);
        try {
            $importer->import($request->file('file'));
        } catch (\Throwable $e) {
            report($e);
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Something went wrong with the import.']);

            return back();
        }

        if ($importer->getTotalRows() === 0) {
            return back()->withErrors(['file' => $importer->getErrors() ?: ['No stock movements could be imported from this file.']]);
        }

        $message = trans_choice('Imported :count stock movement.|Imported :count stock movements.', $importer->getTotalRows(), [
            'count' => $importer->getTotalRows(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return to_route('stock-movements.index');
    }
}
