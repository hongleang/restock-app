<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function view(User $user, Product $product): bool
    {
        return $product->shop->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function update(User $user, Product $product): bool
    {
        return $product->shop->user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->shop->user_id === $user->id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $product->shop->user_id === $user->id;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
