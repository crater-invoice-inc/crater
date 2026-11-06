<?php

namespace Crater\Services\Ai;

use Crater\Models\Company;
use Illuminate\Http\Request;

/**
 * Access rule for the AI assistant (v1).
 *
 * The primary rule is company ownership (companies.owner_id). The Crater
 * "super admin" role is also allowed. Regular admin/user roles are
 * explicitly denied, for both the settings and the assistant endpoints.
 */
class AiAccess
{
    public static function assertAuthorized(Request $request): void
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Authentification requise.');
        }

        if ($user->role === 'super admin') {
            return;
        }

        $company = Company::find($request->header('company'));

        if ($company && (int) $company->owner_id === (int) $user->id) {
            return;
        }

        abort(403, 'L’assistant IA est réservé au propriétaire de la société.');
    }
}
