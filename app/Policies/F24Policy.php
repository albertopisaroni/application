<?php

namespace App\Policies;

use App\Models\F24;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class F24Policy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Tutti gli utenti autenticati possono vedere la lista
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, F24 $f24): bool
    {
        return $user->current_company_id === $f24->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Tutti gli utenti autenticati possono creare F24
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, F24 $f24): bool
    {
        return $user->current_company_id === $f24->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, F24 $f24): bool
    {
        return $user->current_company_id === $f24->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, F24 $f24): bool
    {
        return $user->current_company_id === $f24->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, F24 $f24): bool
    {
        return $user->current_company_id === $f24->company_id;
    }
}
