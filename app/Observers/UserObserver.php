<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        logActivity('User Created', "Created user: {$user->email}");
    }

    public function updated(User $user)
    {
        logActivity('User Updated', "Updated user: {$user->email}");
    }

    public function deleted(User $user)
    {
        logActivity('User Deleted', "Deleted user: {$user->email}");
    }
}
