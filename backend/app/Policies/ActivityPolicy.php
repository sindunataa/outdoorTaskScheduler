<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function view(User $user, Activity $activity): bool
    {
        return $user->id === $activity->user_id;
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->id === $activity->user_id;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->id === $activity->user_id;
    }
}