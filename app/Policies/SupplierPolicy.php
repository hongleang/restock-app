<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $supplier->shop->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->shops()->exists();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $supplier->shop->user_id === $user->id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $supplier->shop->user_id === $user->id;
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $supplier->shop->user_id === $user->id;
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $supplier->shop->user_id === $user->id;
    }
}
