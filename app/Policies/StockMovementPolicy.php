<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockMovementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $stockMovement->product->shop->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        return $stockMovement->product->shop->user_id === $user->id;
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        return $stockMovement->product->shop->user_id === $user->id;
    }

    public function restore(User $user, StockMovement $stockMovement): bool
    {
        return $stockMovement->product->shop->user_id === $user->id;
    }

    public function forceDelete(User $user, StockMovement $stockMovement): bool
    {
        return false;
    }
}
