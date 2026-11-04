<?php

namespace Crater\Policies;

use Crater\Models\Invoice;
use Crater\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Silber\Bouncer\BouncerFacade;

class InvoicePolicy
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
        if (BouncerFacade::can('view-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Invoice  $invoice
     * @return mixed
     */
    public function view(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('view-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
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
        if (BouncerFacade::can('create-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Invoice  $invoice
     * @return mixed
     */
    public function update(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('edit-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return $invoice->allow_edit;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Invoice  $invoice
     * @return mixed
     */
    public function delete(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Invoice  $invoice
     * @return mixed
     */
    public function restore(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Invoice  $invoice
     * @return mixed
     */
    public function forceDelete(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('delete-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can send email of the model.
     *
     * @param  \Crater\Models\User  $user
     * @param  \Crater\Models\Payment  $payment
     * @return mixed
     */
    public function send(User $user, Invoice $invoice)
    {
        if (BouncerFacade::can('send-invoice', $invoice) && $user->hasCompany($invoice->company_id)) {
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
        if (BouncerFacade::can('delete-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the status of models.
     *
     * @param  \Crater\Models\User  $user
     * @return mixed
     */
    public function sendMultiple(User $user)
    {
        if (BouncerFacade::can('send-invoice', Invoice::class)) {
            return true;
        }

        return false;
    }
}
