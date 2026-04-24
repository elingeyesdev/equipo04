<?php

namespace App\Policies;

use App\Models\FloodReport;
use App\Models\User;

class FloodReportPolicy
{
    public function view(User $user, FloodReport $report): bool
    {
        return $user->isAuthority() || $report->citizen_carnet === $user->carnet;
    }

    public function create(User $user): bool
    {
        return $user->isCitizen() && ! $user->isBanned();
    }

    public function update(User $user, FloodReport $report): bool
    {
        if ($user->isAuthority()) {
            return true;
        }

        return $report->citizen_carnet === $user->carnet && $report->status === 'open';
    }
}
