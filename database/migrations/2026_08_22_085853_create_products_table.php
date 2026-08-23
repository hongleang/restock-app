<?php

use App\Models\Shop;
use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->string('barcode');
            $table->string('category');
            $table->decimal('cost_price', 10)->default(0);
            $table->decimal('sell_price', 10)->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('reorder_qty')->default(0);

            $table->foreignIdFor(Shop::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Supplier::class)->nullable()->constrained()->nullOnDelete();

            $table->unique(['shop_id', 'sku']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
