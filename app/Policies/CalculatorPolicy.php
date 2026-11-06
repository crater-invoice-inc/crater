<?php

namespace Crater\Policies;

use Crater\Models\Calculator;
use Crater\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class CalculatorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Crater\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if (BouncerFacade::can('view-calculator', Calculator::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Calculator  $calculator
     * @return mixed
     */
    public function view(User $user, $calculator = null)
    {
        $model = $calculator ?? Calculator::class;

        if (BouncerFacade::can('view-calculator', $model) && (! is_object($calculator) || $user->hasCompany($calculator->company_id))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Crater\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (BouncerFacade::can('create-calculator', Calculator::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Calculator  $calculator
     * @return mixed
     */
    public function update(User $user, $calculator = null)
    {
        $model = $calculator ?? Calculator::class;

        if (BouncerFacade::can('edit-calculator', $model) && (! is_object($calculator) || $user->hasCompany($calculator->company_id))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Calculator  $calculator
     * @return mixed
     */
    public function delete(User $user, $calculator = null)
    {
        $model = $calculator ?? Calculator::class;

        if (BouncerFacade::can('delete-calculator', $model) && (! is_object($calculator) || $user->hasCompany($calculator->company_id))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete models.
     *
     * @param  \Crater\Models\User  $user
     * @return mixed
     */
    public function deleteMultiple(User $user)
    {
        if (BouncerFacade::can('delete-calculator', Calculator::class)) {
            return true;
        }

        return false;
    }
}
