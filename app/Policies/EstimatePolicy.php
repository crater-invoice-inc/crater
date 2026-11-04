<?php

namespace Crater\Policies;

use Crater\Models\Estimate;
use Crater\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class EstimatePolicy
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
        if (BouncerFacade::can('view-estimate', Estimate::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $estimate
     * @return mixed
     */
    public function view(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('view-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
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
        if (BouncerFacade::can('create-estimate', Estimate::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $estimate
     * @return mixed
     */
    public function update(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('edit-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $estimate
     * @return mixed
     */
    public function delete(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $estimate
     * @return mixed
     */
    public function restore(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $estimate
     * @return mixed
     */
    public function forceDelete(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('delete-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send email of the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Estimate  $payment
     * @return mixed
     */
    public function send(User $user, Estimate $estimate)
    {
        if (BouncerFacade::can('send-estimate', $estimate) && $user->hasCompany($estimate->company_id)) {
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
        if (BouncerFacade::can('delete-estimate', Estimate::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update models.
     *
     * @param  \Crater\Models\User  $user
     * @return mixed
     */
    public function updateMultiple(User $user)
    {
        if (BouncerFacade::can('edit-estimate', Estimate::class)) {
            return true;
        }

        return false;
    }
}
